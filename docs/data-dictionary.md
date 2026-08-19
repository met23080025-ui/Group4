# Data Dictionary

Lấy trực tiếp từ 37 file migration trong `database/migrations/` (nguồn sự thật duy nhất — không suy diễn từ model). Quy ước chung áp dụng cho MỌI bảng, không lặp lại ở từng bảng bên dưới:

- Mọi bảng đều có `id BIGINT UNSIGNED PK AUTO_INCREMENT` và cặp `created_at`/`updated_at` (`TIMESTAMP NULL`) — Laravel `$table->id()` + `$table->timestamps()`.
- `deleted_at TIMESTAMP NULL` chỉ xuất hiện ở bảng có soft-delete (đánh dấu **Soft delete: có** trong tiêu đề bảng).
- Cột `gym_id` (khi có) là `FK → gyms.id`, đa số `cascadeOnDelete()` trừ khi ghi chú khác.
- Xem `docs/database-schema.md` để có sơ đồ ERD trực quan; tài liệu này là bản liệt kê chi tiết từng cột.

---

## gyms

| Cột | Kiểu | Khóa | Null | Mô tả |
|---|---|---|---|---|
| id | bigint | PK | ✗ | |
| name | string | | ✗ | Tên Gym |
| slug | string | UK | ✗ | Định danh URL-friendly |
| code | string(10) | UK | ✓ | Mã ngắn (vd `FZ`), tiền tố sinh `member_code`/`transaction_code`/`invoice_number` (thêm ở Khối 7 Ngày 1) |
| address | string | | ✓ | |
| phone | string | | ✓ | |
| email | string | | ✓ | |
| description | text | | ✓ | |
| logo_path | string | | ✓ | |
| cover_path | string | | ✓ | |
| opening_time | time | | ✓ | |
| closing_time | time | | ✓ | |
| is_active | boolean | | ✗ | default `true`. Platform Admin bật/tắt (Khối 3 Ngày 3) |

*Soft delete: không.* Đây là bảng gốc tenant — mọi bảng khác trỏ `gym_id` về đây.

## users

| Cột | Kiểu | Khóa | Null | Mô tả |
|---|---|---|---|---|
| id | bigint | PK | ✗ | |
| gym_id | bigint | FK, `nullOnDelete` | ✓ | `null` cho `platform_admin` (không thuộc Gym nào) |
| name | string | | ✗ | |
| email | string | UK | ✗ | |
| email_verified_at | timestamp | | ✓ | |
| password | string | | ✗ | hash bcrypt (cast `'hashed'`) |
| role | string | | ✗ | default `member`; 1 trong `platform_admin\|gym_owner\|staff\|trainer\|member` |
| phone | string | | ✓ | |
| avatar_path | string | | ✓ | |
| is_active | boolean | | ✗ | default `true` |
| last_login_at | timestamp | | ✓ | |
| remember_token | string | | ✓ | |

*Soft delete: không.* **Quyết định kiến trúc:** `User` KHÔNG dùng trait `BelongsToGym` (tránh đệ quy vô hạn khi Auth guard tự resolve `User` bên trong chính global scope) — hệ quả: Platform Admin dashboard (`Gym::count()`, `User::count()`...) tự thấy toàn bộ dữ liệu mà không cần code bypass riêng.

## members

| Cột | Kiểu | Khóa | Null | Mô tả |
|---|---|---|---|---|
| id | bigint | PK | ✗ | |
| gym_id | bigint | FK | ✗ | |
| user_id | bigint | FK, UK | ✗ | 1 user : 1 hồ sơ member |
| trainer_id | bigint | FK, `nullOnDelete` | ✓ | 🆕 **Ngày 2 – Khối 6.** PT phụ trách chính |
| member_code | string | UK theo `gym_id` | ✗ | vd `FZ-0001`, sinh tuần tự (row lock) |
| date_of_birth | date | | ✓ | |
| gender | string | | ✓ | `male\|female\|other` |
| address | string | | ✓ | |
| emergency_contact | string | | ✓ | |
| height | decimal(5,2) | | ✓ | cm |
| weight | decimal(5,2) | | ✓ | kg |
| status | string | | ✗ | default `active`; `active\|expired\|blocked` |
| joined_at | date | | ✓ | |
| notes | text | | ✓ | |
| qr_secret | text | | ✓ | 🆕 **Ngày 2 – Khối 5.** Khóa HMAC ký token QR check-in, cast `encrypted` (mã hoá tại rest bằng `APP_KEY`), sinh lười lần đầu xem QR |

