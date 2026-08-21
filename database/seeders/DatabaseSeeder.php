<?php

namespace Database\Seeders;

use App\Models\BodyMeasurement;
use App\Models\ClassBooking;
use App\Models\Comment;
use App\Models\Equipment;
use App\Models\Gym;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\Membership;
use App\Models\Notification;
use App\Models\NutritionPlan;
use App\Models\NutritionPlanItem;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Post;
use App\Models\Promotion;
use App\Models\Reaction;
use App\Models\Review;
use App\Models\Schedule;
use App\Models\Staff;
use App\Models\Trainer;
use App\Models\User;
use App\Models\WorkoutPlan;
use App\Models\WorkoutPlanItem;
use App\Services\AttendanceService;
use App\Services\BodyMeasurementService;
use App\Services\ClassBookingService;
use App\Services\EquipmentService;
use App\Services\MembershipService;
use App\Services\NotificationService;
use App\Services\PaymentService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;

/**
 * Seeder demo "phòng gym đang hoạt động thật": không chỉ tạo hồ sơ trống mà
 * còn dựng cả luồng nghiệp vụ (membership -> payment -> invoice, check-in,
 * đặt lớp, bảo trì thiết bị...) bằng CHÍNH các Service thật của ứng dụng
 * (MembershipService/PaymentService/AttendanceService/ClassBookingService/
 * EquipmentService), không tự tay chèn DB thô — đảm bảo dữ liệu demo không
 * bao giờ rơi vào trạng thái mâu thuẫn (vd. active mà không có payment paid).
 *
 * Với các bản ghi cần TRẢI DÀI theo thời gian (doanh thu 6 tháng, lịch sử
 * check-in 30 ngày), seeder "du hành thời gian" bằng Carbon::setTestNow()
 * xung quanh lời gọi Service — mọi now()/created_at/issued_at bên trong Service
 * tự động phản ánh đúng ngày mô phỏng, KHÔNG cần sửa Service để nhận tham số
 * ngày tháng giả.
 */
class DatabaseSeeder extends Seeder
{
    private const STAFF_PER_GYM = 2;

    private const TRAINERS_PER_GYM = 3;

    private const MEMBERS_PER_GYM = 15;

    private const GROUP_CLASSES_PER_GYM = 6;

    private const PT_SESSIONS_PER_GYM = 4;

    private const EQUIPMENT_PER_GYM = 10;

    private const POSTS_PER_GYM = 6;

    // Mỗi Gym có đủ 4 loại gói theo thời hạn cố định (thay vì random) để demo dễ so sánh.
    private const PACKAGE_DURATIONS_DAYS = [30, 90, 180, 365];

    // Số buổi PT đi kèm từng gói — cố định theo thời hạn (không random) để mọi
    // gói đều có ít nhất vài buổi PT dùng được khi demo đặt lớp PT.
    private const PACKAGE_PT_SESSIONS = [30 => 2, 90 => 4, 180 => 8, 365 => 16];

    // Dùng để sinh tên người Việt thật (thay vì "Hội viên N - TênGym") cho
    // staff/trainer/member khi demo, để giao diện không trông như dữ liệu giả.
    private const LAST_NAMES = ['Nguyễn', 'Trần', 'Lê', 'Phạm', 'Hoàng', 'Huỳnh', 'Phan', 'Vũ', 'Võ', 'Đặng', 'Bùi', 'Đỗ', 'Hồ', 'Ngô', 'Dương'];

    private const MALE_MIDDLE_NAMES = ['Văn', 'Hữu', 'Đức', 'Minh', 'Quang', 'Thành', 'Công'];

    private const FEMALE_MIDDLE_NAMES = ['Thị', 'Ngọc', 'Thu', 'Kim', 'Diệu', 'Bích'];

    private const MALE_FIRST_NAMES = ['An', 'Bình', 'Cường', 'Dũng', 'Đạt', 'Hải', 'Hùng', 'Khang', 'Long', 'Minh', 'Nam', 'Phong', 'Quân', 'Sơn', 'Thắng', 'Tuấn', 'Việt', 'Vinh', 'Đăng', 'Kiên'];

    private const FEMALE_FIRST_NAMES = ['An', 'Bình', 'Chi', 'Dung', 'Hà', 'Hoa', 'Hương', 'Lan', 'Linh', 'Mai', 'Ngọc', 'Nhung', 'Phương', 'Quỳnh', 'Thảo', 'Thư', 'Trang', 'Vân', 'Yến', 'Hạnh'];

    // Địa chỉ thật ở Hà Nội, cycle theo seed — đa dạng quận/phường thay vì
    // faker vi_VN sinh địa chỉ chung chung.
    private const HANOI_ADDRESSES = [
        'Số %d Phố Huế, Hai Bà Trưng, Hà Nội',
        'Số %d Nguyễn Trãi, Thanh Xuân, Hà Nội',
        'Số %d Xuân Thủy, Cầu Giấy, Hà Nội',
        'Số %d Trần Duy Hưng, Cầu Giấy, Hà Nội',
        'Số %d Kim Mã, Ba Đình, Hà Nội',
        'Số %d Nguyễn Chí Thanh, Đống Đa, Hà Nội',
        'Số %d Giải Phóng, Hoàng Mai, Hà Nội',
        'Số %d Lạc Long Quân, Tây Hồ, Hà Nội',
        'Số %d Hoàng Quốc Việt, Bắc Từ Liêm, Hà Nội',
        'Số %d Nguyễn Văn Cừ, Long Biên, Hà Nội',
        'Số %d Tôn Đức Thắng, Đống Đa, Hà Nội',
        'Số %d Lê Văn Lương, Thanh Xuân, Hà Nội',
    ];

    private const MEMBER_NOTES = [
        'Mục tiêu: giảm 5kg trong 3 tháng, ưu tiên cardio buổi sáng.',
        'Mục tiêu: tăng cơ, tập trung nhóm ngực và vai.',
        'Có tiền sử đau lưng nhẹ, cần khởi động kỹ trước khi tập nặng.',
        'Mới bắt đầu tập gym, cần hướng dẫn kỹ thuật cơ bản.',
        'Vận động viên nghiệp dư, muốn cải thiện sức bền.',
        'Ưu tiên lịch tập buổi tối sau giờ làm.',
        'Đang phục hồi chấn thương đầu gối, tránh bài tập chân nặng.',
        'Mục tiêu: tăng cân, xây dựng nền tảng sức mạnh.',
        'Thích tập theo nhóm hơn tập một mình.',
        'Chuẩn bị thi đấu thể hình nghiệp dư, cần chế độ tập nghiêm ngặt.',
        'Mục tiêu: duy trì vóc dáng, tập 3 buổi/tuần.',
        'Sức khỏe tốt, không có chấn thương, sẵn sàng tập cường độ cao.',
    ];

