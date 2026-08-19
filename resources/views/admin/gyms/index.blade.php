<x-app-layout>
    <x-slot name="header">Quản lý Gym</x-slot>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Tên Gym</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Mã</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">User</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Hội viên</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Trạng thái</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($gyms as $gym)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $gym->name }}</td>
                        <td class="px-4 py-3 text-gray-500 font-mono">{{ $gym->code ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $gym->users_count }}</td>
                        <td class="px-4 py-3">{{ $gym->members_count }}</td>
                        <td class="px-4 py-3">
                            <span @class([
                                'px-2 py-0.5 rounded-full text-xs font-medium',
                                'bg-emerald-100 text-emerald-700' => $gym->is_active,
                                'bg-gray-100 text-gray-600' => ! $gym->is_active,
                            ])>{{ $gym->is_active ? 'Đang hoạt động' : 'Đã vô hiệu hóa' }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <form method="POST" action="{{ route('admin.gyms.toggle-active', $gym) }}"
                                onsubmit="return confirm('{{ $gym->is_active ? 'Vô hiệu hóa' : 'Kích hoạt' }} Gym {{ $gym->name }}?');">
                                @csrf
                                <button type="submit" class="text-sm {{ $gym->is_active ? 'text-red-600 hover:text-red-800' : 'text-emerald-600 hover:text-emerald-800' }}">
                                    {{ $gym->is_active ? 'Vô hiệu hóa' : 'Kích hoạt' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">Chưa có Gym nào.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $gyms->links() }}</div>
</x-app-layout>