*Soft delete: có* (`destroy` = "Vô hiệu hóa" + Thùng rác/khôi phục).

## trainers

| Cột | Kiểu | Khóa | Null | Mô tả |
|---|---|---|---|---|
| id | bigint | PK | ✗ | |
| gym_id | bigint | FK | ✗ | |
| user_id | bigint | FK, UK | ✗ | |
| specialization | string | | ✓ | |
| bio | text | | ✓ | |
| rating_avg | decimal(3,2) | | ✗ | default `0` (chưa có job tự tính lại từ `reviews`) |
| is_active | boolean | | ✗ | default `true` |

*Soft delete: có.*

## staff

| Cột | Kiểu | Khóa | Null | Mô tả |
|---|---|---|---|---|
| id | bigint | PK | ✗ | |
| gym_id | bigint | FK | ✗ | |
| user_id | bigint | FK, UK | ✗ | |
| position | string | | ✓ | |
| is_active | boolean | | ✗ | default `true` |

*Soft delete: có.*

## packages

| Cột | Kiểu | Khóa | Null | Mô tả |
|---|---|---|---|---|
| id | bigint | PK | ✗ | |
| gym_id | bigint | FK | ✗ | |
| name | string | | ✗ | |
| description | text | | ✓ | |
| price | decimal(12,2) | | ✗ | |
| duration_days | int unsigned | | ✗ | |
| pt_sessions | int unsigned | | ✗ | default `0` — số buổi PT kèm theo gói |
| is_active | boolean | | ✗ | default `true` |

*Soft delete: có.*

## promotions

| Cột | Kiểu | Khóa | Null | Mô tả |
|---|---|---|---|---|
| id | bigint | PK | ✗ | |
| gym_id | bigint | FK | ✗ | |
| code | string | UK theo `gym_id` | ✗ | |
| name | string | | ✗ | |
| discount_type | string | | ✗ | default `percent`; `percent\|fixed` |
| discount_value | decimal(12,2) | | ✗ | |
| start_date | date | | ✗ | |
| end_date | date | | ✗ | |
| usage_limit | int unsigned | | ✓ | |
| used_count | int unsigned | | ✗ | default `0`, tăng qua `increment()` khi áp dụng |
| is_active | boolean | | ✗ | default `true` |

*Soft delete: không.*

## package_promotions (pivot)

| Cột | Kiểu | Khóa | Null | Mô tả |
|---|---|---|---|---|
| id | bigint | PK | ✗ | |
| package_id | bigint | FK | ✗ | |
| promotion_id | bigint | FK | ✗ | |

Unique `[package_id, promotion_id]`. *Soft delete: không.* Không có `gym_id` riêng — scope gián tiếp qua `packages`/`promotions` (cả hai đều đã scope theo gym).

## memberships

| Cột | Kiểu | Khóa | Null | Mô tả |
|---|---|---|---|---|
| id | bigint | PK | ✗ | |
| gym_id | bigint | FK | ✗ | |
| member_id | bigint | FK | ✗ | |
| package_id | bigint | FK, `restrictOnDelete` | ✗ | |
| promotion_id | bigint | FK, `nullOnDelete` | ✓ | |
| start_date | date | | ✗ | |
| end_date | date | | ✗ | `start_date + package.duration_days` |
| original_price | decimal(12,2) | | ✗ | = `package.price` lúc tạo |
| discount_amount | decimal(12,2) | | ✗ | default `0`, tính bằng bcmath |
| final_price | decimal(12,2) | | ✗ | `original_price - discount_amount`, không bao giờ âm |
| remaining_pt_sessions | int unsigned | | ✗ | default `0` |
| status | string | | ✗ | default `pending`; `pending\|active\|expired\|cancelled` |

