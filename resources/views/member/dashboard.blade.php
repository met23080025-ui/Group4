<x-app-layout>
    <x-slot name="header">Trang chủ</x-slot>

    <div class="bg-gradient-to-r from-indigo-600 to-indigo-500 rounded-2xl shadow-sm p-6 mb-6 text-white">
        <p>
            Xin chào, <strong>{{ auth()->user()->name }}</strong>. Chào mừng bạn đến với cộng đồng
            <strong>{{ auth()->user()->gym?->name }}</strong> trên GymHub.
        </p>
    </div>

    @if (! $member)
        <x-empty-state
            icon="credit-card"
            title="Tài khoản của bạn chưa có hồ sơ hội viên"
            description="Membership, lịch tập và các tính năng khác sẽ được kích hoạt sau khi bạn chọn gói tập."
            class="bg-white rounded-2xl border border-gray-200 shadow-sm"
        />
    @else
        <div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-4 gap-5 mb-6">
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 sm:col-span-2 flex items-start gap-4">
                <div class="shrink-0 h-11 w-11 rounded-xl flex items-center justify-center bg-indigo-50 text-indigo-600">
                    <x-icon name="credit-card" class="w-6 h-6" />
                </div>
                <div class="min-w-0">
                    <div class="text-sm text-gray-500">Membership hiện tại</div>
                    @if ($stats['current_membership'])
                        <div class="mt-0.5 text-lg font-semibold text-gray-900">{{ $stats['current_membership']->package->name }}</div>
                        <div class="text-sm text-gray-600 mt-1 flex items-center gap-2">
                            <x-status-badge :status="$stats['days_remaining'] <= 7 ? 'pending' : 'active'" :label="'Còn ' . $stats['days_remaining'] . ' ngày'" />
                            <span class="text-gray-400">hết hạn {{ $stats['current_membership']->end_date->format('d/m/Y') }}</span>
                        </div>
                    @else
                        <div class="mt-1 text-sm text-gray-500">Chưa có gói tập đang hoạt động.</div>
                    @endif
                </div>
            </div>
            <x-stat-card icon="sparkles" color="amber" label="Điểm loyalty" :value="$stats['loyalty_balance']" />
            <x-stat-card
                icon="qr-code"
                color="emerald"
                label="Check-in tháng này"
                :value="$stats['checkins_this_month']"
                :hint="$stats['checked_in_today'] ? 'Đã check-in hôm nay' : 'Chưa check-in hôm nay'"
            />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 text-sm font-semibold text-gray-900">Lớp sắp tới của tôi</div>
                @if ($stats['upcoming_bookings']->isEmpty())
                    <x-empty-state icon="calendar" title="Chưa đặt lớp nào sắp tới">
                        <x-slot name="action">
                            <a href="{{ route('member.schedules.index') }}" class="text-sm text-indigo-600 font-medium hover:text-indigo-800">Xem lịch và đặt lớp &rarr;</a>
                        </x-slot>
                    </x-empty-state>
                @else
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($stats['upcoming_bookings'] as $booking)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-5 py-3 font-medium text-gray-900">{{ $booking->schedule->title }}</td>
                                    <td class="px-5 py-3 text-gray-500">{{ $booking->schedule->class_date->format('d/m/Y') }}, {{ $booking->schedule->start_time->format('H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
                <div class="flex items-center gap-2 mb-3">
                    <x-icon name="scale" class="w-5 h-5 text-gray-400" />
                    <span class="text-sm font-semibold text-gray-900">Tiến trình cơ thể</span>
                </div>
                @if ($stats['latest_measurement'])
                    <p class="text-sm text-gray-700">
                        Cân nặng gần nhất <strong>{{ $stats['latest_measurement']->weight }} kg</strong>,
                        BMI <strong>{{ $stats['latest_measurement']->bmi }}</strong>
                        ({{ $stats['latest_measurement']->measured_at->format('d/m/Y') }})
                    </p>
                    @if ($stats['previous_measurement'])
                        @php $diff = $stats['latest_measurement']->weight - $stats['previous_measurement']->weight; @endphp
                        <p class="text-xs text-gray-500 mt-1">
                            So với lần đo trước: {{ $diff > 0 ? '+' : '' }}{{ number_format($diff, 1) }} kg
                        </p>
                    @endif
                @else
                    <p class="text-sm text-gray-500">Chưa có dữ liệu đo chỉ số cơ thể.</p>
                @endif
            </div>
        </div>
    @endif
</x-app-layout>
