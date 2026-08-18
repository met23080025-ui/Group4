<x-app-layout>
    <x-slot name="header">Membership</x-slot>

    <div class="flex items-center justify-between mb-4">
        <div class="text-sm text-gray-500">{{ $memberships->total() }} membership</div>
        <a href="{{ route('gym.memberships.create') }}"
            class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm rounded-lg hover:bg-gray-700">
            + Tạo membership
        </a>
    </div>

    <form method="GET" action="{{ route('gym.memberships.index') }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-4">
        <div class="flex items-end gap-4">
            <div>
                <x-input-label for="status" value="Trạng thái" />
                <select id="status" name="status" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                    <option value="">-- Tất cả --</option>
                    <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>Chờ thanh toán</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Đang hoạt động</option>
                    <option value="expired" @selected(($filters['status'] ?? '') === 'expired')>Hết hạn</option>
                    <option value="cancelled" @selected(($filters['status'] ?? '') === 'cancelled')>Đã hủy</option>
                </select>
            </div>
            <x-primary-button>Lọc</x-primary-button>
            <a href="{{ route('gym.memberships.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Xóa lọc</a>
        </div>
    </form>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Hội viên</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Gói</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Giá gốc</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Giảm giá</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Thành tiền</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Trạng thái</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($memberships as $membership)
                    <tr>
                        <td class="px-4 py-3">{{ $membership->member->member_code }} — {{ $membership->member->user->name }}</td>
                        <td class="px-4 py-3">{{ $membership->package->name }}</td>
                        <td class="px-4 py-3">{{ number_format($membership->original_price, 0, ',', '.') }} đ</td>
                        <td class="px-4 py-3">{{ number_format($membership->discount_amount, 0, ',', '.') }} đ</td>
                        <td class="px-4 py-3 font-semibold">{{ number_format($membership->final_price, 0, ',', '.') }} đ</td>
                        <td class="px-4 py-3">
                            <span @class([
                                'px-2 py-0.5 rounded-full text-xs font-medium',
                                'bg-amber-100 text-amber-700' => $membership->status === 'pending',
                                'bg-emerald-100 text-emerald-700' => $membership->status === 'active',
                                'bg-gray-100 text-gray-600' => $membership->status === 'expired',
                                'bg-red-100 text-red-700' => $membership->status === 'cancelled',
                            ])>{{ $membership->status }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('gym.memberships.show', $membership) }}" class="text-indigo-600 hover:text-indigo-900">Xem</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-gray-500">Chưa có membership nào khớp bộ lọc.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $memberships->links() }}</div>
</x-app-layout>