    private const WORKOUT_EXERCISES = [
        ['name' => 'Squat', 'sets' => 4, 'reps' => 10, 'weight' => 40],
        ['name' => 'Deadlift', 'sets' => 4, 'reps' => 8, 'weight' => 60],
        ['name' => 'Bench Press', 'sets' => 4, 'reps' => 10, 'weight' => 35],
        ['name' => 'Plank', 'sets' => 3, 'reps' => 1, 'weight' => 0],
        ['name' => 'Chạy bộ máy', 'sets' => 1, 'reps' => 1, 'weight' => 0],
        ['name' => 'Kéo xô (Lat Pulldown)', 'sets' => 3, 'reps' => 12, 'weight' => 30],
        ['name' => 'Đẩy vai (Shoulder Press)', 'sets' => 3, 'reps' => 10, 'weight' => 20],
        ['name' => 'Gập bụng', 'sets' => 3, 'reps' => 20, 'weight' => 0],
        ['name' => 'Lunges', 'sets' => 3, 'reps' => 12, 'weight' => 10],
        ['name' => 'Đạp xe đạp tập', 'sets' => 1, 'reps' => 1, 'weight' => 0],
    ];

    private const MEAL_ITEMS = [
        ['meal' => 'Bữa sáng', 'food' => 'Yến mạch + chuối + sữa tươi không đường', 'calories' => 350, 'protein' => 15, 'carbs' => 55, 'fat' => 8],
        ['meal' => 'Bữa sáng', 'food' => 'Trứng ốp la + bánh mì nguyên cám', 'calories' => 400, 'protein' => 20, 'carbs' => 40, 'fat' => 15],
        ['meal' => 'Bữa trưa', 'food' => 'Ức gà áp chảo + cơm gạo lứt + rau luộc', 'calories' => 550, 'protein' => 45, 'carbs' => 60, 'fat' => 10],
        ['meal' => 'Bữa trưa', 'food' => 'Cá hồi nướng + khoai lang + salad', 'calories' => 500, 'protein' => 40, 'carbs' => 45, 'fat' => 18],
        ['meal' => 'Bữa phụ', 'food' => 'Sữa chua không đường + hạt óc chó', 'calories' => 200, 'protein' => 10, 'carbs' => 15, 'fat' => 12],
        ['meal' => 'Bữa tối', 'food' => 'Ức gà luộc + rau xanh trộn dầu ô liu', 'calories' => 380, 'protein' => 38, 'carbs' => 20, 'fat' => 14],
        ['meal' => 'Bữa tối', 'food' => 'Đậu phụ sốt cà chua + cơm gạo lứt', 'calories' => 420, 'protein' => 22, 'carbs' => 55, 'fat' => 10],
    ];

    private const POST_TEMPLATES = [
        ['type' => Post::TYPE_ANNOUNCEMENT, 'pinned' => true, 'content' => 'Thông báo: Gym nghỉ Tết Dương lịch từ 01/01 đến 02/01, mở cửa lại bình thường từ 03/01. Chúc các hội viên năm mới nhiều sức khỏe!'],
        ['type' => Post::TYPE_CHALLENGE, 'pinned' => true, 'content' => 'Khởi động Challenge 30 ngày Plank cùng Gym! Đăng ký tại quầy lễ tân, hoàn thành đủ 30 ngày nhận ngay phần quà từ Gym. Ai tham gia cùng nào?'],
        ['type' => Post::TYPE_EVENT, 'pinned' => false, 'content' => 'Sự kiện: Buổi workshop dinh dưỡng thể hình miễn phí cho hội viên vào cuối tuần này, có PT trực tiếp tư vấn chế độ ăn phù hợp mục tiêu từng người.'],
        ['type' => Post::TYPE_POST, 'pinned' => false, 'content' => 'Mẹo nhỏ: Khởi động kỹ 5-10 phút trước khi tập nặng giúp giảm đáng kể nguy cơ chấn thương, đừng bỏ qua bước này nhé!'],
        ['type' => Post::TYPE_POST, 'pinned' => false, 'content' => 'Uống đủ nước trong buổi tập rất quan trọng — nên uống từng ngụm nhỏ đều đặn thay vì đợi khát mới uống.'],
        ['type' => Post::TYPE_POST, 'pinned' => false, 'content' => 'Ngủ đủ giấc là một phần của quá trình phục hồi cơ bắp, đừng chỉ tập trung vào buổi tập mà quên nghỉ ngơi hợp lý.'],
        ['type' => Post::TYPE_EVENT, 'pinned' => false, 'content' => 'Lớp Yoga buổi sáng cuối tuần đã mở đăng ký, số lượng có hạn, hội viên đặt lớp sớm để giữ chỗ nhé!'],
    ];

    private const COMMENT_TEMPLATES = [
        'Hay quá, cảm ơn Gym đã chia sẻ!',
        'Em sẽ tham gia ạ.',
        'Thông tin hữu ích, mình sẽ áp dụng.',
        'Cho em hỏi đăng ký ở đâu ạ?',
        'Team mình cùng tham gia luôn nào!',
        'Cảm ơn PT đã tư vấn nhiệt tình.',
        'Rất mong chờ sự kiện này.',
        'Ủng hộ Gym hết mình!',
    ];

    private const REVIEW_COMMENTS_GYM = [
        'Không gian tập rộng rãi, sạch sẽ, thiết bị đầy đủ. Rất hài lòng!',
        'Nhân viên nhiệt tình, hỗ trợ nhanh chóng khi cần. Sẽ giới thiệu bạn bè.',
        'Gym khá đông vào giờ cao điểm nhưng nhìn chung trải nghiệm tốt.',
        'Giá cả hợp lý so với chất lượng dịch vụ, đáng đồng tiền.',
        'Phòng thay đồ hơi nhỏ, mong Gym cải thiện thêm.',
        'Thiết bị hiện đại, thường xuyên được bảo trì, rất yên tâm khi tập.',
        'Lớp học nhóm sinh động, PT hướng dẫn tận tâm.',
        'Vị trí thuận tiện, dễ tìm chỗ để xe.',
    ];

