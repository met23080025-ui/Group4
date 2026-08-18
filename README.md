# GymHub – Multi-Tenant Gym Management & Community Platform

Đồ án môn HSB2006 – Developing Business Applications.

## Mô tả

GymHub là một nền tảng SaaS đa tenant (multi-tenant) cho phép nhiều phòng Gym cùng sử dụng một hệ thống duy nhất để quản lý hội viên, gói tập, huấn luyện viên (PT), lịch tập, check-in, thanh toán VietQR, hóa đơn, doanh thu, và tương tác cộng đồng nội bộ theo từng Gym. Mỗi Gym là một tenant độc lập với dữ liệu được phân tách hoàn toàn (`gym_id`); người dùng chỉ có thể truy cập dữ liệu thuộc Gym của mình, được kiểm soát bởi tenant middleware, global scope và policy ở tầng backend.

## Tech stack

- **Backend:** Laravel 13 (PHP 8.4), MySQL
- **Frontend:** Blade, Tailwind CSS 3 (qua Vite), Alpine.js (dropdown/toggle) — không dùng Livewire/Inertia/Vue
- **Auth scaffolding:** laravel/breeze (Blade stack) — tùy biến lại cho multi-tenant (redirect theo role, chặn tài khoản/gym bị vô hiệu hóa, chọn Gym khi đăng ký)
- **PDF:** barryvdh/laravel-dompdf (hóa đơn)
- **QR Code:** simplesoftwareio/simple-qrcode (thanh toán VietQR, check-in hội viên)
- **Testing:** PHPUnit (Feature/Unit tests)

## Vai trò người dùng (Roles)

| Role | Mô tả |
|---|---|
| `platform_admin` | Quản trị toàn bộ nền tảng, quản lý các Gym/tenant |
| `gym_owner` | Chủ một Gym, quản lý toàn bộ hoạt động của Gym mình |
| `staff` | Nhân viên Gym, vận hành hàng ngày (hội viên, thanh toán, check-in) |
| `trainer` | PT, quản lý học viên được phân công và lịch dạy |
| `member` | Hội viên, sử dụng dịch vụ và tương tác cộng đồng của Gym mình |

## Cài đặt (Windows + XAMPP)

1. Clone repository và cài dependencies:
   ```powershell
   composer install
   npm install
   ```
2. Copy file môi trường và cấu hình:
   ```powershell
   Copy-Item .env.example .env
   php artisan key:generate
   ```
   Chỉnh `.env`: `DB_DATABASE=gymhub`, `DB_USERNAME=root`, `DB_PASSWORD=` (mặc định XAMPP).
3. Tạo database `gymhub` trong MySQL (phpMyAdmin hoặc CLI).
4. Chạy migrate + seed dữ liệu demo:
   ```powershell
   php artisan migrate:fresh --seed
   ```
5. Build frontend assets và chạy server:
   ```powershell
   npm run build
   php artisan serve
   ```
6. Truy cập `http://localhost:8000`.

## Tài khoản demo

Mật khẩu của **mọi** tài khoản demo: `password`.

| Vai trò | Email pattern | Ví dụ |
|---|---|---|
| Platform Admin | `admin@gymhub.test` | `admin@gymhub.test` |
| Chủ Gym | `owner@{prefix}.test` | `owner@fitzone.test` |
| Staff (2/gym) | `staff{n}@{prefix}.test` | `staff1@fitzone.test`, `staff2@fitzone.test` |
| Trainer (3/gym) | `trainer{n}@{prefix}.test` | `trainer1@fitzone.test` ... `trainer3@fitzone.test` |
| Member (15/gym) | `member{n}@{prefix}.test` | `member1@fitzone.test` ... `member15@fitzone.test` |

`prefix` theo từng Gym: `fitzone` (FitZone Hoàn Kiếm), `powerhouse` (PowerHouse Hà Nội), `elite` (Elite Fitness).

Mỗi Gym demo có sẵn: 1 owner, 2 staff, 3 trainer, 15 member, 4 package (1/3/6/12 tháng), 2 promotion. Chạy `php artisan migrate:fresh --seed` để tạo lại toàn bộ dữ liệu này bất kỳ lúc nào.

## Authentication

Đăng ký (chỉ dành cho Member, bắt buộc chọn Gym), đăng nhập, đăng xuất, quên mật khẩu, đặt lại mật khẩu, đổi mật khẩu, xác minh email (dùng `MAIL_MAILER=log` — kiểm tra link xác minh trong `storage/logs/laravel.log`).

Sau khi đăng nhập, hệ thống điều hướng theo role:

| Role | Điều hướng đến |
|---|---|
| `platform_admin` | `/admin` |
| `gym_owner` | `/gym/dashboard` |
| `staff` | `/staff/dashboard` |
| `trainer` | `/trainer/dashboard` |
| `member` | `/home` |

Tài khoản bị vô hiệu hóa (`is_active=false`) hoặc thuộc Gym đang tạm ngưng sẽ bị chặn đăng nhập với thông báo tiếng Việt. Các trang trên hiện là placeholder — dashboard đầy đủ theo role sẽ hoàn thiện ở các khối tiếp theo.

## Authorization & Multi-Tenant Isolation

- Middleware `role:...` chặn sai role bằng **403** (không redirect login). Middleware `tenant` share `$currentGym` cho branding, không dùng để chặn quyền.
- Route được nhóm theo prefix: `/admin/*` (platform_admin), `/gym/*` (gym_owner, một số route mở thêm cho staff), `/staff/*`, `/trainer/*`, hội viên dùng `/home`.
- Mọi model nghiệp vụ có `gym_id` đều tự động lọc theo Gym của user đăng nhập (global scope `BelongsToGym`) — truy cập ID thuộc Gym khác qua route model binding trả **404**, không phải lỗi server.
- Policy (`MemberPolicy`, `PackagePolicy`, `MembershipPolicy`, `PaymentPolicy`, `SchedulePolicy`) kiểm tra cả role **và** gym_id ở tầng backend — không chỉ ẩn menu ở giao diện.
- Bằng chứng: `tests/Feature/RoleAccessTest.php` và `tests/Feature/TenantScopeTest.php`.

## Tài liệu

Tài liệu phân tích, thiết kế (Use Case, Activity, Sequence Diagram, Database Schema, Data Dictionary, Test Cases) được đặt tại thư mục [`/docs`](./docs), viết bằng Mermaid.

## Tiến độ phát triển

Xem [`CHANGELOG.md`](./CHANGELOG.md) để theo dõi tiến độ theo từng ngày (Day 1 / Day 2 / Day 3).

## AI Tool Declaration

Dự án được phát triển với sự hỗ trợ của Claude (Anthropic) trong vai trò trợ lý lập trình — hỗ trợ phân tích yêu cầu, thiết kế kiến trúc, viết code, viết tài liệu và test. Toàn bộ quyết định kiến trúc và nghiệp vụ được người thực hiện đồ án xem xét và phê duyệt.
