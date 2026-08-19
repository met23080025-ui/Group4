<x-app-layout>
    <x-slot name="header">Tổng quan huấn luyện viên</x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <div class="text-sm text-gray-500">Lớp hôm nay</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $todaySchedules->count() }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <div class="text-sm text-gray-500">Học viên được phân công</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $assignedMembers->count() }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <div class="text-sm text-gray-500">Số buổi đã dạy</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $sessionsTaughtCount }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-x-auto">
            <div class="px-4 py-3 border-b border-gray-100 text-sm font-semibold text-gray-900">Lịch dạy hôm nay</div>
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <tbody class="divide-y divide-gray-100">
                    @forelse ($todaySchedules as $schedule)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $schedule->title }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $schedule->start_time->format('H:i') }}–{{ $schedule->end_time->format('H:i') }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $schedule->is_pt_session ? 'Buổi PT' : 'Lớp nhóm' }}</td>
                        </tr>
                    @empty
                        <tr><td class="px-4 py-6 text-center text-gray-500">Không có lớp nào hôm nay.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-x-auto">
            <div class="px-4 py-3 border-b border-gray-100 text-sm font-semibold text-gray-900">Lớp sắp tới</div>
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <tbody class="divide-y divide-gray-100">
                    @forelse ($upcomingSchedules as $schedule)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $schedule->title }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $schedule->class_date->format('d/m/Y') }}, {{ $schedule->start_time->format('H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td class="px-4 py-6 text-center text-gray-500">Chưa có lớp nào sắp tới.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6 bg-white rounded-xl border border-gray-200 shadow-sm overflow-x-auto">
        <div class="px-4 py-3 border-b border-gray-100 text-sm font-semibold text-gray-900">Học viên được phân công cho tôi</div>
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Hội viên</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Trạng thái</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($assignedMembers as $member)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $member->member_code }} — {{ $member->user->name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $member->status }}</td>
                        <td class="px-4 py-3 text-right space-x-3">
                            <a href="{{ route('members.measurements.index', $member) }}" class="text-indigo-600 hover:text-indigo-900">Chỉ số cơ thể</a>
                            <a href="{{ route('members.workout-plans.index', $member) }}" class="text-indigo-600 hover:text-indigo-900">Kế hoạch tập</a>
                            <a href="{{ route('members.nutrition-plans.index', $member) }}" class="text-indigo-600 hover:text-indigo-900">Dinh dưỡng</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-6 text-center text-gray-500">Chưa có hội viên nào được phân công cho bạn.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4 text-sm">
        <a href="{{ route('community.index') }}" class="text-indigo-600 hover:text-indigo-900">Cộng đồng Gym &rarr;</a>
    </div>
</x-app-layout>
