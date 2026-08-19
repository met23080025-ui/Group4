@php
    $statusLabels = ['active' => 'Đang dùng', 'maintenance' => 'Đang bảo trì', 'retired' => 'Ngừng dùng'];
    $today = now()->toDateString();
@endphp
<x-app-layout>
    <x-slot name="header">Thiết bị</x-slot>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif

    <div class="mb-4 flex items-center justify-between">
        <form method="GET" class="flex items-center gap-2 text-sm">
            <select name="status" onchange="this.form.submit()" class="rounded-md border-gray-300 text-sm">
                <option value="">Tất cả trạng thái</option>
                @foreach ($statusLabels as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </form>
        <a href="{{ route('gym.equipment.create') }}" class="rounded-md bg-indigo-600 px-4 py-2 text-white text-sm font-medium hover:bg-indigo-700">
            + Thêm thiết bị
        </a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Tên thiết bị</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Danh mục</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Trạng thái</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Lịch bảo trì kế tiếp</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($equipment as $item)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $item->name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $item->category ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">{{ $statusLabels[$item->status] ?? $item->status }}</span>
                        </td>
                        <td class="px-4 py-3">
                            @if ($item->next_maintenance_at)
                                <span @class(['font-medium' => true, 'text-amber-600' => $item->next_maintenance_at->toDateString() <= $today])>
                                    {{ $item->next_maintenance_at->format('d/m/Y') }}
                                </span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('gym.equipment.show', $item) }}" class="text-indigo-600 hover:text-indigo-900">Xem</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">Chưa có thiết bị nào.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $equipment->links() }}</div>
</x-app-layout>
