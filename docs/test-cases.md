# Test Cases + Test Results

Nguồn: đếm trực tiếp bằng `grep -c "public function test_"` trên từng file trong `tests/Feature/` và `tests/Unit/` — không phải số ước lượng. Chạy lần cuối trước khi viết tài liệu này:

```
php artisan test
{"result":"passed","tests":214,"passed":214,"assertions":588}
```

**214/214 PASS — 100%, 0 test đỏ.**

---

## Ngày 1 — Foundation, Auth/Authz, Member/Package/Promotion/Membership (61 test)

| Test class | Số test | Mục đích |
|---|---|---|
| `TenantScopeTest` | 5 | Global scope `BelongsToGym`: user Gym A chỉ thấy dữ liệu Gym A, Gym B chỉ thấy Gym B, platform_admin thấy tất cả, ngữ cảnh CLI/guest không bị lọc (seeder chạy được), `Member::find()` trả `null` khi khác Gym (mô phỏng 404 route binding) |
| `Auth/AuthenticationTest` | 8 | Đăng nhập/đăng xuất, chặn `is_active=false`, chặn Gym `is_active=false`, platform_admin không cần Gym, cập nhật `last_login_at` |
| `Auth/RegistrationTest` | 3 | Đăng ký Member công khai, bắt buộc chọn Gym active |
| `Auth/EmailVerificationTest` | 3 | Luồng xác minh email (Breeze) |
| `Auth/PasswordConfirmationTest` | 3 | Xác nhận mật khẩu trước thao tác nhạy cảm |
| `Auth/PasswordResetTest` | 4 | Quên mật khẩu, đặt lại mật khẩu qua token |
| `Auth/PasswordUpdateTest` | 2 | Đổi mật khẩu khi đã đăng nhập |
| `ProfileTest` | 5 | Xem/sửa hồ sơ cá nhân, xoá tài khoản |
| `RoleAccessTest` | 7 | Middleware `role:` chặn 403; owner Gym A truy cập member Gym B → 404; owner vào đúng dashboard Gym mình; staff/admin phân quyền đúng; guest bị redirect login |
| `MemberManagementTest` | 8 | Sinh `member_code` tuần tự (row lock, không trùng), search/filter, soft-delete + khôi phục, sửa member Gym khác → 404, role `member` bị 403 |
| `MembershipCreationTest` | 11 | Tính đúng discount percent/fixed, cắt không âm, Membership luôn tạo `pending`, promotion hết hạn/hết lượt bị từ chối, cross-tenant package/promotion bị chặn (Service 403 + HTTP 422) |

## Ngày 2 — Payment/VietQR, Invoice, Schedule/Booking, QR Check-in, Trainer, E2E (84 test)

| Test class | Số test | Mục đích |
|---|---|---|
| `PaymentCreationTest` | 8 | Tạo Payment `pending` đúng số tiền/định dạng mã, mã tuần tự không trùng theo Gym, không tạo trùng payment, quyền xem theo Staff/Owner/Member, cross-tenant 404 |
| `PaymentConfirmationTest` | 7 | Xác nhận atomic (Payment→paid, Membership→active, Invoice, Notification cùng 1 transaction); **mock `InvoiceService` ném lỗi giữa transaction** để chứng minh rollback toàn bộ; không xác nhận trùng 2 lần; cross-tenant 404 |
| `InvoiceDownloadTest` | 6 | PDF là file thật có nội dung (`Storage::assertExists` + `size()>0`), tải lại không render lại file, quyền tải Staff/Member/cross-tenant |
| `ScheduleManagementTest` | 6 | CRUD lịch tập (Owner), chặn giảm capacity dưới số đã đặt, cross-tenant 404 |
| `ClassBookingTest` | 20 | Membership hết hạn/chưa có/pending đều bị chặn đặt lớp, **membership bắt đầu đúng hôm nay vẫn đặt được** (test hồi quy cho bug `whereDate`), capacity, trùng khung giờ, buổi PT trừ/hoàn đúng `remaining_pt_sessions`, huỷ booking, cross-tenant Service (403) + HTTP (404) |
| `AttendanceCheckInTest` | 13 | Token QR không phải id trần (có chữ ký HMAC 64 ký tự hex), token giả mạo bị chặn, member blocked/hết hạn/trùng ngày/cross-tenant đều bị chặn, **membership bắt đầu đúng hôm nay vẫn check-in được** (test hồi quy), check-in hợp lệ cộng đúng +10 điểm loyalty |
| `TrainerAssignmentTest` | 12 | Trainer chỉ xem/sửa được member đã phân công (cross-trainer 403, cross-tenant 404), Owner/Staff không giới hạn theo `trainer_id`, gán/gỡ PT qua HTTP, chặn gán trainer khác Gym |
| `TrainerDashboardTest` | 2 | Dashboard PT tính đúng lịch hôm nay/sắp tới/học viên phân công/số buổi đã dạy (loại trừ buổi bị huỷ); an toàn khi chưa có hồ sơ Trainer |
| `BodyMeasurementTest` | 5 | Tính đúng BMI (nhiều bộ số liệu), `record()` lưu đúng Gym/member, luồng HTTP Trainer/Member tự nhập, validate height/weight bắt buộc |
| `WorkoutNutritionPlanTest` | 4 | Trainer tạo plan + thêm item cho member đã phân công (workout + nutrition), trainer khác không thêm được item, Member không tự tạo plan |
| `CoreWorkflowEndToEndTest` | 1 | **Bằng chứng demo mạnh nhất** — chạy trọn mục 26 bằng service thật (không mock): chọn gói → Membership pending → Payment+QR → Staff xác nhận (atomic) → Invoice PDF là file thật → check-in QR ngay sau đó → Attendance + loyalty +10 |

