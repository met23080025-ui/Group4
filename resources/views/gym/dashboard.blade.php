<x-app-layout>
    <x-slot name="header">Tổng quan Gym</x-slot>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5 mb-6">
        <x-stat-card icon="users" color="indigo" label="Tổng hội viên" :value="$total_members" />
        <x-stat-card icon="check-badge" color="emerald" label="Hội viên đang hoạt động" :value="$active_members" />
        <x-stat-card icon="exclamation-triangle" color="amber" label="Chưa có gói còn hạn" :value="$members_without_valid_membership" />
        <x-stat-card icon="banknotes" color="emerald" label="Doanh thu tháng này" :value="number_format($revenue_this_month, 0, ',', '.') . ' đ'" />
        <x-stat-card icon="credit-card" color="indigo" label="Membership mới tháng này" :value="$new_memberships_this_month" />
        <x-stat-card icon="qr-code" color="sky" label="Check-in hôm nay" :value="$checkins_today" />
        <x-stat-card icon="clock" color="amber" label="Thanh toán chờ xác nhận" :value="$pending_payments" />
        <x-stat-card icon="calendar" color="amber" label="Membership sắp hết hạn (7 ngày)" :value="$expiring_memberships" />
        <x-stat-card
            icon="wrench-screwdriver"
            :color="$equipment_due_for_maintenance > 0 ? 'red' : 'gray'"
            label="Thiết bị sắp đến lịch bảo trì (14 ngày)"
            :value="$equipment_due_for_maintenance"
        />
    </div>

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-2">
            <x-icon name="calendar" class="w-5 h-5 text-gray-400" />
            <span class="text-sm font-semibold text-gray-900">Lớp sắp tới</span>
        </div>
        @if ($upcoming_schedules->isEmpty())
            <x-empty-state
                icon="calendar"
                title="Chưa có lớp nào sắp diễn ra"
                description="Tạo lịch tập mới để hội viên có thể đặt lớp."
            >
                <x-slot name="action">
                    <a href="{{ route('gym.schedules.create') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-white text-sm font-medium hover:bg-indigo-700">
                        <x-icon name="plus" class="w-4 h-4" /> Tạo lịch tập
                    </a>
                </x-slot>
            </x-empty-state>
        @else
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <tbody class="divide-y divide-gray-100">
                    @foreach ($upcoming_schedules as $schedule)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-medium text-gray-900">{{ $schedule->title }}</td>
                            <td class="px-5 py-3 text-gray-500">{{ $schedule->class_date->format('d/m/Y') }}, {{ $schedule->start_time->format('H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</x-app-layout>
