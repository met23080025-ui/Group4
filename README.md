# GymHub – Multi-Tenant Gym Management & Community Platform

Đồ án môn HSB2006 – Developing Business Applications.

## Mô tả

GymHub là một nền tảng SaaS đa tenant (multi-tenant) cho phép nhiều phòng Gym cùng sử dụng một hệ thống duy nhất để quản lý hội viên, gói tập, huấn luyện viên (PT), lịch tập, check-in, thanh toán VietQR, hóa đơn, doanh thu, và tương tác cộng đồng nội bộ theo từng Gym. Mỗi Gym là một tenant độc lập với dữ liệu được phân tách hoàn toàn (`gym_id`); người dùng chỉ có thể truy cập dữ liệu thuộc Gym của mình, được kiểm soát bởi tenant middleware, global scope và policy ở tầng backend.

## Tech stack

- **Backend:** Laravel 13 (PHP 8.4), MySQL
- **Frontend:** Blade, Tailwind CSS 4 (qua Vite) — không dùng Livewire/Inertia/Vue
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

Xem bảng tài khoản demo đầy đủ tại cuối `DatabaseSeeder` (in ra khi chạy `--seed`) — sẽ được cập nhật vào README sau khi hoàn thành Khối 4 (Ngày 1).

## Tài liệu

Tài liệu phân tích, thiết kế (Use Case, Activity, Sequence Diagram, Database Schema, Data Dictionary, Test Cases) được đặt tại thư mục [`/docs`](./docs), viết bằng Mermaid.

## Tiến độ phát triển

Xem [`CHANGELOG.md`](./CHANGELOG.md) để theo dõi tiến độ theo từng ngày (Day 1 / Day 2 / Day 3).

## AI Tool Declaration

Dự án được phát triển với sự hỗ trợ của Claude (Anthropic) trong vai trò trợ lý lập trình — hỗ trợ phân tích yêu cầu, thiết kế kiến trúc, viết code, viết tài liệu và test. Toàn bộ quyết định kiến trúc và nghiệp vụ được người thực hiện đồ án xem xét và phê duyệt.
