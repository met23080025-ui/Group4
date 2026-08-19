<?php

namespace App\Services;

use App\Models\Gym;
use App\Models\Invoice;
use Illuminate\Support\Carbon;

/**
 * Báo cáo doanh thu (mục 20, Ngày 3) — tính từ `invoices.total` (đã sinh ra
 * đúng lúc `PaymentService::confirm()` xác nhận payment `paid`, xem Khối 1-2
 * Ngày 2), KHÔNG cộng lại từ payments để tránh đếm trùng nếu 1 payment có
 * nhiều lần thử. Nhóm theo tháng/gói bằng Collection (PHP), không dùng
 * DATE_FORMAT/strftime (khác nhau giữa MySQL và SQLite) — cùng lý do đã né
 * driver-specific SQL ở AttendanceService/ClassBookingService.
 */
class ReportService
{
    public function revenue(Gym $gym, ?string $from = null, ?string $to = null): array
    {
        $fromDate = $from ? Carbon::parse($from)->startOfDay() : now()->startOfYear();
        $toDate = $to ? Carbon::parse($to)->endOfDay() : now()->endOfDay();

        $invoices = Invoice::query()
            ->where('gym_id', $gym->id)
            ->whereBetween('issued_at', [$fromDate, $toDate])
            ->with('payment.membership.package')
            ->get();

        $byMonth = $invoices
            ->groupBy(fn (Invoice $invoice) => $invoice->issued_at->format('Y-m'))
            ->map(fn ($group) => (float) $group->sum(fn (Invoice $i) => (float) $i->total))
            ->sortKeys();

        $byPackage = $invoices
            ->groupBy(fn (Invoice $invoice) => $invoice->payment?->membership?->package?->name ?? 'Không xác định')
            ->map(fn ($group) => (float) $group->sum(fn (Invoice $i) => (float) $i->total))
            ->sortByDesc(fn ($total) => $total);

        return [
            'from' => $fromDate->toDateString(),
            'to' => $toDate->toDateString(),
            'total' => (float) $invoices->sum(fn (Invoice $i) => (float) $i->total),
            'invoice_count' => $invoices->count(),
            'by_month' => $byMonth,
            'by_package' => $byPackage,
        ];
    }
}
