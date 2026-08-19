<x-app-layout>
    <x-slot name="header">Tổng quan Gym</x-slot>

    <div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <div class="text-sm text-gray-500">Tổng hội viên</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $total_members }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <div class="text-sm text-gray-500">Hội viên active</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $active_members }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <div class="text-sm text-gray-500">Chưa có gói còn hạn</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $members_without_valid_membership }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <div class="text-sm text-gray-500">Doanh thu tháng này</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($revenue_this_month, 0, ',', '.') }} đ</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <div class="text-sm text-gray-500">Membership mới tháng này</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $new_memberships_this_month }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <div class="text-sm text-gray-500">Check-in hôm nay</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $checkins_today }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <div class="text-sm text-gray-500">Thanh toán chờ xác nhận</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $pending_payments }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <div class="text-sm text-gray-500">Membership sắp hết hạn (7 ngày)</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $expiring_memberships }}</div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-x-auto mb-6">
        <div class="px-4 py-3 border-b border-gray-100 text-sm font-semibold text-gray-900">Lớp sắp tới</div>
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <tbody class="divide-y divide-gray-100">
                @forelse ($upcoming_schedules as $schedule)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $schedule->title }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $schedule->class_date->format('d/m/Y') }}, {{ $schedule->start_time->format('H:i') }}</td>
                    </tr>
                @empty
                    <tr><td class="px-4 py-6 text-center text-gray-500">Chưa có lớp nào sắp diễn ra.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="flex flex-wrap gap-4 text-sm">
        <a href="{{ route('gym.reports.revenue') }}" class="text-indigo-600 hover:text-indigo-900">Báo cáo doanh thu &rarr;</a>
        <a href="{{ route('community.index') }}" class="text-indigo-600 hover:text-indigo-900">Cộng đồng Gym &rarr;</a>
        <a href="{{ route('reviews.index') }}" class="text-indigo-600 hover:text-indigo-900">Đánh giá &rarr;</a>
    </div>
</x-app-layout>