*Soft delete: có.*

## payments

| Cột | Kiểu | Khóa | Null | Mô tả |
|---|---|---|---|---|
| id | bigint | PK | ✗ | |
| gym_id | bigint | FK | ✗ | |
| membership_id | bigint | FK | ✗ | |
| member_id | bigint | FK | ✗ | |
| transaction_code | string | UK | ✗ | `PAY-{gymCode}-{yyyymmdd}-{seq}` |
| amount | decimal(12,2) | | ✗ | |
| method | string | | ✗ | `bank_transfer\|cash` |
| status | string | | ✗ | default `pending`; `pending\|paid\|failed\|cancelled` |
| qr_payload | string | | ✓ | URL ảnh VietQR (`img.vietqr.io`) |
| paid_at | timestamp | | ✓ | |
| confirmed_by | bigint | FK → `users.id`, `nullOnDelete` | ✓ | Staff/Owner đã xác nhận |
| note | string | | ✓ | |

*Soft delete: không.*

## invoices

| Cột | Kiểu | Khóa | Null | Mô tả |
|---|---|---|---|---|
| id | bigint | PK | ✗ | |
| gym_id | bigint | FK | ✗ | |
| payment_id | bigint | FK | ✗ | 1-1 với payment |
| member_id | bigint | FK | ✗ | |
| invoice_number | string | UK | ✗ | `INV-{gymCode}-{yyyymmdd}-{seq}` |
| issued_at | timestamp | | ✗ | |
| subtotal | decimal(12,2) | | ✗ | = `membership.original_price` |
| discount | decimal(12,2) | | ✗ | = `membership.discount_amount` |
| total | decimal(12,2) | | ✗ | = `membership.final_price` |
| pdf_path | string | | ✓ | đường dẫn trên disk `local`, sinh lười lần tải đầu (DomPDF) |

*Soft delete: không.*

## schedules

| Cột | Kiểu | Khóa | Null | Mô tả |
|---|---|---|---|---|
| id | bigint | PK | ✗ | |
| gym_id | bigint | FK | ✗ | |
| trainer_id | bigint | FK, `nullOnDelete` | ✓ | |
| title | string | | ✗ | |
| description | text | | ✓ | |
| class_date | date | | ✗ | |
| start_time | time | | ✗ | |
| end_time | time | | ✗ | |
| capacity | int unsigned | | ✗ | |
| is_pt_session | boolean | | ✗ | 🆕 **Ngày 2 – Khối 4.** default `false` — buổi PT 1-kèm-1 (thường capacity=1) khác lớp nhóm |
| status | string | | ✗ | default `scheduled`; `scheduled\|cancelled\|completed` (không có job tự chuyển `completed`) |

*Soft delete: có.*

## class_bookings

| Cột | Kiểu | Khóa | Null | Mô tả |
|---|---|---|---|---|
| id | bigint | PK | ✗ | |
| gym_id | bigint | FK | ✗ | |
| schedule_id | bigint | FK | ✗ | |
| member_id | bigint | FK | ✗ | |
| membership_id | bigint | FK, `nullOnDelete` | ✓ | 🆕 **Ngày 2 – Khối 4.** Ghi lại đúng membership đã dùng lúc đặt (để hoàn `remaining_pt_sessions` đúng khi huỷ dù member đã đổi gói) |
| status | string | | ✗ | default `booked`; `booked\|cancelled` |
| booked_at | timestamp | | ✓ | |
| cancelled_at | timestamp | | ✓ | |

