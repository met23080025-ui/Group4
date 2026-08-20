@props(['icon' => 'inbox', 'title', 'description' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center text-center py-12 px-4']) }}>
    <div class="h-14 w-14 rounded-full bg-gray-100 flex items-center justify-center text-gray-400 mb-4">
        <x-icon :name="$icon" class="w-7 h-7" />
    </div>
    <div class="text-sm font-medium text-gray-700">{{ $title }}</div>
    @if ($description)
        <p class="mt-1 text-sm text-gray-400 max-w-sm">{{ $description }}</p>
    @endif
    @isset($action)
        <div class="mt-4">{{ $action }}</div>
    @endisset
</div>
