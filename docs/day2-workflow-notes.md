# Ngày 2 — Ghi chú workflow mục 26 + schema mới (để vẽ Sequence/Activity Diagram ở Ngày 3)

Tài liệu nội bộ, không phải Data Dictionary chính thức — mục đích duy nhất là làm nguyên liệu để vẽ Sequence Diagram và Activity Diagram cho mục 26 ("chọn gói tập → thanh toán → kích hoạt") mở rộng thêm phần check-in ở Ngày 2. Test bằng chứng cho toàn bộ workflow này: `tests/Feature/CoreWorkflowEndToEndTest.php` (1 test, chạy bằng service thật, không mock), xanh trong tag `day2-core-workflow`.

## Bảng + cột mới đụng tới ở Ngày 2

Không có bảng mới — cả 30 bảng đã tạo hết ở Ngày 1 (Khối 2). Ngày 2 chỉ thêm đúng 4 cột mới qua 3 migration (`2026_08_19_00000{1,2,3}`):

| # | Bảng | Cột mới | Kiểu | Ghi chú |
|---|---|---|---|---|
| 1 | `schedules` | `is_pt_session` | `boolean default false` | Đánh dấu buổi PT 1-kèm-1 (thường capacity=1) khác lớp nhóm |
| 2 | `class_bookings` | `membership_id` | `FK nullable → memberships` | Ghi lại đúng membership đã dùng lúc đặt, để hoàn đúng `remaining_pt_sessions` khi huỷ dù member đã đổi gói |
| 3 | `members` | `qr_secret` | `text nullable`, cast `encrypted` | Khóa HMAC ký token QR check-in, sinh lười lần đầu xem QR, không rời server |
| 4 | `members` | `trainer_id` | `FK nullable → trainers` | PT phụ trách chính, 1 member : 1 trainer tại 1 thời điểm, không cần bảng pivot |

Bảng đã có từ Ngày 1 nhưng lần đầu có logic/dữ liệu thật ở Ngày 2 (để biết đường nào "sống" từ giờ khi vẽ ERD/diagram): `payments`, `invoices`, `notifications`, `schedules`, `class_bookings`, `attendances`, `loyalty_point_transactions`, `body_measurements`, `workout_plans`, `workout_plan_items`, `nutrition_plans`, `nutrition_plan_items`.

## Workflow mục 26 — chi tiết từng bước

**Actor / lane gợi ý cho Sequence Diagram:** Member, Staff/Owner, Controller, Service (`MembershipService`/`PaymentService`/`VietQrService`/`InvoiceService`/`AttendanceService`), Database.

Giai đoạn 1-4 là "chọn gói → thanh toán → kích hoạt" (mục 26 gốc). Giai đoạn 5 là phần nối thêm ở Ngày 2 (check-in ngay sau kích hoạt) — chứng minh membership vừa active dùng được thật, không chỉ là con số trong DB.

### Giai đoạn 1 — Tạo Membership (pending)

1. Staff/Owner mở form, chọn Member + Package + Promotion (tuỳ chọn).
2. `StoreMembershipRequest` validate `member_id`/`package_id`/`promotion_id` đều thuộc đúng `gym_id` hiện tại (raw query, chặn cross-tenant ngay ở 422).
3. `MembershipService::create()`:
   - `guardSameGym()` — lớp phòng vệ thứ 2 (403 nếu bypass Form Request, ví dụ gọi Service trực tiếp).
   - Nếu có promotion: kiểm tra `isValidNow()` (active + trong ngày + chưa hết `usage_limit`) — **rẽ nhánh: hợp lệ / bị từ chối**.
   - Tính `original_price` = giá gói; `discount_amount` (percent = giá×%/100, fixed = giá trị cố định — **rẽ nhánh theo `discount_type`**, luôn cắt không vượt `original_price`); `final_price` = original − discount (bcmath, không dùng float).
   - `start_date` = ngày chọn hoặc hôm nay; `end_date` = start + `duration_days`.
   - Tạo `Membership` **status=pending**. Nếu có promotion, `used_count++`.
4. Redirect sang trang Membership, hiển thị "chờ thanh toán".

### Giai đoạn 2 — Tạo Payment + QR

5. Staff/Owner bấm "Tạo thanh toán" → `PaymentService::create(membership)`:
   - **Rẽ nhánh:** membership không pending, hoặc đã có payment pending/paid cho membership này → từ chối.
   - Khóa dòng Gym (`lockForUpdate`), sinh `transaction_code` tuần tự dạng `PAY-{gymCode}-{yyyymmdd}-{seq}`.
   - `VietQrService::dynamicUrl()` build URL ảnh QR (nhúng sẵn số tiền + nội dung CK) từ `img.vietqr.io` (không tự sinh chuỗi EMVCo).
   - Tạo `Payment` **status=pending**.
6. Hiển thị trang Payment với ảnh QR.