Unique `[schedule_id, member_id]`. *Soft delete: không.*

## attendances

| Cột | Kiểu | Khóa | Null | Mô tả |
|---|---|---|---|---|
| id | bigint | PK | ✗ | |
| gym_id | bigint | FK | ✗ | |
| member_id | bigint | FK | ✗ | |
| trainer_id | bigint | FK, `nullOnDelete` | ✓ | |
| class_booking_id | bigint | FK, `nullOnDelete` | ✓ | |
| check_in_date | date | | ✗ | |
| check_in_time | timestamp | | ✗ | |
| source | string | | ✗ | default `qr`; `qr\|manual` |

Unique `[gym_id, member_id, check_in_date]` — chặn check-in trùng ngày ở tầng DB. *Soft delete: không.*

## body_measurements

| Cột | Kiểu | Khóa | Null | Mô tả |
|---|---|---|---|---|
| id | bigint | PK | ✗ | |
| gym_id | bigint | FK | ✗ | |
| member_id | bigint | FK | ✗ | |
| recorded_by | bigint | FK → `users.id`, `nullOnDelete` | ✓ | |
| measured_at | date | | ✗ | |
| height | decimal(5,2) | | ✓ | cm |
| weight | decimal(5,2) | | ✓ | kg |
| body_fat_percent | decimal(5,2) | | ✓ | |
| muscle_mass | decimal(5,2) | | ✓ | |
| bmi | decimal(5,2) | | ✓ | tính sẵn = weight / (height/100)² |
| notes | text | | ✓ | |

*Soft delete: không.*

## workout_plans

| Cột | Kiểu | Khóa | Null | Mô tả |
|---|---|---|---|---|
| id | bigint | PK | ✗ | |
| gym_id | bigint | FK | ✗ | |
| member_id | bigint | FK | ✗ | |
| trainer_id | bigint | FK, `nullOnDelete` | ✓ | |
| title | string | | ✗ | |
| description | text | | ✓ | |
| start_date | date | | ✓ | |
| end_date | date | | ✓ | |
| is_active | boolean | | ✗ | default `true` |

*Soft delete: không.*

## workout_plan_items

| Cột | Kiểu | Khóa | Null | Mô tả |
|---|---|---|---|---|
| id | bigint | PK | ✗ | |
| workout_plan_id | bigint | FK | ✗ | |
| exercise_name | string | | ✗ | |
| sets | int unsigned | | ✓ | |
| reps | int unsigned | | ✓ | |
| weight | decimal(6,2) | | ✓ | |
| rest_seconds | int unsigned | | ✓ | |
| day_of_week | string | | ✓ | |
| notes | text | | ✓ | |
| sort_order | int unsigned | | ✗ | default `0` |

Không có `gym_id` — scope gián tiếp qua `workout_plan_id` (đã scope). *Soft delete: không.*

## nutrition_plans

| Cột | Kiểu | Khóa | Null | Mô tả |
|---|---|---|---|---|
| id | bigint | PK | ✗ | |
| gym_id | bigint | FK | ✗ | |
| member_id | bigint | FK | ✗ | |
| trainer_id | bigint | FK, `nullOnDelete` | ✓ | |
| title | string | | ✗ | |
| description | text | | ✓ | |
| start_date | date | | ✓ | |
| end_date | date | | ✓ | |
| is_active | boolean | | ✗ | default `true` |

*Soft delete: không.*

## nutrition_plan_items

| Cột | Kiểu | Khóa | Null | Mô tả |
|---|---|---|---|---|
| id | bigint | PK | ✗ | |
| nutrition_plan_id | bigint | FK | ✗ | |
| meal_name | string | | ✗ | |
| meal_time | time | | ✓ | |
| food | string | | ✗ | |
| calories | decimal(8,2) | | ✓ | |
| protein | decimal(8,2) | | ✓ | |
| carbs | decimal(8,2) | | ✓ | |
| fat | decimal(8,2) | | ✓ | |
| notes | text | | ✓ | |
| sort_order | int unsigned | | ✗ | default `0` |

