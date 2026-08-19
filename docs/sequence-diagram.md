# Sequence Diagram — Workflow mục 26

Cùng nguồn 13 bước với `docs/activity-diagram.md` (xem `docs/day2-workflow-notes.md`), nhưng tập trung vào **luồng chính (happy path)** + các nhánh lỗi quan trọng nhất gộp trong khối `alt` (chi tiết đầy đủ MỌI rẽ nhánh đã có ở Activity Diagram, không lặp lại ở đây cho đỡ rối). Lane theo đúng yêu cầu: Member / Staff / Controller / Service / DB / PDF.

`Controller` đại diện chung cho `MembershipController`/`PaymentController`/`InvoiceController`/`AttendanceController`; `Service` đại diện chung cho `MembershipService`/`PaymentService`/`VietQrService`/`InvoiceService`/`AttendanceService` — tên class cụ thể ghi trong nội dung message.

```mermaid
sequenceDiagram
    actor Member
    actor Staff
    participant Controller
    participant Service
    participant DB
    participant PDF as PDF (DomPDF + Storage)

    Staff->>Controller: Chọn Member + Package + Promotion (tuỳ chọn)
    Controller->>Service: MembershipService::create()
    Service->>DB: kiểm tra Promotion::isValidNow()
    DB-->>Service: hợp lệ
    Service->>Service: tính discount_amount/final_price (bcmath)
    Service->>DB: INSERT memberships (status=pending)
    DB-->>Service: Membership#id
    Service-->>Controller: Membership (pending)
    Controller-->>Staff: Trang Membership - chờ thanh toán

    Staff->>Controller: Bấm Tạo thanh toán
    Controller->>Service: PaymentService::create(membership)
    Service->>DB: SELECT gyms FOR UPDATE, sinh transaction_code tuần tự
    Service->>Service: VietQrService::dynamicUrl()
    Service->>DB: INSERT payments (status=pending, qr_payload)
    DB-->>Service: Payment#id
    Service-->>Controller: Payment + qr_payload
    Controller-->>Staff: Trang Payment kèm ảnh QR

    Member->>Member: Quét QR, chuyển khoản qua app ngân hàng (ngoài hệ thống)

    Staff->>Controller: Bấm Xác nhận đã nhận tiền
    Controller->>Service: PaymentService::confirm(payment, staff)
    activate Service
    Service->>DB: BEGIN TRANSACTION
    Service->>DB: SELECT payments ... FOR UPDATE

    alt Payment không còn pending (đã xử lý trước đó)
        DB-->>Service: status != pending
        Service->>DB: ROLLBACK
        Service-->>Controller: InvalidArgumentException
        Controller-->>Staff: Flash lỗi - đã xác nhận trước đó
    else Payment còn pending
        Service->>DB: UPDATE payments SET status=paid, paid_at, confirmed_by
        Service->>DB: SELECT memberships ... FOR UPDATE
        Service->>DB: UPDATE memberships SET status=active
        Service->>Service: InvoiceService::create()
        Service->>DB: SELECT gyms FOR UPDATE, sinh invoice_number tuần tự
        Service->>DB: INSERT invoices (subtotal/discount/total)
        Service->>DB: INSERT notifications (type=payment_confirmed)
        Service->>DB: COMMIT
        DB-->>Service: OK
        Service-->>Controller: Payment (paid) + Invoice
        Controller-->>Staff: Trang Payment = paid, link tải hoá đơn
    end
    deactivate Service

    Staff->>Controller: Bấm tải hoá đơn PDF
    Controller->>Service: InvoiceService::ensureStored(invoice)
    alt Đã có pdf_path và file tồn tại trên disk
        Service->>PDF: Storage::exists(pdf_path)
        PDF-->>Service: true - trả file cũ, KHÔNG render lại
    else Chưa có file
        Service->>PDF: DomPDF render (font DejaVu Sans)
        PDF-->>Service: nội dung PDF
        Service->>PDF: Storage::put(invoices/{invoice_number}.pdf)
        Service->>DB: UPDATE invoices SET pdf_path
    end
    Service-->>Controller: đường dẫn PDF
    Controller-->>Staff: Trả file PDF về trình duyệt

    Member->>Controller: Mở trang /qr
    Controller->>Service: AttendanceService::tokenFor(member)
    opt Chưa từng có qr_secret
        Service->>DB: UPDATE members SET qr_secret (ngẫu nhiên 40 ký tự)
    end
    Service-->>Controller: token = base64(member_id . HMAC-SHA256)
    Controller-->>Member: Hiển thị ảnh QR

    Member->>Staff: Đưa QR để quét (trực tiếp tại quầy lễ tân)
    Staff->>Controller: Nhập/quét token tại /gym/checkin
    Controller->>Service: AttendanceService::checkIn(token, staff)
    Service->>Service: giải mã base64 + xác thực chữ ký (hash_equals)

    alt Chữ ký sai, khác Gym, member bị khoá, hết hạn membership, hoặc đã check-in hôm nay
        Service-->>Controller: InvalidArgumentException / CrossTenantOperationException
        Controller-->>Staff: Flash lỗi tương ứng (xem Activity Diagram)
    else Hợp lệ
        Service->>DB: BEGIN TRANSACTION
        Service->>DB: INSERT attendances (source=qr, check_in_date=hôm nay)
        Service->>DB: đọc balance_after gần nhất
        Service->>DB: INSERT loyalty_point_transactions (+10 điểm)
        Service->>DB: COMMIT
        DB-->>Service: OK
        Service-->>Controller: Attendance
        Controller-->>Staff: "+10 điểm loyalty", nhật ký check-in cập nhật
    end
```

## Ghi chú đọc diagram

- 2 khối `alt` lớn (xác nhận thanh toán, check-in) là **đúng 2 giao dịch nguyên tử thật** trong code (`DB::transaction()` trong `PaymentService::confirm()` và `AttendanceService::checkIn()`) — nhánh lỗi bên trong luôn kết thúc bằng rollback ngầm định (Laravel tự rollback khi closure của `DB::transaction()` ném exception), không có commit một phần.
- `opt` (không phải `alt`) ở bước sinh `qr_secret` vì đây là nhánh **không bắt buộc phải có** (chỉ chạy lần đầu member xem QR), không phải 2 lựa chọn loại trừ nhau.
- Muốn xem đầy đủ **mọi** nhánh từ chối riêng lẻ (5 rẽ nhánh ở bước check-in, rẽ nhánh discount_type, v.v.) — xem `docs/activity-diagram.md`, đúng vai trò của 2 diagram: Sequence tả *thứ tự gọi giữa các lớp*, Activity tả *toàn bộ luồng quyết định nghiệp vụ*.
