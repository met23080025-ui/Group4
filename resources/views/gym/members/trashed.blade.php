<x-app-layout>
    <x-slot name="header">Thùng rác — Hội viên</x-slot>

    <div class="mb-4">
        <a href="{{ route('gym.members.index') }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; Quay lại danh sách hội viên</a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Mã HV</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Họ tên</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Email</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Đã vô hiệu hóa lúc</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($members as $member)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $member->member_code }}</td>
                        <td class="px-4 py-3">{{ $member->user->name }}</td>
                        <td class="px-4 py-3">{{ $member->user->email }}</td>
                        <td class="px-4 py-3">{{ $member->deleted_at?->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-right">
                            <form method="POST" action="{{ route('gym.members.restore', $member->id) }}">
                                @csrf
                                <button type="submit" class="text-emerald-600 hover:text-emerald-800">Khôi phục</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-500">Thùng rác trống.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $members->links() }}
    </div>
</x-app-layout>
