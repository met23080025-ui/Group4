# Changelog

Tất cả các thay đổi đáng chú ý của dự án GymHub được ghi lại tại đây, theo từng ngày phát triển.

## Day 1 — 17/08/2026

### Foundation (Khối 1)
- Cấu hình `.env` cho MySQL (`gymhub`), timezone `Asia/Ho_Chi_Minh`, locale `vi`.
- Cập nhật `.env.example` đầy đủ biến, bao gồm cấu hình VietQR và Invoice prefix (giá trị trống/giả).
- Khởi tạo `README.md` và `CHANGELOG.md`.
- Xác nhận `.gitignore` chuẩn Laravel.
- Cài `simplesoftwareio/simple-qrcode` và `barryvdh/laravel-dompdf`.
- Tạo layout khung `resources/views/layouts/app.blade.php` (sidebar + topbar, responsive, Tailwind).

### Migrations Multi-Tenant (Khối 2)
- Viết 30 migration nghiệp vụ theo đúng thứ tự phụ thuộc (gyms → users → ... → audit_logs → salaries).
- Mọi bảng nghiệp vụ đều có `gym_id` (FK → `gyms`) trừ `package_promotions`, `workout_plan_items`, `nutrition_plan_items` (scope gián tiếp qua bảng cha).
- Soft delete cho: members, trainers, staff, packages, memberships, schedules, posts, equipment, salaries.
- Unique constraint chống trùng lặp: `gyms.slug`, `users.email`, `invoices.invoice_number`, `payments.transaction_code`, `members[gym_id,member_code]`, `promotions[gym_id,code]`, `attendances[gym_id,member_id,check_in_date]`, `class_bookings[schedule_id,member_id]`, `reactions[post_id,user_id]`, `salaries[staff_id,month]`.
- Bổ sung bảng `salaries` (bị cắt khỏi phạm vi Khối 2 ban đầu, thêm lại theo yêu cầu để nhất quán với ERD/Data Dictionary — tính năng "Quản lý lương" thuộc nhóm COULD, chưa build UI).
- `php artisan migrate:fresh` chạy sạch 2 lần liên tiếp trên MySQL `gymhub`, không lỗi FK.

### Models + Tenant Global Scope (Khối 3)
- Tạo 30 Eloquent model tương ứng 30 bảng, đầy đủ quan hệ hai chiều, `casts()`, và `#[Fillable]` attribute (theo convention có sẵn của Laravel 13 skeleton).
- Trait `App\Models\Concerns\BelongsToGym`: tự động lọc theo `gym_id` của user đăng nhập (global scope `gym`), bỏ qua lọc cho `platform_admin` và cho ngữ cảnh CLI/seeder (không có user đăng nhập), tự gán `gym_id` khi tạo mới nếu chưa có.
- **Quyết định kiến trúc quan trọng:** model `User` KHÔNG áp trait `BelongsToGym` dù có cột `gym_id`, để tránh đệ quy vô hạn khi Auth guard tự resolve lại `User` bên trong chính global scope. Việc phân tách người dùng theo Gym được thực hiện qua các model nghiệp vụ (`Member`/`Trainer`/`Staff`) — nơi thực sự diễn ra thao tác quản lý theo từng Gym.
- Gỡ bỏ trait `Notifiable` mặc định khỏi `User` vì dự án dùng model `Notification` tự định nghĩa (bảng `notifications` khác schema với bảng notification mặc định của Laravel), tránh xung đột tên bảng.
- Viết `tests/Feature/TenantScopeTest.php` — 5 test case, bao phủ: user Gym A chỉ thấy dữ liệu Gym A, user Gym B chỉ thấy dữ liệu Gym B, Platform Admin thấy tất cả, ngữ cảnh CLI/guest không bị lọc (seeder chạy được), và `Member::find()` trả `null` khi truy cập ID thuộc Gym khác (mô phỏng 404 ở route model binding). **Toàn bộ 5/5 test PASS.**
- Bật extension `pdo_sqlite` trong `C:\php84\php.ini` (cần cho PHPUnit chạy trên SQLite in-memory theo cấu hình mặc định của `phpunit.xml`).

