@php
    $statusLabels = ['active' => 'Đang dùng', 'maintenance' => 'Đang bảo trì', 'retired' => 'Ngừng dùng'];
@endphp
<x-app-layout>
    <x-slot name="header">{{ $equipment->name }}</x-slot>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif

    <div class="mb-4 flex items-center justify-between">
        <a href="{{ route('gym.equipment.index') }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; Quay lại danh sách thiết bị</a>
        <div class="flex items-center gap-3">
            <a href="{{ route('gym.equipment.edit', $equipment) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50">Sửa</a>
            <form method="POST" action="{{ route('gym.equipment.destroy', $equipment) }}" onsubmit="return confirm('Xoá thiết bị này?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-50 text-red-700 text-sm rounded-lg hover:bg-red-100">Xoá</button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <dl class="grid grid-cols-2 gap-4 text-sm">
                <div><dt class="text-gray-500">Danh mục</dt><dd class="mt-1 text-gray-900">{{ $equipment->category ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">Trạng thái</dt><dd class="mt-1 text-gray-900">{{ $statusLabels[$equipment->status] ?? $equipment->status }}</dd></div>
                <div><dt class="text-gray-500">Ngày mua</dt><dd class="mt-1 text-gray-900">{{ optional($equipment->purchase_date)->format('d/m/Y') ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">Chu kỳ bảo trì</dt><dd class="mt-1 text-gray-900">{{ $equipment->maintenance_interval_days ? $equipment->maintenance_interval_days.' ngày' : '—' }}</dd></div>
                <div><dt class="text-gray-500">Bảo trì gần nhất</dt><dd class="mt-1 text-gray-900">{{ optional($equipment->last_maintenance_at)->format('d/m/Y') ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">Bảo trì kế tiếp</dt><dd class="mt-1 text-gray-900">{{ optional($equipment->next_maintenance_at)->format('d/m/Y') ?? '—' }}</dd></div>
            </dl>

            <div class="mt-6 border-t border-gray-100 pt-4">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">Lịch sử bảo trì</h3>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($equipment->maintenanceRecords as $record)
                            <tr>
                                <td class="px-2 py-2 text-gray-900">{{ $record->performed_at->format('d/m/Y') }}</td>
                                <td class="px-2 py-2 text-gray-500">{{ $record->description ?? '—' }}</td>
                                <td class="px-2 py-2 text-gray-500">{{ $record->performedBy?->name ?? '—' }}</td>
                                <td class="px-2 py-2 text-right text-gray-900">{{ $record->cost ? number_format($record->cost, 0, ',', '.').' đ' : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-2 py-4 text-center text-gray-500">Chưa có lịch sử bảo trì.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Ghi nhận bảo trì mới</h3>
            <form method="POST" action="{{ route('gym.equipment.maintenance.store', $equipment) }}" class="space-y-3 text-sm">
                @csrf
                <div>
                    <label class="block text-gray-600 mb-1">Ngày thực hiện</label>
                    <input type="date" name="performed_at" value="{{ now()->toDateString() }}" required class="w-full rounded-md border-gray-300 text-sm">
                    @error('performed_at')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-gray-600 mb-1">Mô tả</label>
                    <textarea name="description" rows="2" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                </div>
                <div>
                    <label class="block text-gray-600 mb-1">Chi phí</label>
                    <input type="number" step="1000" name="cost" class="w-full rounded-md border-gray-300 text-sm">
                </div>
                <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-white text-sm font-medium hover:bg-indigo-700">Ghi nhận</button>
            </form>
        </div>
    </div>
</x-app-layout>
