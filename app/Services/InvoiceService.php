<?php

namespace App\Services;

use App\Models\Gym;
use App\Models\Invoice;
use App\Models\Membership;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

/**
 * Sinh Invoice cho một Payment vừa được xác nhận (Khối 2). Luôn được gọi
 * bên trong transaction của PaymentService::confirm() — KHÔNG tự mở
 * transaction ở đây để không tạo transaction lồng nhau; nếu bước nào sau
 * đó trong PaymentService::confirm() ném lỗi, Invoice vừa tạo cũng bị
 * rollback cùng Payment/Membership (tính nguyên tử của cả khối).
 */
class InvoiceService
{
    public function create(Payment $payment, Membership $membership): Invoice
    {
        /** @var Gym $gym */
        $gym = Gym::query()->whereKey($payment->gym_id)->lockForUpdate()->firstOrFail();

        return Invoice::create([
            'gym_id' => $payment->gym_id,
            'payment_id' => $payment->id,
            'member_id' => $payment->member_id,
            'invoice_number' => $this->nextInvoiceNumber($gym),
            'issued_at' => now(),
            'subtotal' => $membership->original_price,
            'discount' => $membership->discount_amount,
            'total' => $membership->final_price,
        ]);
    }

    /**
     * Đảm bảo file PDF của Invoice tồn tại trên disk 'local' và trả về đường
     * dẫn tương đối (đã lưu vào invoice.pdf_path). Sinh PDF một lần rồi tái sử
     * dụng cho các lần tải sau — không render lại mỗi request.
     */
    public function ensureStored(Invoice $invoice): string
    {
        if ($invoice->pdf_path && Storage::disk('local')->exists($invoice->pdf_path)) {
            return $invoice->pdf_path;
        }

        $path = "invoices/{$invoice->invoice_number}.pdf";

        Storage::disk('local')->put($path, $this->render($invoice)->output());
        $invoice->update(['pdf_path' => $path]);

        return $path;
    }

    /**
     * Render nội dung Invoice thành PDF. Font "DejaVu Sans" (đi kèm sẵn trong
     * dompdf/dompdf) hỗ trợ đầy đủ Unicode tiếng Việt có dấu — bắt buộc dùng
     * font này (hoặc font Unicode khác đã nhúng), font mặc định của DomPDF
     * (Helvetica) KHÔNG hiển thị được dấu tiếng Việt.
     */
    private function render(Invoice $invoice): \Barryvdh\DomPDF\PDF
    {
        $invoice->loadMissing(['gym', 'member.user', 'payment.membership.package']);

        return Pdf::loadView('invoices.pdf', ['invoice' => $invoice])->setPaper('a4');
    }

    /**
     * Sinh invoice_number dạng {prefix}-{gym.code}-{yyyymmdd}-{seq}, cùng cơ
     * chế khóa dòng Gym (SELECT ... FOR UPDATE) dùng để sinh transaction_code
     * ở PaymentService, tuần tự hóa việc sinh mã theo từng Gym.
     */
    private function nextInvoiceNumber(Gym $gym): string
    {
        if (! $gym->code) {
            throw new InvalidArgumentException(
                "Gym '{$gym->name}' chưa được cấu hình mã (code) để sinh invoice_number."
            );
        }

        $prefix = sprintf('%s-%s-%s-', config('services.invoice.prefix', 'INV'), $gym->code, now()->format('Ymd'));

        $maxSeq = Invoice::withoutGlobalScope('gym')
            ->where('gym_id', $gym->id)
            ->where('invoice_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->get(['invoice_number'])
            ->map(fn (Invoice $i) => (int) substr($i->invoice_number, strlen($prefix)))
            ->max();

        return $prefix.sprintf('%04d', ($maxSeq ?? 0) + 1);
    }
}
