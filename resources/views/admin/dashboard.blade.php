@php
    $roleLabels = [
        'platform_admin' => 'Platform Admin', 'gym_owner' => 'Chủ Gym',
        'staff' => 'Nhân viên', 'trainer' => 'Huấn luyện viên', 'member' => 'Hội viên',
    ];
    $roleIcons = [
        'platform_admin' => 'building-office', 'gym_owner' => 'user-circle',
        'staff' => 'users', 'trainer' => 'star', 'member' => 'users',
    ];
@endphp
<x-app-layout>
    <x-slot name="header">Tổng quan nền tảng</x-slot>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-6">
        <x-stat-card icon="building-office" color="indigo" label="Tổng số Gym" :value="$total_gyms" />
        <x-stat-card icon="check-badge" color="emerald" label="Gym đang hoạt động" :value="$active_gyms" />
        <x-stat-card icon="users" color="sky" label="Tổng số user" :value="$total_users" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 text-sm font-semibold text-gray-900">User theo vai trò</div>
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <tbody class="divide-y divide-gray-100">
                    @foreach ($roleLabels as $role => $label)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 text-gray-700 flex items-center gap-2">
                                <x-icon :name="$roleIcons[$role]" class="w-4 h-4 text-gray-400" />
                                {{ $label }}
                            </td>
                            <td class="px-5 py-3 text-right font-medium text-gray-900">{{ $users_by_role[$role] ?? 0 }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 flex flex-col justify-center items-start">
            <div class="h-11 w-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center mb-3">
                <x-icon name="building-office" class="w-6 h-6" />
            </div>
            <p class="text-sm text-gray-600 mb-4">Quản lý danh sách Gym trên nền tảng, kích hoạt hoặc vô hiệu hoá từng Gym.</p>
            <a href="{{ route('admin.gyms.index') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-white text-sm font-medium hover:bg-indigo-700">
                Quản lý Gym <x-icon name="chevron-right" class="w-4 h-4" />
            </a>
        </div>
    </div>
</x-app-layout>