Không có `gym_id` — scope gián tiếp qua `nutrition_plan_id`. *Soft delete: không.*

## posts

| Cột | Kiểu | Khóa | Null | Mô tả |
|---|---|---|---|---|
| id | bigint | PK | ✗ | |
| gym_id | bigint | FK | ✗ | |
| user_id | bigint | FK | ✗ | tác giả |
| content | text | | ✗ | |
| image_path | string | | ✓ | |
| type | string | | ✗ | default `post`; `post\|announcement\|event\|challenge` |
| is_pinned | boolean | | ✗ | default `false` — Owner/Staff ghim lên đầu feed |
| published_at | timestamp | | ✓ | |

*Soft delete: có.*

## comments

| Cột | Kiểu | Khóa | Null | Mô tả |
|---|---|---|---|---|
| id | bigint | PK | ✗ | |
| gym_id | bigint | FK | ✗ | |
| post_id | bigint | FK | ✗ | |
| user_id | bigint | FK | ✗ | |
| content | text | | ✗ | |

*Soft delete: không.*

## reactions

| Cột | Kiểu | Khóa | Null | Mô tả |
|---|---|---|---|---|
| id | bigint | PK | ✗ | |
| gym_id | bigint | FK | ✗ | |
| post_id | bigint | FK | ✗ | |
| user_id | bigint | FK | ✗ | |
| type | string | | ✗ | default `like`; `like\|love\|wow` |

Unique `[post_id, user_id]` — 1 user chỉ 1 reaction/post (đổi loại = update, bấm lại = xoá). *Soft delete: không.*

## reviews

| Cột | Kiểu | Khóa | Null | Mô tả |
|---|---|---|---|---|
| id | bigint | PK | ✗ | |
| gym_id | bigint | FK | ✗ | |
| member_id | bigint | FK | ✗ | |
| trainer_id | bigint | FK, `cascadeOnDelete` | ✓ | `null` = review Gym chung; có giá trị = review 1 Trainer |
| rating | tinyint unsigned | | ✗ | 1–5 (validate ở Form Request, DB không ràng buộc CHECK) |
| comment | text | | ✓ | |
| is_visible | boolean | | ✗ | default `true` — Owner/Staff ẩn review không phù hợp |

*Soft delete: không.*

## notifications

| Cột | Kiểu | Khóa | Null | Mô tả |
|---|---|---|---|---|
| id | bigint | PK | ✗ | |
| gym_id | bigint | FK | ✗ | |
| user_id | bigint | FK | ✗ | người nhận |
| type | string | | ✗ | `payment_confirmed\|class_booked\|class_cancelled\|new_comment\|new_announcement\|membership_expiring\|...` |
| title | string | | ✗ | |
| body | text | | ✓ | |
| data | json | | ✓ | payload liên quan (vd `{payment_id, membership_id}`) |
| read_at | timestamp | | ✓ | `null` = chưa đọc |

*Soft delete: không.*

## loyalty_point_transactions

| Cột | Kiểu | Khóa | Null | Mô tả |
|---|---|---|---|---|
| id | bigint | PK | ✗ | |
| gym_id | bigint | FK | ✗ | |
| member_id | bigint | FK | ✗ | |
| points | int | | ✗ | có thể âm (trừ điểm, chưa dùng tới) |
| reason | string | | ✗ | `check_in\|review\|challenge_completion\|reward_redemption` |
| reference_type | string | | ✓ | polymorphic — class model liên quan (vd `App\Models\Attendance`) |
| reference_id | bigint unsigned | | ✓ | id của bản ghi liên quan |
| balance_after | int | | ✗ | số dư điểm SAU giao dịch này (event-sourced, không có cột "điểm hiện tại" riêng trên `members`) |

*Soft delete: không.*

## rewards *(khung COULD — chưa có Controller/Service)*

