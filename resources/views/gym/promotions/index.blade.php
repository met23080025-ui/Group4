<x-app-layout>
    <x-slot name="header">Khuyến mãi</x-slot>

    <div class="flex items-center justify-between mb-4">
        <div class="text-sm text-gray-500">{{ $promotions->total() }} khuyến mãi</div>
        <a href="{{ route('gym.promotions.create') }}"
            class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm rounded-lg hover:bg-gray-700">
            + Thêm khuyến mãi
        </a>
    </div>

    <form method="GET" action="{{ route('gym.promotions.index') }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-4">
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <div class="sm:col-span-2">
                <x-input-label for="search" value="Tìm theo mã / tên" />
                <x-text-input id="search" name="search" type="text" class="mt-1 block w-full" :value="$filters['search'] ?? ''" />
            </div>
            <div>
                <x-input-label for="status" value="Trạng thái" />
                <select id="status" name="status" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                    <option value="">-- Tất cả --</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Đang kích hoạt</option>
                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Tạm dừng</option>
                </select>
            </div>
            <div class="flex items-end gap-3">
                <x-primary-button>Lọc</x-primary-button>
                <a href="{{ route('gym.promotions.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Xóa lọc</a>
            </div>
        </div>
    </form>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Mã</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Tên</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Giảm giá</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Hiệu lực</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Đã dùng</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Trạng thái</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($promotions as $promotion)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $promotion->code }}</td>
                        <td class="px-4 py-3">{{ $promotion->name }}</td>
                        <td class="px-4 py-3">
                            {{ $promotion->discount_type === 'percent' ? $promotion->discount_value.'%' : number_format($promotion->discount_value, 0, ',', '.').'đ' }}
                        </td>
                        <td class="px-4 py-3">{{ $promotion->start_date->format('d/m/Y') }} — {{ $promotion->end_date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">{{ $promotion->used_count }}{{ $promotion->usage_limit ? '/'.$promotion->usage_limit : '' }}</td>
                        <td class="px-4 py-3">
                            <span @class([
                                'px-2 py-0.5 rounded-full text-xs font-medium',
                                'bg-emerald-100 text-emerald-700' => $promotion->isValidNow(),
                                'bg-gray-100 text-gray-600' => ! $promotion->isValidNow(),
                            ])>{{ $promotion->isValidNow() ? 'Còn hiệu lực' : 'Không áp dụng được' }}</span>
                        </td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <a href="{{ route('gym.promotions.edit', $promotion) }}" class="text-indigo-600 hover:text-indigo-900">Sửa</a>
                            <form method="POST" action="{{ route('gym.promotions.destroy', $promotion) }}" class="inline" onsubmit="return confirm('Xóa khuyến mãi này?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800">Xóa</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-gray-500">Chưa có khuyến mãi nào khớp bộ lọc.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $promotions->links() }}</div>
</x-app-layout>
