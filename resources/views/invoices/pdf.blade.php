<?php
/** @var \App\Models\Invoice $invoice */
$payment = $invoice->payment;
$package = $payment?->membership?->package;
$methodLabel = match ($payment?->method) {
    \App\Models\Payment::METHOD_CASH => 'Tiền mặt',
    \App\Models\Payment::METHOD_BANK_TRANSFER => 'Chuyển khoản ngân hàng',
    default => '—',
};
$money = fn ($value) => number_format((float) $value, 0, ',', '.').' đ';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: "DejaVu Sans", sans-serif; font-size: 12px; color: #1f2937; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .muted { color: #6b7280; }
        /* Bảng thay vì float: DomPDF không clear float đáng tin cậy, từng làm
           nội dung phía sau (vd. "Hội viên") bị vỡ dòng theo từng chữ. */
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .header-table td { border: none; padding: 0; vertical-align: top; }
        .header-table .meta { text-align: right; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; text-align: left; }
        th { background: #f3f4f6; font-size: 11px; text-transform: uppercase; color: #6b7280; }
        .text-right { text-align: right; }
        .total-row td { font-weight: bold; font-size: 14px; border-top: 2px solid #1f2937; border-bottom: none; }
        .section-title { font-size: 12px; font-weight: bold; margin: 16px 0 4px; text-transform: uppercase; color: #6b7280; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="width: 60%;">
                <h1>{{ $invoice->gym->name }}</h1>
                @if ($invoice->gym->address)
                    <div class="muted">{{ $invoice->gym->address }}</div>
                @endif
                @if ($invoice->gym->phone)
                    <div class="muted">ĐT: {{ $invoice->gym->phone }}</div>
                @endif
            </td>
            <td class="meta" style="width: 40%;">
                <h1>HÓA ĐƠN</h1>
                <div>Số: <strong>{{ $invoice->invoice_number }}</strong></div>
                <div class="muted">Ngày xuất: {{ $invoice->issued_at->format('d/m/Y H:i') }}</div>
            </td>
        </tr>
    </table>

    <div class="section-title">Hội viên</div>
    <div>{{ $invoice->member->user->name }} ({{ $invoice->member->member_code }})</div>

    <div class="section-title">Chi tiết</div>
    <table>
        <thead>
            <tr>
                <th>Gói tập</th>
                <th class="text-right">Đơn giá</th>
                <th class="text-right">Giảm giá</th>
                <th class="text-right">Thành tiền</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $package->name ?? '—' }}</td>
                <td class="text-right">{{ $money($invoice->subtotal) }}</td>
                <td class="text-right">{{ $money($invoice->discount) }}</td>
                <td class="text-right">{{ $money($invoice->total) }}</td>
            </tr>
            <tr class="total-row">
                <td colspan="3">Tổng cộng</td>
                <td class="text-right">{{ $money($invoice->total) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Thanh toán</div>
    <table>
        <tbody>
            <tr>
                <td class="muted">Phương thức</td>
                <td>{{ $methodLabel }}</td>
            </tr>
            <tr>
                <td class="muted">Mã giao dịch</td>
                <td>{{ $payment->transaction_code ?? '—' }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