### Giai đoạn 3 — Thanh toán ngoài hệ thống

7. Member quét QR bằng app ngân hàng, chuyển khoản (hành động vật lý, ngoài phạm vi hệ thống — không có tích hợp ngân hàng thật).

### Giai đoạn 4 — Staff xác nhận (atomic — "trái tim" của mục 26)

8. Staff/Owner đối chiếu sao kê thật, bấm "Xác nhận đã nhận tiền" → `PaymentService::confirm()` — **toàn bộ trong 1 DB transaction**:
   - Khóa dòng Payment (`SELECT...FOR UPDATE`); **rẽ nhánh:** nếu không còn pending (đã xác nhận trước đó) → từ chối, ngăn xác nhận trùng khi 2 request gần đồng thời.
   - Payment → `paid`, ghi `paid_at`, `confirmed_by`, `note`.
   - Khóa dòng Membership; **rẽ nhánh:** nếu không pending → từ chối.
   - Membership → `active` (giữ nguyên `start_date`/`end_date`/`remaining_pt_sessions` đã tính ở bước 3, KHÔNG tính lại).
   - `InvoiceService::create()`: khóa Gym, sinh `invoice_number` tuần tự dạng `INV-{gymCode}-{yyyymmdd}-{seq}`, tạo `Invoice` (subtotal/discount/total copy từ membership).
   - Tạo `Notification` (`type=payment_confirmed`, kèm `data{payment_id, membership_id, invoice_id}`) cho member.
   - **Bất kỳ bước nào lỗi → rollback toàn bộ** (Payment/Membership về lại pending, không Invoice/Notification nào được tạo) — điểm quan trọng nhất để vẽ trong Activity Diagram: 1 khối atomic, không có trạng thái nửa vời.
9. Redirect sang trang Payment: `paid`, có link tải hóa đơn.
10. (Tuỳ lúc) Staff/Owner hoặc Member tải PDF → `InvoiceService::ensureStored()` render DomPDF (font DejaVu Sans) lần đầu, lưu vào disk `local` tại `invoices/{invoice_number}.pdf`, các lần sau tái sử dụng file đã lưu (không render lại).

### Giai đoạn 5 — Check-in ngay sau kích hoạt (mở rộng Ngày 2)

11. Member mở `/qr` → `AttendanceService::tokenFor()`: sinh `qr_secret` (chuỗi ngẫu nhiên 40 ký tự, nếu chưa có, lưu vào `members.qr_secret`), tính token = `base64(member_id . '|' . HMAC-SHA256(member_id, qr_secret))`, hiển thị dưới dạng ảnh QR.
12. Member đưa QR cho Staff quét/nhập tại `/gym/checkin` → `AttendanceService::checkIn()`:
    - Giải mã base64, tách `member_id` + chữ ký, tra `Member` theo id, tính lại HMAC bằng đúng `qr_secret` đã lưu, so bằng `hash_equals` — **rẽ nhánh:** sai/giả mạo → từ chối ("Mã QR không hợp lệ").
    - **Rẽ nhánh:** `member.gym_id` ≠ gym của Staff đang quét → từ chối (cross-tenant, `CrossTenantOperationException`).
    - **Rẽ nhánh:** `member.status == blocked` → từ chối.
    - **Rẽ nhánh:** không có Membership `active` còn hạn (`start_date <= hôm nay <= end_date`) — chính là membership vừa active ở bước 8 → nếu KHÔNG có, từ chối.
    - **Rẽ nhánh:** đã check-in hôm nay rồi (unique `[gym_id, member_id, check_in_date]`) → từ chối.
    - Trong 1 transaction: tạo `Attendance` (`source=qr`, `check_in_date`=hôm nay, `check_in_time`=now) + tạo `LoyaltyPointTransaction` (+10 điểm, `reason=check_in`, `reference_type/reference_id` trỏ về Attendance vừa tạo, `balance_after` cộng dồn từ dòng gần nhất của member).
13. Redirect, hiển thị "+10 điểm loyalty", nhật ký check-in trong ngày cập nhật.

## Gợi ý dùng khi vẽ

- **Sequence Diagram:** dùng đúng 5 lane actor ở trên; mỗi bước 1-13 là 1 message; các message trong bước 8 và bước 12 nên gộp thành 1 "combined fragment" (transaction) để thể hiện tính atomic.
- **Activity Diagram:** mỗi dòng "**Rẽ nhánh:**" ở trên là 1 decision node (kim cương); toàn bộ giai đoạn 4 nên bọc trong 1 khung "transaction" duy nhất với 1 lối thoát rollback chung cho mọi nhánh lỗi bên trong.
- **Bằng chứng chạy thật:** `tests/Feature/CoreWorkflowEndToEndTest.php` — đọc theo đúng thứ tự assert trong file này để khớp 100% với 13 bước ở trên, không suy diễn thêm.