    private const REVIEW_COMMENTS_TRAINER = [
        'PT hướng dẫn kỹ thuật rất chi tiết, dễ hiểu.',
        'Nhiệt tình, luôn động viên trong buổi tập.',
        'Lên giáo án tập phù hợp với thể trạng của mình.',
        'PT chuyên nghiệp, đúng giờ, theo sát tiến trình tập luyện.',
        'Kiến thức chuyên môn tốt, giải đáp mọi thắc mắc rõ ràng.',
        'Buổi tập hiệu quả, cảm nhận rõ sự tiến bộ sau vài tuần.',
    ];

    private const EQUIPMENT_CATALOG = [
        ['name' => 'Máy chạy bộ Life Fitness', 'category' => 'Cardio', 'interval' => 90, 'lastDaysAgo' => 100, 'status' => Equipment::STATUS_MAINTENANCE],
        ['name' => 'Máy đạp xe tập', 'category' => 'Cardio', 'interval' => 90, 'lastDaysAgo' => 85, 'status' => Equipment::STATUS_ACTIVE],
        ['name' => 'Giàn tạ đa năng (Smith Machine)', 'category' => 'Sức mạnh', 'interval' => 120, 'lastDaysAgo' => 30, 'status' => Equipment::STATUS_ACTIVE],
        ['name' => 'Ghế đẩy ngực', 'category' => 'Sức mạnh', 'interval' => 180, 'lastDaysAgo' => 170, 'status' => Equipment::STATUS_ACTIVE],
        ['name' => 'Máy kéo xô (Lat Pulldown)', 'category' => 'Sức mạnh', 'interval' => 120, 'lastDaysAgo' => 20, 'status' => Equipment::STATUS_ACTIVE],
        ['name' => 'Máy ép đùi', 'category' => 'Sức mạnh', 'interval' => 90, 'lastDaysAgo' => 10, 'status' => Equipment::STATUS_ACTIVE],
        ['name' => 'Thảm tập Yoga (bộ 10 cái)', 'category' => 'Dụng cụ nhỏ', 'interval' => null, 'lastDaysAgo' => null, 'status' => Equipment::STATUS_ACTIVE],
        ['name' => 'Dây kháng lực (bộ)', 'category' => 'Dụng cụ nhỏ', 'interval' => null, 'lastDaysAgo' => null, 'status' => Equipment::STATUS_ACTIVE],
        ['name' => 'Máy chèo thuyền (Rowing Machine)', 'category' => 'Cardio', 'interval' => 60, 'lastDaysAgo' => 65, 'status' => Equipment::STATUS_MAINTENANCE],
        ['name' => 'Xe đạp tập nhóm (Spin Bike)', 'category' => 'Cardio', 'interval' => 180, 'lastDaysAgo' => 60, 'status' => Equipment::STATUS_ACTIVE],
    ];

    private const GYMS = [
        [
            'name' => 'FitZone Hoàn Kiếm',
            'slug' => 'fitzone-hoan-kiem',
            'prefix' => 'fitzone',
            'code' => 'FZ',
            'address' => '12 Hàng Bài, Hoàn Kiếm, Hà Nội',
            'description' => 'FitZone Hoàn Kiếm là phòng gym trung tâm quận Hoàn Kiếm, sở hữu hệ thống máy tập nhập khẩu hiện đại, đội ngũ PT giàu kinh nghiệm và không gian tập luyện thoáng đãng ngay giữa lòng phố cổ Hà Nội. Phù hợp cho cả người mới bắt đầu lẫn dân tập lâu năm.',
        ],
        [
            'name' => 'PowerHouse Hà Nội',
            'slug' => 'powerhouse-hanoi',
            'prefix' => 'powerhouse',
            'code' => 'PH',
            'address' => '45 Trần Duy Hưng, Cầu Giấy, Hà Nội',
            'description' => 'PowerHouse Hà Nội chuyên về tập luyện sức mạnh (strength training) với khu vực tạ tự do rộng lớn, phù hợp cho các bạn theo đuổi thể hình và powerlifting. Có PT chuyên sâu về dinh dưỡng và phục hồi chấn thương.',
        ],
        [
            'name' => 'Elite Fitness',
            'slug' => 'elite-fitness',
            'prefix' => 'elite',
            'code' => 'EF',
            'address' => '88 Nguyễn Trãi, Thanh Xuân, Hà Nội',
            'description' => 'Elite Fitness mang phong cách phòng gym cao cấp, đa dạng lớp học nhóm (Yoga, Zumba, Boxing) cùng khu vực cardio nhìn ra thành phố. Cộng đồng hội viên năng động, thường xuyên tổ chức challenge và sự kiện.',
        ],
    ];

    /**
     * Sinh tên người Việt "Họ Đệm Tên" xoay vòng qua các mảng ở trên theo
     * $seed, xen kẽ nam/nữ để danh sách demo trông tự nhiên và đa dạng.
     */
    private function vietnameseName(int $seed): string
    {
        $isMale = $seed % 2 === 0;
        $lastName = self::LAST_NAMES[$seed % count(self::LAST_NAMES)];
        $middlePool = $isMale ? self::MALE_MIDDLE_NAMES : self::FEMALE_MIDDLE_NAMES;
        $middleName = $middlePool[intdiv($seed, 2) % count($middlePool)];
        $firstPool = $isMale ? self::MALE_FIRST_NAMES : self::FEMALE_FIRST_NAMES;
        $firstName = $firstPool[intdiv($seed, 5) % count($firstPool)];

        return "{$lastName} {$middleName} {$firstName}";
    }

    private function hanoiAddress(int $seed): string
    {
        $template = self::HANOI_ADDRESSES[$seed % count(self::HANOI_ADDRESSES)];

        return sprintf($template, ($seed * 7 % 180) + 1);
    }

    private function vietnamesePhone(): string
    {
        $prefix = fake()->randomElement(['09', '08']);

        return $prefix.fake()->numerify('########');
    }

