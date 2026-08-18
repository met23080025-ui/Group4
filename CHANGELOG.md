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
