<?php

namespace App\Services;

use App\Models\Gym;
use App\Models\Membership;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Tạo Payment (trạng thái pending) cho một Membership đang chờ thanh toán,
 * kèm QR VietQR, và xác nhận thanh toán (Khối 2 — Ngày 2): việc xác nhận là
 * thao tác thủ công của Staff/Admin sau khi đối chiếu sao kê ngân hàng, hệ
 * thống này không có tích hợp ngân hàng thật nên không tự động hóa bước đó
 * (mục 8 của đề bài).
 */
class PaymentService
{
    private const MAX_CODE_GENERATION_ATTEMPTS = 3;

    public function __construct(
        private readonly VietQrService $vietQr,
        private readonly InvoiceService $invoiceService,
    ) {}

    public function create(Membership $membership): Payment
    {
        if ($membership->status !== Membership::STATUS_PENDING) {
            throw new InvalidArgumentException(
                'Chỉ có thể tạo thanh toán cho membership đang ở trạng thái chờ (pending).'
            );
        }

        if ($membership->payments()->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_PAID])->exists()) {
            throw new InvalidArgumentException('Membership này đã có một thanh toán đang chờ hoặc đã hoàn tất.');
        }

        $attempt = 0;

        while (true) {
            $attempt++;

            try {
                return DB::transaction(function () use ($membership) {
                    $gym = Gym::findOrFail($membership->gym_id);
                    $transactionCode = $this->nextTransactionCode($gym);
                    $amount = $membership->final_price;

                    return Payment::create([
                        'gym_id' => $membership->gym_id,
                        'membership_id' => $membership->id,
                        'member_id' => $membership->member_id,
                        'transaction_code' => $transactionCode,
                        'amount' => $amount,
                        'method' => Payment::METHOD_BANK_TRANSFER,
                        'status' => Payment::STATUS_PENDING,
                        'qr_payload' => $this->vietQr->dynamicUrl($amount, $transactionCode),
                    ]);
                });
            } catch (QueryException $e) {
                if ($this->isDuplicateTransactionCode($e) && $attempt < self::MAX_CODE_GENERATION_ATTEMPTS) {
                    continue;
                }

                throw $e;
            }
        }
    }

    /**
     * Xác nhận Staff/Admin đã nhận tiền (Khối 2 — Ngày 2): Payment -> paid,
     * Membership -> active, sinh Invoice, tạo Notification cho hội viên.
     * TẤT CẢ trong 1 transaction duy nhất — nếu bất kỳ bước nào ném lỗi
     * (kể cả tạo Invoice/Notification), toàn bộ rollback, Payment vẫn
     * pending và Membership vẫn pending (không có trạng thái nửa vời).
     *
     * Khóa dòng Payment (SELECT ... FOR UPDATE) trước khi kiểm tra trạng
     * thái để chặn xác nhận trùng lặp — kể cả khi 2 request xác nhận cùng
     * một Payment gửi lên gần như đồng thời, request thứ hai phải đợi
     * request thứ nhất commit rồi mới đọc được status=paid và bị chặn ở
     * đây, không tạo Invoice/Notification lần thứ hai.
     */
    public function confirm(Payment $payment, User $confirmedBy, ?string $note = null): Payment
    {
        return DB::transaction(function () use ($payment, $confirmedBy, $note) {
            /** @var Payment $lockedPayment */
            $lockedPayment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($lockedPayment->status !== Payment::STATUS_PENDING) {
                throw new InvalidArgumentException(
                    "Thanh toán {$lockedPayment->transaction_code} đã được xử lý trước đó (trạng thái hiện tại: {$lockedPayment->status}), không thể xác nhận lại."
                );
            }

            $lockedPayment->forceFill([
                'status' => Payment::STATUS_PAID,
                'paid_at' => now(),
                'confirmed_by' => $confirmedBy->id,
                'note' => $note,
            ])->save();

            /** @var Membership $membership */
            $membership = Membership::query()->whereKey($lockedPayment->membership_id)->lockForUpdate()->firstOrFail();

            if ($membership->status !== Membership::STATUS_PENDING) {
                throw new InvalidArgumentException(
                    "Membership #{$membership->id} không ở trạng thái chờ (pending), không thể kích hoạt qua thanh toán này."
                );
            }

            // start_date/end_date/remaining_pt_sessions đã được tính đúng từ lúc
            // tạo membership (MembershipService::create) — chỉ cần chuyển trạng
            // thái, KHÔNG tính lại, để giữ đúng ngày bắt đầu Staff đã chọn.
            $membership->forceFill(['status' => Membership::STATUS_ACTIVE])->save();

            $invoice = $this->invoiceService->create($lockedPayment, $membership);

            Notification::create([
                'gym_id' => $lockedPayment->gym_id,
                'user_id' => $membership->member->user_id,
                'type' => Notification::TYPE_PAYMENT_CONFIRMED,
                'title' => 'Thanh toán đã được xác nhận',
                'body' => "Thanh toán {$lockedPayment->transaction_code} đã được xác nhận. Membership của bạn đã kích hoạt, hóa đơn {$invoice->invoice_number} đã được phát hành.",
                'data' => [
                    'payment_id' => $lockedPayment->id,
                    'membership_id' => $membership->id,
                    'invoice_id' => $invoice->id,
                ],
            ]);

            return $lockedPayment->fresh(['membership', 'invoice']);
        });
    }

    /**
     * Sinh transaction_code dạng PAY-{gym.code}-{yyyymmdd}-{seq}, an toàn khi
     * nhiều request tạo payment đồng thời: khóa dòng Gym (SELECT ... FOR
     * UPDATE) để tuần tự hóa việc sinh mã theo từng Gym — cùng cơ chế đã dùng
     * cho member_code ở Ngày 1 (MemberService), không dùng count()+1 đơn thuần.
     */
    private function nextTransactionCode(Gym $gym): string
    {
        /** @var Gym $lockedGym */
        $lockedGym = Gym::query()->whereKey($gym->id)->lockForUpdate()->firstOrFail();

        if (! $lockedGym->code) {
            throw new InvalidArgumentException(
                "Gym '{$lockedGym->name}' chưa được cấu hình mã (code) để sinh transaction_code."
            );
        }

        $prefix = sprintf('PAY-%s-%s-', $lockedGym->code, now()->format('Ymd'));

        $maxSeq = Payment::withoutGlobalScope('gym')
            ->where('gym_id', $lockedGym->id)
            ->where('transaction_code', 'like', $prefix.'%')
            ->lockForUpdate()
            ->get(['transaction_code'])
            ->map(fn (Payment $p) => (int) substr($p->transaction_code, strlen($prefix)))
            ->max();

        return $prefix.sprintf('%04d', ($maxSeq ?? 0) + 1);
    }

    private function isDuplicateTransactionCode(QueryException $e): bool
    {
        return str_contains($e->getMessage(), 'payments_transaction_code_unique');
    }
}
