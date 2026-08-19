@php
    $roleLabels = [
        'platform_admin' => 'Platform Admin', 'gym_owner' => 'Chủ Gym',
        'staff' => 'Nhân viên', 'trainer' => 'Huấn luyện viên', 'member' => 'Hội viên',
    ];
@endphp
<x-app-layout>
    <x-slot name="header">Tổng quan nền tảng</x-slot>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <div class="text-sm text-gray-500">Tổng số Gym</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $total_gyms }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <div class="text-sm text-gray-500">Gym đang hoạt động</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $active_gyms }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <div class="text-sm text-gray-500">Tổng số user</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $total_users }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-x-auto">
            <div class="px-4 py-3 border-b border-gray-100 text-sm font-semibold text-gray-900">User theo vai trò</div>
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <tbody class="divide-y divide-gray-100">
                    @foreach ($roleLabels as $role => $label)
                        <tr>
                            <td class="px-4 py-2 text-gray-700">{{ $label }}</td>
                            <td class="px-4 py-2 text-right font-medium text-gray-900">{{ $users_by_role[$role] ?? 0 }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 flex flex-col justify-center items-start">
            <p class="text-sm text-gray-600 mb-3">Quản lý danh sách Gym, kích hoạt/vô hiệu hóa từng Gym.</p>
            <a href="{{ route('admin.gyms.index') }}" class="rounded-md bg-indigo-600 px-4 py-2 text-white text-sm font-medium hover:bg-indigo-700">
                Quản lý Gym &rarr;
            </a>
        </div>
    </div>
</x-app-layout>