| Cột | Kiểu | Khóa | Null | Mô tả |
|---|---|---|---|---|
| id | bigint | PK | ✗ | |
| gym_id | bigint | FK | ✗ | |
| name | string | | ✗ | |
| description | text | | ✓ | |
| points_required | int unsigned | | ✗ | |
| stock | int unsigned | | ✓ | |
| is_active | boolean | | ✗ | default `true` |

*Soft delete: không.*

## reward_redemptions *(khung COULD — chưa có Controller/Service)*

| Cột | Kiểu | Khóa | Null | Mô tả |
|---|---|---|---|---|
| id | bigint | PK | ✗ | |
| gym_id | bigint | FK | ✗ | |
| member_id | bigint | FK | ✗ | |
| reward_id | bigint | FK, `restrictOnDelete` | ✗ | |
| points_spent | int unsigned | | ✗ | |
| status | string | | ✗ | default `pending` |
| redeemed_at | timestamp | | ✓ | |

*Soft delete: không.*

## equipment

| Cột | Kiểu | Khóa | Null | Mô tả |
|---|---|---|---|---|
| id | bigint | PK | ✗ | |
| gym_id | bigint | FK | ✗ | |
| name | string | | ✗ | |
| category | string | | ✓ | |
| purchase_date | date | | ✓ | |
| status | string | | ✗ | default `active`; `active\|maintenance\|retired` |
| maintenance_interval_days | int unsigned | | ✓ | chu kỳ bảo trì |
| last_maintenance_at | date | | ✓ | |
| next_maintenance_at | date | | ✓ | tính = `last_maintenance_at + maintenance_interval_days`, cập nhật cùng lúc tạo `maintenance_records` |

*Soft delete: có.*

## maintenance_records

| Cột | Kiểu | Khóa | Null | Mô tả |
|---|---|---|---|---|
| id | bigint | PK | ✗ | |
| gym_id | bigint | FK | ✗ | |
| equipment_id | bigint | FK | ✗ | |
| performed_by | bigint | FK → `users.id`, `nullOnDelete` | ✓ | |
| performed_at | date | | ✗ | |
| description | text | | ✓ | |
| cost | decimal(12,2) | | ✓ | |

*Soft delete: không.*

## audit_logs *(khung COULD — chưa có middleware/observer nào ghi)*

| Cột | Kiểu | Khóa | Null | Mô tả |
|---|---|---|---|---|
| id | bigint | PK | ✗ | |
| gym_id | bigint | FK, `nullOnDelete` | ✓ | |
| user_id | bigint | FK, `nullOnDelete` | ✓ | |
| action | string | | ✗ | |
| auditable_type | string | | ✓ | polymorphic |
| auditable_id | bigint unsigned | | ✓ | |
| old_values | json | | ✓ | |
| new_values | json | | ✓ | |
| ip_address | string(45) | | ✓ | |

*Soft delete: không.*

## salaries *(khung COULD — chưa có UI, xem CHANGELOG Ngày 1)*

| Cột | Kiểu | Khóa | Null | Mô tả |
|---|---|---|---|---|
| id | bigint | PK | ✗ | |
| gym_id | bigint | FK | ✗ | |
| staff_id | bigint | FK | ✗ | |
| month | date | UK theo `staff_id` | ✗ | |
| base_salary | decimal(12,2) | | ✗ | |
| bonus | decimal(12,2) | | ✗ | default `0` |
| deduction | decimal(12,2) | | ✗ | default `0` |
| total | decimal(12,2) | | ✗ | |
| paid_at | timestamp | | ✓ | |
| status | string | | ✗ | default `pending` |

*Soft delete: có.*

---

## Bảng hạ tầng Laravel (không phải nghiệp vụ, không nằm trong ERD)

`password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs` — sinh bởi Laravel skeleton mặc định, phục vụ auth/queue/cache, không thuộc phạm vi Data Dictionary nghiệp vụ.
