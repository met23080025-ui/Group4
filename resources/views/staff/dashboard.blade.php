<x-app-layout>
    <x-slot name="header">Tổng quan nhân viên</x-slot>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
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

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden mb-6">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-2">
            <x-icon name="calendar" class="w-5 h-5 text-gray-400" />
            <span class="text-sm font-semibold text-gray-900">Lớp sắp tới</span>
        </div>
        @if ($upcoming_schedules->isEmpty())
            <x-empty-state icon="calendar" title="Chưa có lớp nào sắp diễn ra" />
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

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="{{ route('gym.checkin.index') }}" class="flex items-center gap-3 bg-white rounded-xl border border-gray-200 shadow-sm p-4 hover:border-indigo-300 hover:shadow-md transition">
            <div class="h-9 w-9 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0"><x-icon name="qr-code" class="w-5 h-5" /></div>
            <span class="text-sm font-medium text-gray-800">Check-in cho khách</span>
        </a>
        <a href="{{ route('gym.payments.index') }}" class="flex items-center gap-3 bg-white rounded-xl border border-gray-200 shadow-sm p-4 hover:border-indigo-300 hover:shadow-md transition">
            <div class="h-9 w-9 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center shrink-0"><x-icon name="banknotes" class="w-5 h-5" /></div>
            <span class="text-sm font-medium text-gray-800">Xử lý thanh toán</span>
        </a>
        <a href="{{ route('gym.members.index') }}" class="flex items-center gap-3 bg-white rounded-xl border border-gray-200 shadow-sm p-4 hover:border-indigo-300 hover:shadow-md transition">
            <div class="h-9 w-9 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center shrink-0"><x-icon name="users" class="w-5 h-5" /></div>
            <span class="text-sm font-medium text-gray-800">Danh sách hội viên</span>
        </a>
        <a href="{{ route('gym.equipment.index') }}" class="flex items-center gap-3 bg-white rounded-xl border border-gray-200 shadow-sm p-4 hover:border-indigo-300 hover:shadow-md transition">
            <div class="h-9 w-9 rounded-lg bg-gray-100 text-gray-600 flex items-center justify-center shrink-0"><x-icon name="wrench-screwdriver" class="w-5 h-5" /></div>
            <span class="text-sm font-medium text-gray-800">Thiết bị</span>
        </a>
    </div>
</x-app-layout>
