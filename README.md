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

Tài khoản bị vô hiệu hóa (`is_active=false`) hoặc thuộc Gym đang tạm ngưng sẽ bị chặn đăng nhập với thông báo tiếng Việt. `/admin`, `/staff/dashboard`, `/home` hiện vẫn là placeholder — sẽ hoàn thiện ở Ngày 3. `/trainer/dashboard` đã là dashboard thật (Ngày 2, xem mục PT bên dưới). `/gym/*` đã có CRUD thật cho Hội viên, Gói tập, Khuyến mãi, Membership, Thanh toán, Hóa đơn, Lịch tập, Check-in (xem bên dưới).

## Authorization & Multi-Tenant Isolation

- Middleware `role:...` chặn sai role bằng **403** (không redirect login). Middleware `tenant` share `$currentGym` cho branding, không dùng để chặn quyền.
- Route được nhóm theo prefix: `/admin/*` (platform_admin), `/gym/*` (gym_owner, một số route mở thêm cho staff), `/staff/*`, `/trainer/*`, hội viên dùng `/home`.
- Mọi model nghiệp vụ có `gym_id` đều tự động lọc theo Gym của user đăng nhập (global scope `BelongsToGym`) — truy cập ID thuộc Gym khác qua route model binding trả **404**, không phải lỗi server.
- Policy (`MemberPolicy`, `PackagePolicy`, `PromotionPolicy`, `MembershipPolicy`, `PaymentPolicy`, `InvoicePolicy`, `SchedulePolicy`, `ClassBookingPolicy`, `AttendancePolicy`, `BodyMeasurementPolicy`, `WorkoutPlanPolicy`, `NutritionPlanPolicy`) kiểm tra cả role **và** gym_id ở tầng backend — không chỉ ẩn menu ở giao diện. Riêng nhóm "coaching" (body measurement, workout/nutrition plan) còn thêm 1 lớp lọc theo `trainer_id` (`MemberPolicy::coach()`): Trainer chỉ thao tác được trên hội viên **đã được phân công** cho chính mình, không phải mọi hội viên cùng Gym như Owner/Staff.
- Bằng chứng: `tests/Feature/RoleAccessTest.php`, `tests/Feature/TenantScopeTest.php`, `tests/Feature/MemberManagementTest.php`, `tests/Feature/MembershipCreationTest.php`, `tests/Feature/TrainerAssignmentTest.php`, `tests/Feature/CoreWorkflowEndToEndTest.php` (chạy trọn workflow thanh toán → check-in trong 1 test).

## Quản lý Gym (`/gym/*` — Owner + Staff)