### Seeders dữ liệu demo (Khối 4)
- Tạo 6 Factory: `GymFactory`, `MemberFactory`, `TrainerFactory`, `StaffFactory`, `PackageFactory`, `PromotionFactory`.
- Viết lại `DatabaseSeeder`: 1 Platform Admin + 3 Gym (FitZone Hoàn Kiếm, PowerHouse Hà Nội, Elite Fitness), mỗi Gym có 1 owner + 2 staff + 3 trainer + 15 member + 4 package (30/90/180/365 ngày) + 2 promotion. Mọi bản ghi gán `gym_id` tường minh (không dựa vào auto-fill của `BelongsToGym`, vì seeder chạy CLI không có `auth()->user()`).
- Gỡ trait `WithoutModelEvents` khỏi `DatabaseSeeder` mặc định (không cần thiết cho quy mô seed hiện tại, tránh vô tình chặn model events ở các seeder sau này).
- `php artisan migrate:fresh --seed` chạy sạch trên MySQL `gymhub`. Kiểm chứng bằng SQL: mỗi Gym đúng 15 member (45 tổng), không có bản ghi `gym_id NULL` ở bất kỳ bảng nghiệp vụ nào ngoài Platform Admin.
- Bảng tài khoản demo được in ra bằng `$this->command->table()` khi seed (email pattern `owner@{prefix}.test`, `staff{n}@{prefix}.test`, `trainer{n}@{prefix}.test`, `member{n}@{prefix}.test`; prefix: `fitzone` / `powerhouse` / `elite`; mật khẩu tất cả là `password`).

### Authentication (Khối 5)
- Cài `laravel/breeze` v2.4.2 (Blade stack) — **tương thích Laravel 13**, không cần viết auth thủ công. Breeze hạ `tailwindcss` xuống v3 (kèm `postcss`, `autoprefixer`, `@tailwindcss/forms`) và thêm `alpinejs` cho toàn bộ project (không chỉ trang auth) — chấp nhận thay đổi này thay vì tự viết lại theo Tailwind v4, vì đây là stack chuẩn/dễ giải thích khi viva và tránh phải tự bảo trì auth scaffolding.
- Viết lại `resources/views/layouts/app.blade.php` (Blade component `<x-app-layout>`) bằng layout sidebar+topbar GymHub gốc từ Khối 1, chuyển từ vanilla JS sang Alpine.js cho toggle sidebar/dropdown (nhất quán với các view Breeze khác). Xóa `layouts/navigation.blade.php` (không dùng) và `dashboard.blade.php` mặc định (route `/dashboard` giờ chỉ redirect).
- `lang/vi/auth.php`, `lang/vi/passwords.php`, `lang/vi.json`: dịch thông báo đăng nhập/quên mật khẩu và toàn bộ nhãn UI (`__('...')`) sang tiếng Việt. **Chưa** dịch `validation.php` (để nguyên phạm vi Khối 7 theo kế hoạch gốc) — lỗi validate (vd. "The email field is required") tạm thời vẫn tiếng Anh.
- `LoginRequest::authenticate()`: sau `Auth::attempt` thành công, chặn user `is_active=false` và user thuộc gym `is_active=false` (trừ `platform_admin`), cập nhật `last_login_at`. Thông báo lỗi tiếng Việt.
- `User::dashboardPath()`: map role → route (`/admin`, `/gym/dashboard`, `/staff/dashboard`, `/trainer/dashboard`, `/home`). `AuthenticatedSessionController` và route `/dashboard` (giữ lại làm điểm trung chuyển cho các controller Breeze khác như xác minh email) đều dùng hàm này.
- `RegisteredUserController` + view `auth/register.blade.php`: đăng ký công khai chỉ dành cho Member, bắt buộc chọn Gym đang active (dropdown), gán `role=member`. **Quyết định phạm vi:** đăng ký chỉ tạo `User`, CHƯA tạo hồ sơ `Member` (member_code, status...) — việc này nối vào workflow chọn gói/membership ở Khối 7-8 theo đúng mục 26, tránh làm trùng logic sinh `member_code`.
- Tạo 5 trang placeholder dashboard (`resources/views/placeholders/*`) cho 5 role, route bảo vệ bằng `auth`+`verified`. **Lưu ý:** CHƯA có role middleware (đó là phạm vi Khối 6) — hiện tại một role có thể gõ URL dashboard của role khác và vẫn xem được (chỉ là placeholder tĩnh, không rò rỉ dữ liệu thật).
- Bật `MustVerifyEmail` trên `User`. **Sự cố phát hiện qua test:** gỡ `Notifiable` khỏi `User` ở Khối 3 làm vỡ `sendPasswordResetNotification()`/`sendEmailVerificationNotification()` (cả hai đều gọi `notify()`, chỉ `Notifiable` mới cung cấp) — phát hiện qua 4 test đỏ, đã khôi phục `Notifiable` và giữ nguyên override `notifications()` trỏ về model `Notification` tùy chỉnh (không xung đột vì override method của class luôn thắng method của trait).
- Cập nhật `tests/Feature/Auth/AuthenticationTest.php` (+4 test case mới: chặn is_active, chặn gym inactive, platform_admin không cần gym, cập nhật last_login_at) và `RegistrationTest.php` (field `gym_id` bắt buộc) cho khớp hành vi mới.
- **Kiểm thử thật:** `php artisan test` → 35/35 PASS. Đăng nhập thật qua HTTP (curl) với `admin@gymhub.test` → `/admin`, `owner@fitzone.test` → `/gym/dashboard`, `member1@fitzone.test` → `/home`, cả 3 đều đúng; `last_login_at` được ghi nhận trong DB thật.

