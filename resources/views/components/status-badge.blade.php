@props(['status', 'label' => null])

@php
    $map = [
        'active' => ['Đang hoạt động', 'bg-emerald-100 text-emerald-700'],
        'paid' => ['Đã thanh toán', 'bg-emerald-100 text-emerald-700'],
        'booked' => ['Đã đặt', 'bg-emerald-100 text-emerald-700'],
        'confirmed' => ['Đã xác nhận', 'bg-emerald-100 text-emerald-700'],
        'completed' => ['Hoàn thành', 'bg-emerald-100 text-emerald-700'],
        'pending' => ['Chờ xác nhận', 'bg-amber-100 text-amber-700'],
        'maintenance' => ['Đang bảo dưỡng', 'bg-amber-100 text-amber-700'],
        'expired' => ['Hết hạn', 'bg-gray-200 text-gray-600'],
        'cancelled' => ['Đã huỷ', 'bg-gray-200 text-gray-600'],
        'retired' => ['Ngừng sử dụng', 'bg-gray-200 text-gray-600'],
        'no_show' => ['Không đến', 'bg-gray-200 text-gray-600'],
        'blocked' => ['Bị khoá', 'bg-red-100 text-red-700'],
        'failed' => ['Thất bại', 'bg-red-100 text-red-700'],
    ];

    [$autoLabel, $colorClasses] = $map[$status] ?? [$status, 'bg-gray-200 text-gray-600'];
    $sizeClasses = $attributes->get('class') ?: 'px-2.5 py-1 text-xs';
@endphp

<span {{ $attributes->except('class')->merge(['class' => "inline-flex items-center rounded-full font-medium whitespace-nowrap $colorClasses $sizeClasses"]) }}>
    {{ $label ?? $autoLabel }}
</span>