    /**
     * Sinh 2 ảnh SVG tĩnh (logo vuông + cover ngang) cho Gym, lưu trong
     * public/images/gyms/ — KHÔNG hotlink dịch vụ ảnh ngoài (dễ chết link),
     * tự vẽ bằng SVG nên không bao giờ vỡ ảnh. Ghi "lười" (idempotent): nếu
     * file đã tồn tại từ lần seed trước thì không ghi lại.
     */
    private function gymAssets(string $slug, string $initials, string $color): array
    {
        $dir = public_path('images/gyms');
        File::ensureDirectoryExists($dir);

        $logoPath = "images/gyms/{$slug}-logo.svg";
        $coverPath = "images/gyms/{$slug}-cover.svg";

        $logoFile = public_path($logoPath);
        if (! File::exists($logoFile)) {
            File::put($logoFile, <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200">
                <rect width="200" height="200" rx="24" fill="{$color}" />
                <text x="100" y="118" font-family="Arial, sans-serif" font-size="72" font-weight="bold" fill="#ffffff" text-anchor="middle">{$initials}</text>
            </svg>
            SVG);
        }

        $coverFile = public_path($coverPath);
        if (! File::exists($coverFile)) {
            File::put($coverFile, <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" width="1200" height="300" viewBox="0 0 1200 300">
                <rect width="1200" height="300" fill="{$color}" />
                <rect width="1200" height="300" fill="#000000" opacity="0.15" />
                <text x="60" y="180" font-family="Arial, sans-serif" font-size="64" font-weight="bold" fill="#ffffff">{$initials}</text>
            </svg>
            SVG);
        }

        return [$logoPath, $coverPath];
    }

