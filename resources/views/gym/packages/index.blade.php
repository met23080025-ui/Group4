<x-app-layout>
    <x-slot name="header">Gói tập</x-slot>

    <div class="flex items-center justify-between mb-4">
        <div class="text-sm text-gray-500">{{ $packages->total() }} gói tập</div>
        <a href="{{ route('gym.packages.create') }}"
            class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm rounded-lg hover:bg-gray-700">
            + Thêm gói tập
        </a>
    </div>

    <form method="GET" action="{{ route('gym.packages.index') }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-4">
        <div class="grid grid-cols-1 sm:grid-cols-6 gap-4">
            <div class="sm:col-span-2">
                <x-input-label for="search" value="Tìm theo tên" />
                <x-text-input id="search" name="search" type="text" class="mt-1 block w-full" :value="$filters['search'] ?? ''" />
            </div>
            <div>
                <x-input-label for="min_price" value="Giá từ" />
                <x-text-input id="min_price" name="min_price" type="number" class="mt-1 block w-full" :value="$filters['min_price'] ?? ''" />
            </div>
            <div>
                <x-input-label for="max_price" value="Giá đến" />
                <x-text-input id="max_price" name="max_price" type="number" class="mt-1 block w-full" :value="$filters['max_price'] ?? ''" />
            </div>
            <div>
                <x-input-label for="duration_days" value="Thời hạn (ngày)" />
                <x-text-input id="duration_days" name="duration_days" type="number" class="mt-1 block w-full" :value="$filters['duration_days'] ?? ''" />
            </div>
            <div>
                <x-input-label for="status" value="Trạng thái" />
                <select id="status" name="status" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                    <option value="">-- Tất cả --</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Đang mở bán</option>
                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Ngừng bán</option>
                </select>
            </div>
            <div>
                <x-input-label for="direction" value="Sắp xếp theo giá" />
                <select id="direction" name="direction" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                    <option value="asc" @selected(($filters['direction'] ?? 'asc') === 'asc')>Thấp đến cao</option>
                    <option value="desc" @selected(($filters['direction'] ?? '') === 'desc')>Cao đến thấp</option>
                </select>
            </div>
            <div class="flex items-end gap-3">
                <x-primary-button>Lọc</x-primary-button>
                <a href="{{ route('gym.packages.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Xóa lọc</a>
            </div>
        </div>
    </form>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Tên gói</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Giá</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Thời hạn</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">PT kèm</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Trạng thái</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($packages as $package)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $package->name }}</td>
                        <td class="px-4 py-3">{{ number_format($package->price, 0, ',', '.') }} đ</td>
                        <td class="px-4 py-3">{{ $package->duration_days }} ngày</td>
                        <td class="px-4 py-3">{{ $package->pt_sessions }}</td>
                        <td class="px-4 py-3">
                            <span @class([
                                'px-2 py-0.5 rounded-full text-xs font-medium',
                                'bg-emerald-100 text-emerald-700' => $package->is_active,
                                'bg-gray-100 text-gray-600' => ! $package->is_active,
                            ])>{{ $package->is_active ? 'Đang mở bán' : 'Ngừng bán' }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('gym.packages.show', $package) }}" class="text-indigo-600 hover:text-indigo-900">Xem</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-gray-500">Chưa có gói tập nào khớp bộ lọc.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $packages->links() }}</div>
</x-app-layout>