## Ngày 3 — Community, Notification/Review, Dashboard/Reports, Equipment, Security (69 test)

| Test class | Số test | Mục đích |
|---|---|---|
| `CommunityFeedTest` | 19 | Feed scope đúng Gym, Member không tự đăng bài (403) nhưng Owner/Staff/Trainer đăng được, cross-tenant comment/react → 404, reaction toggle (bấm lại = gỡ, đổi loại = update), Owner/Staff kiểm duyệt xoá mọi post còn Trainer chỉ xoá bài mình, ghim announcement, platform_admin bị loại khỏi `/community` |
| `NotificationTest` | 10 | Notification scope đúng user+Gym (cross-gym 404, cùng-Gym-khác-user 403), mark-read/mark-all-read chỉ ảnh hưởng đúng chủ; 4 trigger: lớp đặt/huỷ, comment mới (trừ tự comment), announcement broadcast (trừ tác giả + Gym khác), membership sắp hết hạn (idempotent trong ngày) |
| `ReviewTest` | 10 | Member review Gym/Trainer, rating validate 1-5, chỉ Member tạo được, cross-tenant `trainer_id` bị chặn validate, Owner ẩn/hiện review, Member không kiểm duyệt được, cross-tenant moderate 404, Trainer chỉ thấy review về mình, review ẩn không lộ ra danh sách công khai |
| `DashboardReportsTest` | 13 | Owner/Staff/Member/Admin dashboard đúng số liệu; **doanh thu Owner Gym A không lộ sang Gym B** (yêu cầu bắt buộc); platform_admin thấy tổng hợp toàn nền tảng; kích hoạt/vô hiệu hoá Gym; Report doanh thu group đúng tháng/gói, filter khoảng ngày, Staff bị chặn khỏi Report (403) |
| `EquipmentTest` | 10 | Equipment CRUD scope Gym, cross-tenant 404 (xem/sửa/xoá/ghi bảo trì), ghi nhận bảo trì cập nhật đúng `last/next_maintenance_at`, đếm cảnh báo bảo trì đúng (due-soon/overdue/còn xa/chưa có lịch), cảnh báo không lộ chéo Gym |
| `TenantIsolationTest` | 7 | **Bằng chứng bảo mật multi-tenant quan trọng nhất** — Owner Gym A cố truy cập MỌI loại resource Gym B (member/payment/invoice/schedule/post/review/equipment) → toàn bộ 403/404, không có bản ghi nào bị sửa/xoá qua nỗ lực cross-tenant |

## Scaffolding (không phải test nghiệp vụ)

| Test class | Số test | Mục đích |
|---|---|---|
| `Feature/ExampleTest` | 1 | Mặc định Laravel — trang `/` trả 200 |
| `Unit/ExampleTest` | 1 | Mặc định Laravel — `assertTrue(true)` |

---

## Bug thật phát hiện qua test (không phải giả định)

1. **`whereDate` vs so chuỗi trực tiếp** (Khối 7, Ngày 2): `AttendanceService::hasValidMembership()` và `ClassBookingService::findActiveValidMembership()` so `start_date`/`end_date` bằng `where()` thay vì `whereDate()`. Trên SQLite, cột `date` cast lưu `"Y-m-d 00:00:00"` đầy đủ, khiến membership **bắt đầu đúng hôm nay** bị từ chối sai. Phát hiện qua `CoreWorkflowEndToEndTest`, sửa + thêm test hồi quy ở cả `ClassBookingTest` và `AttendanceCheckInTest`.
2. **`EquipmentService::recordMaintenance()` thiếu transaction** (Khối 5, Ngày 3): 2 write (tạo `MaintenanceRecord` + cập nhật `Equipment`) không nguyên tử — phát hiện khi rà soát checklist "Transaction" ở security review, không phải từ 1 test đỏ cụ thể. Đã bọc `DB::transaction()`, `EquipmentTest` vẫn xanh sau khi sửa.

Cả 2 đều đã có commit `fix(...)` riêng, tách khỏi commit `feat`/`test` — xem `git log`.