    /**
     * "Du hành thời gian": chạy $callback trong khi Carbon::now() trả về
     * $date, rồi LUÔN reset lại real time ở finally (kể cả khi $callback ném
     * lỗi) — tránh làm rò rỉ thời gian giả sang phần seed tiếp theo.
     */
    private function atDate(Carbon $date, \Closure $callback): mixed
    {
        Carbon::setTestNow($date);

        try {
            return $callback();
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * Seed the application's database.
     *
     * QUAN TRỌNG: seeder chạy qua CLI, không có auth()->user() nên trait
     * BelongsToGym KHÔNG tự gán được gym_id VÀ KHÔNG tự lọc theo Gym — mọi
     * bản ghi bên dưới đều truyền gym_id tường minh, không dựa vào auto-fill.
     */
    public function run(): void
    {
        $demoAccounts = [];
        $nameSeed = 0;
        $summary = [];

        $admin = User::factory()->create([
            'gym_id' => null,
            'role' => User::ROLE_PLATFORM_ADMIN,
            'name' => 'Platform Admin',
            'email' => 'admin@gymhub.test',
            'phone' => $this->vietnamesePhone(),
        ]);
        $demoAccounts[] = ['Platform Admin', $admin->email, 'password', 'platform_admin', '-'];

        $colors = ['#4f46e5', '#0ea5e9', '#16a34a'];

        foreach (self::GYMS as $index => $def) {
            [$rows, $counts] = $this->seedGym($def, $nameSeed, $colors[$index % count($colors)]);
            $demoAccounts = array_merge($demoAccounts, $rows);
            $summary[$def['name']] = $counts;
        }

        // Trigger "membership sắp hết hạn" (Notification::TYPE_MEMBERSHIP_EXPIRING)
        // cho các membership đang active và còn hạn ≤7 ngày vừa seed — dùng
        // đúng Service thật (idempotent trong ngày) thay vì tự tay tạo Notification.
        app(NotificationService::class)->notifyExpiringMemberships(7);

        $this->command->table(
            ['Vai trò', 'Email', 'Mật khẩu', 'Role', 'Gym'],
            $demoAccounts
        );

        foreach ($summary as $gymName => $counts) {
            $this->command->info("--- {$gymName} ---");
            $this->command->table(['Loại dữ liệu', 'Số lượng'], collect($counts)->map(fn ($v, $k) => [$k, $v])->values()->all());
        }

        $this->command->info('Seed dữ liệu demo hoàn tất.');
    }

    /**
     * Seed toàn bộ dữ liệu cho 1 Gym: hồ sơ người dùng, gói/khuyến mãi,
     * membership+payment+invoice (nhất quán nghiệp vụ), check-in+loyalty,
     * lịch tập+đặt lớp, chỉ số cơ thể, kế hoạch tập/dinh dưỡng, cộng đồng,
     * đánh giá, thiết bị+bảo trì. Trả về [danh sách tài khoản demo, số liệu đếm].
     */
    private function seedGym(array $def, int &$nameSeed, string $color): array
    {
        $demoAccounts = [];
        $initials = mb_strtoupper(mb_substr($def['code'], 0, 2));
        [$logoPath, $coverPath] = $this->gymAssets($def['slug'], $initials, $color);

        $gym = Gym::factory()->create([
            'name' => $def['name'],
            'slug' => $def['slug'],
            'code' => $def['code'],
            'address' => $def['address'],
            'phone' => '024'.fake()->numerify('#######'),
            'email' => "contact@{$def['prefix']}.test",
            'description' => $def['description'],
            'logo_path' => $logoPath,
            'cover_path' => $coverPath,
            'opening_time' => '06:00',
            'closing_time' => '22:00',
            'is_active' => true,
        ]);

        $owner = User::factory()->create([
            'gym_id' => $gym->id,
            'role' => User::ROLE_GYM_OWNER,
            'name' => 'Chủ '.$def['name'],
            'email' => "owner@{$def['prefix']}.test",
            'phone' => $this->vietnamesePhone(),
        ]);
        $demoAccounts[] = ["Chủ Gym - {$def['name']}", $owner->email, 'password', 'gym_owner', $def['name']];

        $staffUsers = [];
        for ($i = 1; $i <= self::STAFF_PER_GYM; $i++) {
            $staffUser = User::factory()->create([
                'gym_id' => $gym->id,
                'role' => User::ROLE_STAFF,
                'name' => $this->vietnameseName($nameSeed++),
                'phone' => $this->vietnamesePhone(),
                'email' => "staff{$i}@{$def['prefix']}.test",
            ]);

            Staff::factory()->create([
                'gym_id' => $gym->id,
                'user_id' => $staffUser->id,
                'position' => $i === 1 ? 'Quản lý ca' : 'Lễ tân',
            ]);

            $staffUsers[] = $staffUser;
            $demoAccounts[] = ["Staff {$i} - {$def['name']}", $staffUser->email, 'password', 'staff', $def['name']];
        }

        $trainers = [];
        $trainerSpecializations = ['Tăng cơ giảm mỡ', 'Yoga', 'Boxing', 'Phục hồi chấn thương', 'Cardio & sức bền'];
        for ($i = 1; $i <= self::TRAINERS_PER_GYM; $i++) {
            $trainerUser = User::factory()->create([
                'gym_id' => $gym->id,
                'role' => User::ROLE_TRAINER,
                'name' => $this->vietnameseName($nameSeed++),
                'phone' => $this->vietnamesePhone(),
                'email' => "trainer{$i}@{$def['prefix']}.test",
            ]);

            $trainers[] = Trainer::factory()->create([
                'gym_id' => $gym->id,
                'user_id' => $trainerUser->id,
                'specialization' => $trainerSpecializations[($i - 1) % count($trainerSpecializations)],
                'bio' => "Huấn luyện viên chuyên về {$trainerSpecializations[($i - 1) % count($trainerSpecializations)]}, đồng hành cùng hội viên xây dựng lộ trình tập luyện an toàn và hiệu quả.",
                'rating_avg' => round(fake()->randomFloat(2, 4.0, 5.0), 2),
            ]);

            $demoAccounts[] = ["Trainer {$i} - {$def['name']}", $trainerUser->email, 'password', 'trainer', $def['name']];
        }

        $packages = $this->seedPackages($gym);
        $promotions = $this->seedPromotions($gym, $def['code'], $packages);

        $confirmer = $owner;
        $membershipData = $this->seedMembersWithMemberships($gym, $def, $trainers, $confirmer, $packages, $promotions, $nameSeed, $demoAccounts);
        $members = $membershipData['members'];
        $eligibleForCheckin = $membershipData['eligible'];
        $membersWithTrainer = $membershipData['withTrainer'];

        $schedules = $this->seedSchedulesAndBookings($gym, $trainers, $eligibleForCheckin);

        $checkinCount = $this->seedAttendance($eligibleForCheckin, $owner);

        $this->seedBodyMeasurements($members, $owner);

        $planCounts = $this->seedPlans($membersWithTrainer);

        $allGymUsers = array_merge([$owner], $staffUsers, array_map(fn ($t) => $t->user, $trainers), array_map(fn ($m) => $m->user, $members));
        $communityCounts = $this->seedCommunity($gym, $owner, $staffUsers, $trainers, $members, $allGymUsers);

        $reviewCount = $this->seedReviews($gym, $members, $trainers);

        $equipmentCounts = $this->seedEquipment($gym, $owner);

        $counts = [
            'Users (owner+staff+trainer+member)' => 1 + count($staffUsers) + count($trainers) + count($members),
            'Members' => count($members),
            'Packages' => count($packages),
            'Promotions' => count($promotions),
            'Memberships' => Membership::where('gym_id', $gym->id)->count(),
            'Payments (paid)' => Payment::where('gym_id', $gym->id)->where('status', Payment::STATUS_PAID)->count(),
            'Payments (pending)' => Payment::where('gym_id', $gym->id)->where('status', Payment::STATUS_PENDING)->count(),
            'Invoices' => Invoice::where('gym_id', $gym->id)->count(),
            'Schedules' => count($schedules),
            'Class bookings' => ClassBooking::where('gym_id', $gym->id)->count(),
            'Attendances (check-in)' => $checkinCount,
            'Body measurements' => BodyMeasurement::where('gym_id', $gym->id)->count(),
            'Workout plans' => $planCounts['workout'],
            'Nutrition plans' => $planCounts['nutrition'],
            'Posts' => $communityCounts['posts'],
            'Comments' => $communityCounts['comments'],
            'Reactions' => $communityCounts['reactions'],
            'Reviews' => $reviewCount,
            'Equipment' => count(self::EQUIPMENT_CATALOG),
            'Maintenance records' => $equipmentCounts,
        ];

        return [$demoAccounts, $counts];
    }

    /** @return array<int,Package> keyed by duration_days */
    private function seedPackages(Gym $gym): array
    {
        $descriptions = [
            30 => 'Gói linh hoạt 1 tháng: tự do sử dụng toàn bộ khu vực tập và phòng thay đồ, kèm 2 buổi tập cùng PT để làm quen thiết bị. Phù hợp người muốn trải nghiệm trước khi cam kết dài hạn.',
            90 => 'Gói 3 tháng: quyền lợi như gói 1 tháng, cộng thêm 4 buổi PT 1-kèm-1 và đo chỉ số cơ thể miễn phí đầu/cuối gói. Phù hợp người mới bắt đầu xây nền tảng thể lực.',
            180 => 'Gói 6 tháng: đầy đủ quyền lợi gói 3 tháng, tăng lên 8 buổi PT, ưu tiên đặt lớp nhóm giờ cao điểm, tặng 1 buổi tư vấn dinh dưỡng. Lựa chọn tiết kiệm cho người tập nghiêm túc.',
            365 => 'Gói 12 tháng (tiết kiệm nhất): toàn bộ quyền lợi gói 6 tháng, tăng lên 16 buổi PT trong năm, ưu tiên đặt lớp nhóm mọi khung giờ, tặng áo tập độc quyền của Gym.',
        ];

        $pricePerMonth = fake()->numberBetween(450000, 850000);
        $packages = [];

        foreach (self::PACKAGE_DURATIONS_DAYS as $duration) {
            $packages[$duration] = Package::factory()->withDuration($duration)->create([
                'gym_id' => $gym->id,
                'description' => $descriptions[$duration],
                'price' => round($pricePerMonth * $duration / 30, -3),
                'pt_sessions' => self::PACKAGE_PT_SESSIONS[$duration],
                'is_active' => true,
            ]);
        }

        return $packages;
    }

    /** @return array{running:Promotion,upcoming:Promotion,expired:Promotion} */
    private function seedPromotions(Gym $gym, string $code, array $packages): array
    {
        $running = Promotion::factory()->create([
            'gym_id' => $gym->id,
            'code' => "{$code}SALE1",
            'name' => 'Ưu đãi mùa hè',
            'discount_type' => Promotion::DISCOUNT_TYPE_PERCENT,
            'discount_value' => 15,
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => now()->addDays(60)->toDateString(),
            'usage_limit' => 100,
            'used_count' => 0,
            'is_active' => true,
        ]);

        $upcoming = Promotion::factory()->create([
            'gym_id' => $gym->id,
            'code' => "{$code}SALE2",
            'name' => 'Khuyến mãi sắp diễn ra - Mừng khai trương cơ sở mới',
            'discount_type' => Promotion::DISCOUNT_TYPE_FIXED,
            'discount_value' => 200000,
            'start_date' => now()->addDays(15)->toDateString(),
            'end_date' => now()->addDays(45)->toDateString(),
            'usage_limit' => 50,
            'used_count' => 0,
            'is_active' => true,
        ]);

        $expired = Promotion::factory()->create([
            'gym_id' => $gym->id,
            'code' => "{$code}SALE3",
            'name' => 'Khuyến mãi khai trương (đã kết thúc)',
            'discount_type' => Promotion::DISCOUNT_TYPE_PERCENT,
            'discount_value' => 20,
            'start_date' => now()->subDays(90)->toDateString(),
            'end_date' => now()->subDays(30)->toDateString(),
            'usage_limit' => 30,
            'used_count' => 30,
            'is_active' => true,
        ]);

        $packages[90]->promotions()->attach($running->id);
        $packages[180]->promotions()->attach($running->id);
        $packages[365]->promotions()->attach($upcoming->id);
        $packages[30]->promotions()->attach($expired->id);

        return ['running' => $running, 'upcoming' => $upcoming, 'expired' => $expired];
    }

    /**
     * Tạo 15 member/Gym với hồ sơ đầy đủ + luồng membership/payment/invoice
     * NHẤT QUÁN theo 4 kịch bản (mỗi kịch bản dùng đúng MembershipService/
     * PaymentService thật, "du hành thời gian" để trải doanh thu qua nhiều
     * tháng):
     *   - pending: có payment pending, CHƯA confirm -> chưa có invoice.
     *   - expiring: active, còn hạn ≤7 ngày.
     *   - expired: active/paid lúc tạo nhưng đã hết hạn từ lâu -> cập nhật
     *     status=expired sau khi xác nhận (đúng dữ liệu lịch sử, không giả).
     *   - comfortable: active, còn hạn thoải mái, ngày mua trải đều 6 tháng
     *     gần nhất để biểu đồ doanh thu có nhiều cột.
     */
    private function seedMembersWithMemberships(
        Gym $gym,
        array $def,
        array $trainers,
        User $confirmer,
        array $packages,
        array $promotions,
        int &$nameSeed,
        array &$demoAccounts,
    ): array {
        $membershipService = app(MembershipService::class);
        $paymentService = app(PaymentService::class);

        $members = [];
        $eligible = [];
        $withTrainer = [];

        for ($i = 1; $i <= self::MEMBERS_PER_GYM; $i++) {
            $seed = $nameSeed++;
            $isMale = $seed % 2 === 0;

            [$scenario, $startDaysAgo, $duration] = match (true) {
                $i <= 2 => ['pending', rand(0, 4), fake()->randomElement(self::PACKAGE_DURATIONS_DAYS)],
                $i <= 4 => ['expiring', 30 - rand(1, 7), 30],
                $i <= 6 => ['expired', rand(10, 40) + 30, 30],
                default => ['comfortable', ...$this->comfortableStart($i)],
            };

            $purchaseDate = now()->subDays($startDaysAgo)->setTime(rand(8, 20), rand(0, 59));

            $memberUser = User::factory()->create([
                'gym_id' => $gym->id,
                'role' => User::ROLE_MEMBER,
                'name' => $this->vietnameseName($seed),
                'phone' => $this->vietnamesePhone(),
                'email' => "member{$i}@{$def['prefix']}.test",
            ]);

            $trainerId = $i <= 10 ? $trainers[($i - 1) % count($trainers)]->id : null;

            $member = Member::factory()->create([
                'gym_id' => $gym->id,
                'trainer_id' => $trainerId,
                'user_id' => $memberUser->id,
                'member_code' => sprintf('%s-%04d', $def['code'], $i),
                'date_of_birth' => now()->subYears(rand(18, 55))->subDays(rand(0, 364)),
                'gender' => $isMale ? Member::GENDER_MALE : Member::GENDER_FEMALE,
                'address' => $this->hanoiAddress($seed),
                'emergency_contact' => $this->vietnamesePhone(),
                'height' => $isMale ? fake()->randomFloat(2, 165, 190) : fake()->randomFloat(2, 150, 172),
                'weight' => $isMale ? fake()->randomFloat(2, 60, 95) : fake()->randomFloat(2, 45, 65),
                'status' => Member::STATUS_ACTIVE,
                'joined_at' => $purchaseDate->toDateString(),
                'notes' => self::MEMBER_NOTES[$seed % count(self::MEMBER_NOTES)],
            ]);

            $members[] = $member;
            if ($trainerId) {
                $withTrainer[] = $member;
            }

            if ($i <= 2) {
                $demoAccounts[] = ["Member {$i} - {$def['name']}", $memberUser->email, 'password', 'member', $def['name']];
            }

            $package = $packages[$duration];

            $this->atDate($purchaseDate, function () use ($membershipService, $paymentService, $member, $package, $promotions, $confirmer, $scenario, &$eligible) {
                $promo = null;
                if ($scenario === 'comfortable' && $promotions['running']->isValidNow() && rand(1, 100) <= 40) {
                    $promo = $promotions['running'];
                }

                $membership = $membershipService->create($member, $package, $promo);
                $payment = $paymentService->create($membership);

                if ($scenario !== 'pending') {
                    $paymentService->confirm($payment, $confirmer, 'Đã đối chiếu sao kê, xác nhận chuyển khoản qua VietQR.');

                    if (in_array($scenario, ['comfortable', 'expiring'], true)) {
                        $eligible[] = $member;
                    }
                }

                if ($scenario === 'expired') {
                    $membership->update(['status' => Membership::STATUS_EXPIRED]);
                }
            });
        }

        return ['members' => $members, 'eligible' => $eligible, 'withTrainer' => $withTrainer];
    }

    /** @return array{0:int,1:int} [startDaysAgo, duration] cho kịch bản "comfortable active", trải đều 6 tháng gần nhất. */
    private function comfortableStart(int $memberIndex): array
    {
        $offsets = [0, 1, 2, 3, 4, 5, 0, 1, 2];
        $offset = $offsets[($memberIndex - 7) % count($offsets)];
        $startDaysAgo = $offset * 30 + rand(3, 26);

        $safeDurations = collect([90, 180, 365])->filter(fn ($d) => ($d - $startDaysAgo) > 10)->values();
        $duration = $safeDurations->isNotEmpty() ? $safeDurations->random() : 365;

        return [$startDaysAgo, $duration];
    }

    /** @return array<int,Schedule> */
    private function seedSchedulesAndBookings(Gym $gym, array $trainers, array $eligibleMembers): array
    {
        $groupTitles = ['Yoga', 'Zumba', 'Gym cơ bản', 'Boxing cơ bản', 'HIIT', 'Cardio Blast'];
        $schedules = [];

        for ($i = 0; $i < self::GROUP_CLASSES_PER_GYM; $i++) {
            $schedules[] = Schedule::factory()->create([
                'gym_id' => $gym->id,
                'trainer_id' => fake()->randomElement($trainers)->id,
                'title' => $groupTitles[$i % count($groupTitles)],
            ]);
        }

        for ($i = 0; $i < self::PT_SESSIONS_PER_GYM; $i++) {
            $schedules[] = Schedule::factory()->ptSession()->create([
                'gym_id' => $gym->id,
                'trainer_id' => fake()->randomElement($trainers)->id,
            ]);
        }

        if (empty($eligibleMembers)) {
            return $schedules;
        }

        $bookingService = app(ClassBookingService::class);

        foreach ($schedules as $schedule) {
            $isPt = $schedule->is_pt_session;
            $candidates = collect($eligibleMembers)->shuffle();
            $slots = $isPt ? 1 : rand(3, 7);
            $booked = 0;

            foreach ($candidates as $candidate) {
                if ($booked >= $slots) {
                    break;
                }

                try {
                    $bookingService->book($candidate, $schedule);
                    $booked++;
                } catch (InvalidArgumentException $e) {
                    // Trùng lịch/hết chỗ/hết buổi PT — bỏ qua, thử member khác, dữ
                    // liệu demo không cần MỌI booking đều thành công.
                    continue;
                }
            }
        }

        return $schedules;
    }

    /**
     * Check-in lịch sử 30 ngày gần nhất cho các member đang có membership
     * active hợp lệ (comfortable + expiring) — dùng CHÍNH AttendanceService
     * nên tự động cộng đúng +10 điểm loyalty/lần check-in (mục 5).
     */
    private function seedAttendance(array $eligibleMembers, User $scannedBy): int
    {
        $attendanceService = app(AttendanceService::class);
        $total = 0;

        foreach ($eligibleMembers as $member) {
            $membership = Membership::where('member_id', $member->id)->where('status', Membership::STATUS_ACTIVE)->first();
            if (! $membership) {
                continue;
            }

            $maxDaysBack = min(29, now()->startOfDay()->diffInDays($membership->start_date));

            $days = collect(range(0, $maxDaysBack))
                ->filter(fn ($d) => $d === 0 ? rand(1, 100) <= 75 : rand(1, 100) <= 40)
                ->values();

            if ($days->isEmpty()) {
                $days = collect([0]);
            }

            foreach ($days as $d) {
                $day = now()->subDays($d)->setTime(rand(6, 21), rand(0, 59));

                $this->atDate($day, function () use ($attendanceService, $member, $scannedBy, &$total) {
                    try {
                        $token = $attendanceService->tokenFor($member);
                        $attendanceService->checkIn($token, $scannedBy);
                        $total++;
                    } catch (InvalidArgumentException $e) {
                        // Đã check-in ngày đó / trùng — bỏ qua, không phải lỗi thật.
                    }
                });
            }
        }

        return $total;
    }

    /**
     * 3-5 bản ghi chỉ số cơ thể cho phần lớn member, trải theo thời gian với
     * xu hướng cân nặng giảm dần/cơ tăng dần để biểu đồ tiến trình có đường
     * đi thật (không phải số random rời rạc).
     */
    private function seedBodyMeasurements(array $members, User $fallbackRecorder): void
    {
        $service = app(BodyMeasurementService::class);

        foreach ($members as $member) {
            if (rand(1, 100) > 80) {
                continue;
            }

            $recorder = $member->trainer?->user ?? $fallbackRecorder;
            $count = rand(3, 5);
            $startWeight = (float) $member->weight + rand(2, 6);
            $startBodyFat = fake()->randomFloat(2, 20, 28);
            $startMuscle = fake()->randomFloat(2, 30, 38);

            for ($n = 0; $n < $count; $n++) {
                $measuredAt = now()->subDays(($count - $n) * 18)->toDateString();
                $weight = round($startWeight - ($n * rand(1, 3) * 0.4), 2);
                $bodyFat = round($startBodyFat - ($n * 0.6), 2);
                $muscle = round($startMuscle + ($n * 0.4), 2);

                $service->record($member, $recorder, [
                    'measured_at' => $measuredAt,
                    'height' => $member->height,
                    'weight' => max($weight, 40),
                    'body_fat_percent' => max($bodyFat, 8),
                    'muscle_mass' => $muscle,
                    'notes' => $n === $count - 1 ? 'Số đo gần nhất, tiến triển tốt so với lần đo trước.' : null,
                ]);
            }
        }
    }

    /** @return array{workout:int,nutrition:int} */
    private function seedPlans(array $membersWithTrainer): array
    {
        $workoutCount = 0;
        $nutritionCount = 0;

        foreach ($membersWithTrainer as $member) {
            if (rand(1, 100) > 70) {
                continue;
            }

            $plan = WorkoutPlan::create([
                'gym_id' => $member->gym_id,
                'member_id' => $member->id,
                'trainer_id' => $member->trainer_id,
                'title' => 'Giáo án tập luyện cá nhân',
                'description' => 'Lộ trình tập luyện xây dựng bởi PT phụ trách, điều chỉnh theo tiến độ thực tế của hội viên.',
                'start_date' => now()->subDays(14)->toDateString(),
                'end_date' => now()->addDays(60)->toDateString(),
                'is_active' => true,
            ]);
            $workoutCount++;

            $exercises = collect(self::WORKOUT_EXERCISES)->shuffle()->take(4)->values();
            foreach ($exercises as $order => $exercise) {
                WorkoutPlanItem::create([
                    'workout_plan_id' => $plan->id,
                    'exercise_name' => $exercise['name'],
                    'sets' => $exercise['sets'],
                    'reps' => $exercise['reps'],
                    'weight' => $exercise['weight'],
                    'rest_seconds' => 60,
                    'day_of_week' => ['Thứ 2', 'Thứ 4', 'Thứ 6', 'Thứ 7'][$order % 4],
                    'sort_order' => $order,
                ]);
            }

            $nutritionPlan = NutritionPlan::create([
                'gym_id' => $member->gym_id,
                'member_id' => $member->id,
                'trainer_id' => $member->trainer_id,
                'title' => 'Kế hoạch dinh dưỡng đi kèm',
                'description' => 'Thực đơn tham khảo hỗ trợ mục tiêu tập luyện, hội viên có thể điều chỉnh theo khẩu vị.',
                'start_date' => now()->subDays(14)->toDateString(),
                'end_date' => now()->addDays(60)->toDateString(),
                'is_active' => true,
            ]);
            $nutritionCount++;

            $meals = collect(self::MEAL_ITEMS)->shuffle()->take(3)->values();
            foreach ($meals as $order => $meal) {
                NutritionPlanItem::create([
                    'nutrition_plan_id' => $nutritionPlan->id,
                    'meal_name' => $meal['meal'],
                    'meal_time' => ['07:00', '12:00', '19:00'][$order % 3],
                    'food' => $meal['food'],
                    'calories' => $meal['calories'],
                    'protein' => $meal['protein'],
                    'carbs' => $meal['carbs'],
                    'fat' => $meal['fat'],
                    'sort_order' => $order,
                ]);
            }
        }

        return ['workout' => $workoutCount, 'nutrition' => $nutritionCount];
    }

    /** @return array{posts:int,comments:int,reactions:int} */
    private function seedCommunity(Gym $gym, User $owner, array $staffUsers, array $trainers, array $members, array $allGymUsers): array
    {
        $notificationService = app(NotificationService::class);
        $authors = array_merge([$owner], $staffUsers, array_map(fn ($t) => $t->user, $trainers));
        $commenters = array_merge($authors, array_map(fn ($m) => $m->user, $members));

        $postCount = 0;
        $commentCount = 0;
        $reactionCount = 0;

        foreach (self::POST_TEMPLATES as $i => $template) {
            $author = $authors[$i % count($authors)];
            $publishedAt = now()->subDays(self::POSTS_PER_GYM - $i)->setTime(rand(8, 18), rand(0, 59));

            $post = Post::create([
                'gym_id' => $gym->id,
                'user_id' => $author->id,
                'content' => $template['content'],
                'type' => $template['type'],
                'is_pinned' => $template['pinned'],
                'published_at' => $publishedAt,
            ]);
            $postCount++;

            if ($template['type'] === Post::TYPE_ANNOUNCEMENT) {
                $notificationService->notifyGymUsers(
                    $gym,
                    Notification::TYPE_NEW_ANNOUNCEMENT,
                    'Thông báo mới từ Gym',
                    $post->content,
                    ['post_id' => $post->id],
                    exceptUserId: $author->id,
                );
            }

            $commentAuthors = collect($commenters)->shuffle()->take(rand(2, 4));
            foreach ($commentAuthors as $commentAuthor) {
                $comment = Comment::create([
                    'gym_id' => $gym->id,
                    'post_id' => $post->id,
                    'user_id' => $commentAuthor->id,
                    'content' => self::COMMENT_TEMPLATES[array_rand(self::COMMENT_TEMPLATES)],
                ]);
                $commentCount++;

                if ($post->user_id !== $commentAuthor->id) {
                    $notificationService->notify(
                        $author,
                        Notification::TYPE_NEW_COMMENT,
                        'Có bình luận mới',
                        "{$commentAuthor->name} đã bình luận vào bài viết của bạn: \"{$comment->content}\"",
                        ['post_id' => $post->id, 'comment_id' => $comment->id],
                    );
                }
            }

            $reactionUsers = collect($commenters)->shuffle()->take(rand(3, 8));
            foreach ($reactionUsers as $reactionUser) {
                Reaction::create([
                    'gym_id' => $gym->id,
                    'post_id' => $post->id,
                    'user_id' => $reactionUser->id,
                    'type' => fake()->randomElement(Reaction::TYPES),
                ]);
                $reactionCount++;
            }
        }

        return ['posts' => $postCount, 'comments' => $commentCount, 'reactions' => $reactionCount];
    }

    private function seedReviews(Gym $gym, array $members, array $trainers): int
    {
        $count = 0;
        $reviewers = collect($members)->shuffle();

        foreach ($reviewers->take(8) as $i => $member) {
            Review::create([
                'gym_id' => $gym->id,
                'member_id' => $member->id,
                'trainer_id' => null,
                'rating' => fake()->randomElement([5, 5, 4, 4, 4, 3]),
                'comment' => self::REVIEW_COMMENTS_GYM[$i % count(self::REVIEW_COMMENTS_GYM)],
                'is_visible' => $i !== 0,
            ]);
            $count++;
        }

        foreach ($trainers as $trainer) {
            $trainerReviewers = collect($members)->filter(fn ($m) => $m->trainer_id === $trainer->id)->shuffle()->take(3);

            foreach ($trainerReviewers as $j => $member) {
                Review::create([
                    'gym_id' => $gym->id,
                    'member_id' => $member->id,
                    'trainer_id' => $trainer->id,
                    'rating' => fake()->randomElement([5, 5, 4, 4, 3]),
                    'comment' => self::REVIEW_COMMENTS_TRAINER[$j % count(self::REVIEW_COMMENTS_TRAINER)],
                    'is_visible' => true,
                ]);
                $count++;
            }

            $avg = Review::where('gym_id', $gym->id)->where('trainer_id', $trainer->id)->avg('rating');
            if ($avg) {
                $trainer->update(['rating_avg' => round($avg, 2)]);
            }
        }

        return $count;
    }

    private function seedEquipment(Gym $gym, User $performedBy): int
    {
        $equipmentService = app(EquipmentService::class);
        $maintenanceCount = 0;

        foreach (self::EQUIPMENT_CATALOG as $item) {
            $equipment = Equipment::create([
                'gym_id' => $gym->id,
                'name' => $item['name'],
                'category' => $item['category'],
                'purchase_date' => now()->subMonths(rand(6, 24))->toDateString(),
                'status' => $item['status'],
                'maintenance_interval_days' => $item['interval'],
            ]);

            if ($item['lastDaysAgo'] !== null) {
                $equipmentService->recordMaintenance($equipment, $performedBy, [
                    'performed_at' => now()->subDays($item['lastDaysAgo'])->toDateString(),
                    'description' => 'Bảo trì định kỳ: vệ sinh, tra dầu, kiểm tra an toàn thiết bị.',
                    'cost' => fake()->numberBetween(200000, 1500000),
                ]);
                $maintenanceCount++;
            }
        }

        return $maintenanceCount;
    }
}
