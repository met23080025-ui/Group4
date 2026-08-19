<x-app-layout>
    <x-slot name="header">Trang chủ</x-slot>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-6">
        <p class="text-gray-700">
            Xin chào, <strong>{{ auth()->user()->name }}</strong>. Chào mừng bạn đến với cộng đồng
            <strong>{{ auth()->user()->gym?->name }}</strong> trên GymHub.
        </p>
    </div>

    @if (! $member)
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 text-sm text-gray-500">
            Tài khoản của bạn chưa có hồ sơ hội viên. Membership, lịch tập... sẽ được kích hoạt sau khi bạn chọn gói tập.
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 sm:col-span-2">
                <div class="text-sm text-gray-500">Membership hiện tại</div>
                @if ($stats['current_membership'])
                    <div class="mt-1 text-lg font-semibold text-gray-900">{{ $stats['current_membership']->package->name }}</div>
                    <div class="text-sm text-gray-600 mt-1">Còn <strong>{{ $stats['days_remaining'] }}</strong> ngày (hết hạn {{ $stats['current_membership']->end_date->format('d/m/Y') }})</div>
                @else
                    <div class="mt-1 text-sm text-gray-500">Chưa có gói tập đang hoạt động.</div>
                @endif
            </div>
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <div class="text-sm text-gray-500">Điểm loyalty</div>
                <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $stats['loyalty_balance'] }}</div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <div class="text-sm text-gray-500">Check-in tháng này</div>
                <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $stats['checkins_this_month'] }}</div>
                <div class="text-xs {{ $stats['checked_in_today'] ? 'text-emerald-600' : 'text-gray-400' }} mt-1">
                    {{ $stats['checked_in_today'] ? 'Đã check-in hôm nay' : 'Chưa check-in hôm nay' }}
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-x-auto">
                <div class="px-4 py-3 border-b border-gray-100 text-sm font-semibold text-gray-900">Lớp sắp tới của tôi</div>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($stats['upcoming_bookings'] as $booking)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $booking->schedule->title }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ $booking->schedule->class_date->format('d/m/Y') }}, {{ $booking->schedule->start_time->format('H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="px-4 py-6 text-center text-gray-500">Chưa đặt lớp nào sắp tới.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <div class="text-sm font-semibold text-gray-900 mb-2">Tiến trình cơ thể</div>
                @if ($stats['latest_measurement'])
                    <p class="text-sm text-gray-700">
                        Cân nặng gần nhất: <strong>{{ $stats['latest_measurement']->weight }} kg</strong> —
                        BMI: <strong>{{ $stats['latest_measurement']->bmi }}</strong>
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

        <div class="flex flex-wrap gap-4 text-sm border-t border-gray-100 pt-4">
            <a href="{{ route('member.qr.show') }}" class="text-indigo-600 hover:text-indigo-900">QR check-in của tôi &rarr;</a>
            <a href="{{ route('members.measurements.index', $member) }}" class="text-indigo-600 hover:text-indigo-900">Chỉ số cơ thể của tôi &rarr;</a>
            <a href="{{ route('members.workout-plans.index', $member) }}" class="text-indigo-600 hover:text-indigo-900">Kế hoạch tập của tôi &rarr;</a>
            <a href="{{ route('members.nutrition-plans.index', $member) }}" class="text-indigo-600 hover:text-indigo-900">Dinh dưỡng của tôi &rarr;</a>
            <a href="{{ route('community.index') }}" class="text-indigo-600 hover:text-indigo-900">Cộng đồng Gym &rarr;</a>
            <a href="{{ route('reviews.index') }}" class="text-indigo-600 hover:text-indigo-900">Đánh giá &rarr;</a>
        </div>
    @endif
</x-app-layout>
