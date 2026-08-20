@props(['icon', 'label', 'value', 'color' => 'indigo', 'hint' => null])

@php
    $colors = [
        'indigo' => 'bg-indigo-50 text-indigo-600',
        'emerald' => 'bg-emerald-50 text-emerald-600',
        'amber' => 'bg-amber-50 text-amber-600',
        'red' => 'bg-red-50 text-red-600',
        'sky' => 'bg-sky-50 text-sky-600',
        'gray' => 'bg-gray-100 text-gray-600',
    ];

    $iconClasses = $colors[$color] ?? $colors['indigo'];
@endphp

<div {{ $attributes->merge(['class' => 'bg-white rounded-2xl border border-gray-200 shadow-sm p-5 flex items-start gap-4']) }}>
    <div class="shrink-0 h-11 w-11 rounded-xl flex items-center justify-center {{ $iconClasses }}">
        <x-icon :name="$icon" class="w-6 h-6" />
    </div>
    <div class="min-w-0">
        <div class="text-sm text-gray-500 truncate">{{ $label }}</div>
        <div class="mt-0.5 text-2xl font-semibold text-gray-900 tracking-tight">{{ $value }}</div>
        @if ($hint)
            <div class="mt-0.5 text-xs text-gray-400">{{ $hint }}</div>
        @endif
    </div>
</div>