| Module | Tính năng |
|---|---|
| Hội viên (`/gym/members`) | CRUD, search (tên/email/mã HV/SĐT), filter (trạng thái, khoảng ngày tham gia), sort, soft-delete ("Vô hiệu hóa") + Thùng rác + khôi phục. `member_code` sinh tự động theo Gym (vd `FZ-0001`), an toàn khi nhiều request tạo đồng thời (row lock). Gán/gỡ **PT phụ trách chính** cho từng hội viên ngay tại trang chi tiết. |
| Gói tập (`/gym/packages`) | CRUD, search theo tên, filter khoảng giá/thời hạn/trạng thái, sort theo giá, gán/gỡ Khuyến mãi cho từng gói. |
| Khuyến mãi (`/gym/promotions`) | CRUD, discount theo % hoặc số tiền cố định, kiểm tra còn hiệu lực (ngày + trạng thái + lượt dùng) trước khi cho áp dụng. |
| Membership (`/gym/memberships`) | Chọn Hội viên + Gói + Khuyến mãi (tuỳ chọn) → tự tính giá cuối cùng (bcmath) → tạo membership trạng thái **"chờ thanh toán"**. |
| Thanh toán (`/gym/payments`) | Tạo QR VietQR (ảnh động từ `img.vietqr.io`, nhúng sẵn số tiền + nội dung CK) cho 1 membership pending; xác nhận đã nhận tiền → **atomic**: Membership active, Invoice sinh ra, Notification gửi cho hội viên (tất cả trong 1 transaction, rollback toàn bộ nếu bất kỳ bước nào lỗi). |
| Hóa đơn (`/gym/invoices/{id}/download`) | Xuất PDF (DomPDF, font DejaVu Sans hỗ trợ tiếng Việt có dấu), sinh 1 lần rồi tái sử dụng, tải được bởi cả Staff/Owner và chính Member liên quan. |
| Lịch tập (`/gym/schedules`) | CRUD lớp nhóm + buổi PT 1-kèm-1 (`is_pt_session`), kiểm soát capacity không giảm dưới số đã đặt. |
| Check-in (`/gym/checkin`) | Nhập/quét token QR của hội viên để check-in — chặn hội viên bị khóa, membership hết hạn, check-in trùng ngày, token thuộc Gym khác; hợp lệ thì cộng +10 điểm loyalty. Xem mục [QR check-in](#qr-check-in--loyalty) bên dưới. |

## Đặt lớp + Thanh toán + Hóa đơn (Member, `/schedules`, `/payments`, `/invoices`)

Member xem lớp sắp diễn ra của Gym mình và đặt chỗ (chặn: chưa có membership active còn hạn, hết chỗ, trùng khung giờ, đã đặt lớp này rồi; buổi PT trừ đúng `remaining_pt_sessions`, huỷ thì hoàn lại). Xem thanh toán + tải hóa đơn PDF của chính mình.

## QR check-in + Loyalty

Member xem mã QR check-in của riêng mình tại `/qr` (`simplesoftwareio/simple-qrcode`). QR mã hóa một **token đã ký** (`base64(member_id . '|' . HMAC-SHA256(member_id, qr_secret))`), **không phải ID trần và không chứa SĐT/tên** — `qr_secret` là khóa ngẫu nhiên riêng từng member, không rời server; server verify bằng `hash_equals`. Check-in hợp lệ cộng tự động +10 điểm loyalty (`loyalty_point_transactions`); module Loyalty đầy đủ (đổi điểm lấy quà...) vẫn thuộc nhóm COULD, chưa build.

## PT / Trainer (`/trainer/*`, `/members/{member}/...`)

Trainer có dashboard thật (`/trainer/dashboard`): lịch dạy hôm nay, lớp sắp tới, danh sách học viên **được phân công**, số buổi đã dạy. Trainer chỉ xem/ghi được dữ liệu (chỉ số cơ thể, kế hoạch tập, kế hoạch dinh dưỡng) của hội viên đã được Owner/Staff gán cho chính mình — không phải mọi hội viên cùng Gym.

| Module | Tính năng |
|---|---|
| Chỉ số cơ thể (`/members/{member}/measurements`) | Member/Trainer/Staff/Owner nhập height/weight/body_fat_percent/muscle_mass, tự tính BMI, lưu lịch sử đầy đủ. |
| Kế hoạch tập (`/members/{member}/workout-plans`) | Owner/Staff/Trainer (đã phân công) tạo plan + thêm bài tập (exercise/sets/reps...); Member chỉ xem. |
| Kế hoạch dinh dưỡng (`/members/{member}/nutrition-plans`) | Tương tự kế hoạch tập, thêm bữa ăn (món/calo/protein/carb/fat...). |

## Tài liệu

Tài liệu phân tích, thiết kế (Use Case, Activity, Sequence Diagram, Database Schema, Data Dictionary, Test Cases) được đặt tại thư mục [`/docs`](./docs), viết bằng Mermaid.

## Tiến độ phát triển

Xem [`CHANGELOG.md`](./CHANGELOG.md) để theo dõi tiến độ theo từng ngày (Day 1 / Day 2 / Day 3).

## AI Tool Declaration

Dự án được phát triển với sự hỗ trợ của Claude (Anthropic) trong vai trò trợ lý lập trình — hỗ trợ phân tích yêu cầu, thiết kế kiến trúc, viết code, viết tài liệu và test. Toàn bộ quyết định kiến trúc và nghiệp vụ được người thực hiện đồ án xem xét và phê duyệt.
