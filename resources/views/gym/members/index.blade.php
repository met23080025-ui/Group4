<x-app-layout>
    <x-slot name="header">Hội viên</x-slot>

    <div class="flex items-center justify-between mb-4">
        <div class="text-sm text-gray-500">{{ $members->total() }} hội viên</div>
        <div class="flex items-center gap-3">
            <a href="{{ route('gym.members.trashed') }}" class="text-sm text-gray-600 hover:text-gray-900">Thùng rác</a>
            <a href="{{ route('gym.members.create') }}"
                class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">
                <x-icon name="plus" class="w-4 h-4" /> Thêm hội viên
            </a>
        </div>
    </div>

    <form method="GET" action="{{ route('gym.members.index') }}" class="bg-white rounded-2xl border border-gray-200 shadow-sm p-4 mb-5">
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <div class="sm:col-span-2">
                <x-input-label for="search" value="Tìm kiếm (tên / email / mã HV / SĐT)" />
                <x-text-input id="search" name="search" type="text" class="mt-1 block w-full" :value="$filters['search'] ?? ''" />
            </div>
            <div>
                <x-input-label for="status" value="Trạng thái" />
                <select id="status" name="status" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                    <option value="">-- Tất cả --</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Đang hoạt động</option>
                    <option value="expired" @selected(($filters['status'] ?? '') === 'expired')>Hết hạn</option>
                    <option value="blocked" @selected(($filters['status'] ?? '') === 'blocked')>Bị khoá</option>
                </select>
            </div>
            <div>
                <x-input-label for="sort" value="Sắp xếp theo" />
                <select id="sort" name="sort" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                    <option value="" @selected(($filters['sort'] ?? '') === '')>Mới tạo trước</option>
                    <option value="name" @selected(($filters['sort'] ?? '') === 'name')>Tên</option>
                    <option value="joined_at" @selected(($filters['sort'] ?? '') === 'joined_at')>Ngày tham gia</option>
                </select>
            </div>
            <div>
                <x-input-label for="joined_from" value="Tham gia từ ngày" />
                <x-text-input id="joined_from" name="joined_from" type="date" class="mt-1 block w-full" :value="$filters['joined_from'] ?? ''" />
            </div>
            <div>
                <x-input-label for="joined_to" value="Đến ngày" />
                <x-text-input id="joined_to" name="joined_to" type="date" class="mt-1 block w-full" :value="$filters['joined_to'] ?? ''" />
            </div>
            <div>
                <x-input-label for="direction" value="Chiều" />
                <select id="direction" name="direction" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                    <option value="desc" @selected(($filters['direction'] ?? 'desc') === 'desc')>Giảm dần</option>
                    <option value="asc" @selected(($filters['direction'] ?? '') === 'asc')>Tăng dần</option>
                </select>
            </div>
            <div class="flex items-end">
                <x-primary-button>Lọc</x-primary-button>
                <a href="{{ route('gym.members.index') }}" class="ml-3 text-sm text-gray-600 hover:text-gray-900">Xoá lọc</a>
            </div>
        </div>
    </form>

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        @if ($members->isEmpty())
            <x-empty-state
                icon="users"
                title="Chưa có hội viên nào khớp bộ lọc."
                description="Thử điều chỉnh bộ lọc, hoặc thêm hội viên mới cho phòng gym của bạn."
            >
                <x-slot name="action">
                    <a href="{{ route('gym.members.create') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-white text-sm font-medium hover:bg-indigo-700">
                        <x-icon name="plus" class="w-4 h-4" /> Thêm hội viên
                    </a>
                </x-slot>
            </x-empty-state>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Mã HV</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Họ tên</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Email</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">SĐT</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Ngày tham gia</th>
                            <th class="px-5 py-3 text-left font-medium text-gray-500">Trạng thái</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($members as $member)
                            <tr class="odd:bg-white even:bg-gray-50/60 hover:bg-indigo-50/40">
                                <td class="px-5 py-3 font-medium text-gray-900">{{ $member->member_code }}</td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-2.5">
                                        <div class="h-8 w-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-semibold shrink-0">
                                            {{ mb_strtoupper(mb_substr($member->user->name, 0, 1)) }}
                                        </div>
                                        {{ $member->user->name }}
                                    </div>
                                </td>
                                <td class="px-5 py-3 text-gray-600">{{ $member->user->email }}</td>
                                <td class="px-5 py-3 text-gray-600">{{ $member->user->phone ?? 'Chưa cập nhật' }}</td>
                                <td class="px-5 py-3 text-gray-600">{{ optional($member->joined_at)->format('d/m/Y') ?? 'Chưa cập nhật' }}</td>
                                <td class="px-5 py-3"><x-status-badge :status="$member->status" /></td>
                                <td class="px-5 py-3 text-right">
                                    <a href="{{ route('gym.members.show', $member) }}" class="text-indigo-600 hover:text-indigo-900 font-medium">Xem</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="mt-4">
        {{ $members->links() }}
    </div>
</x-app-layout>
