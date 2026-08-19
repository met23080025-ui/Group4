<x-app-layout>
    <x-slot name="header">Thông báo</x-slot>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif

    <div class="mb-4 flex justify-end">
        <form method="POST" action="{{ route('notifications.read-all') }}">
            @csrf
            <button type="submit" class="text-sm text-indigo-600 hover:text-indigo-900">Đánh dấu tất cả đã đọc</button>
        </form>
    </div>

    <div class="space-y-2">
        @forelse ($notifications as $notification)
            <div class="bg-white rounded-xl border {{ $notification->read_at ? 'border-gray-200' : 'border-indigo-300 bg-indigo-50/30' }} shadow-sm p-4 flex items-start justify-between gap-4">
                <div>
                    <div class="text-sm font-semibold text-gray-900">{{ $notification->title }}</div>
                    @if ($notification->body)
                        <div class="text-sm text-gray-600 mt-1">{{ $notification->body }}</div>
                    @endif
                    <div class="text-xs text-gray-400 mt-1">{{ $notification->created_at->format('d/m/Y H:i') }}</div>
                </div>
                @unless ($notification->read_at)
                    <form method="POST" action="{{ route('notifications.read', $notification) }}">
                        @csrf
                        <button type="submit" class="text-xs text-indigo-600 hover:text-indigo-900 whitespace-nowrap">Đánh dấu đã đọc</button>
                    </form>
                @endunless
            </div>
        @empty
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 text-center text-gray-500 text-sm">
                Chưa có thông báo nào.
            </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $notifications->links() }}</div>
</x-app-layout>
