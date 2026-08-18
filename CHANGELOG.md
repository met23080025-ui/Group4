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
