<?php

namespace App\Services;

use App\Models\Gym;
use App\Models\Invoice;
use App\Models\Membership;
use App\Models\Payment;
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