### Tenant middleware + Role middleware + Policies (Khối 6)
- `App\Http\Middleware\EnsureTenantAccess` (alias `tenant`): xác định Gym hiện tại từ `$user->gym_id`, share `$currentGym` ra mọi view để hiển thị branding sidebar. `platform_admin` (gym_id null) → `$currentGym = null`, không crash.
- `App\Http\Middleware\RoleMiddleware` (alias `role`): cú pháp `role:gym_owner,staff`. Sai role → `abort(403)` (không redirect login — user đã đăng nhập hợp lệ, chỉ thiếu quyền).
- `routes/web.php` tái cấu trúc theo prefix: `/admin/*` (`role:platform_admin`), `/gym/*` (`role:gym_owner` cho dashboard, `role:gym_owner,staff` cho `/gym/members*`), `/staff/*` (`role:staff`), `/trainer/*` (`role:trainer`), `/home` (`role:member`).
- `App\Http\Controllers\Gym\MemberController` (khung — `index`/`show` gọi `$this->authorize(...)`) + route `/gym/members`, `/gym/members/{member}` — đủ để chứng minh cơ chế tenant isolation qua route thật; CRUD đầy đủ vẫn ở Khối 7. Thêm `AuthorizesRequests` vào base `Controller`.
- 5 Policy khung (`MemberPolicy`, `PackagePolicy`, `MembershipPolicy`, `PaymentPolicy`, `SchedulePolicy`), dùng chung trait `App\Policies\Concerns\TenantPolicy` (platform_admin bypass qua `before()`, so sánh `$user->gym_id === $model->gym_id` trực tiếp — **không** query gom nhóm bảng `users` theo đúng lưu ý Khối 5). `PaymentPolicy::update()` cố tình loại trừ role `member` — hội viên không được tự xác nhận thanh toán của mình (mục 8). `MemberPolicy`/`MembershipPolicy`/`PaymentPolicy::view()` xử lý an toàn khi `$user->member` là `null` (tài khoản Member tự đăng ký chưa có hồ sơ — Khối 5), không giả định luôn tồn tại. Laravel tự động nhận diện cả 5 policy theo convention, không cần đăng ký thủ công (đã xác minh qua `Gate::getPolicyFor()`).
- `tests/Feature/RoleAccessTest.php` — 7 test, bao gồm đủ 3 case bắt buộc: (a) member gõ `/gym/members` → 403; (b) owner Gym A gõ URL member Gym B → 404 (route model binding nhờ global scope, không cần policy can thiệp); (c) owner Gym A vào đúng `/gym/dashboard` → 200. Thêm: staff vào được `/gym/members` nhưng không vào được `/gym/dashboard`; gym_owner bị 403 ở `/admin`; platform_admin `can()` true qua mọi policy; guest bị redirect `/login` (không phải 403). **7/7 PASS.**
- **Kiểm thử cross-tenant thật** (không chỉ PHPUnit): khởi động server thật, đăng nhập `owner@fitzone.test`, gọi `/gym/members/16` (id thật của member `PH-0001` thuộc PowerHouse trong DB) → **404**; gọi `/gym/members/1` (member `FZ-0001` thuộc chính FitZone) → **200**; `member1@fitzone.test` gọi `/gym/members` → **403**; `owner@fitzone.test` gọi `/admin` → **403**. `php artisan test` toàn bộ → **42/42 PASS**.
