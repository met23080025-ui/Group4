<x-app-layout>
    <x-slot name="header">Tổng quan huấn luyện viên</x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-6">
        <x-stat-card icon="calendar" color="indigo" label="Lớp hôm nay" :value="$todaySchedules->count()" />
        <x-stat-card icon="users" color="sky" label="Học viên được phân công" :value="$assignedMembers->count()" />
        <x-stat-card icon="check-badge" color="emerald" label="Số buổi đã dạy" :value="$sessionsTaughtCount" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 text-sm font-semibold text-gray-900">Lịch dạy hôm nay</div>
            @if ($todaySchedules->isEmpty())
                <x-empty-state icon="calendar" title="Không có lớp nào hôm nay" description="Bạn có thể nghỉ ngơi hoặc chuẩn bị giáo án cho các buổi sắp tới." />
            @else
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($todaySchedules as $schedule)
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3 font-medium text-gray-900">{{ $schedule->title }}</td>
                                <td class="px-5 py-3 text-gray-500">{{ $schedule->start_time->format('H:i') }} - {{ $schedule->end_time->format('H:i') }}</td>
                                <td class="px-5 py-3 text-gray-500">{{ $schedule->is_pt_session ? 'Buổi PT' : 'Lớp nhóm' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 text-sm font-semibold text-gray-900">Lớp sắp tới</div>
            @if ($upcomingSchedules->isEmpty())
                <x-empty-state icon="calendar" title="Chưa có lớp nào sắp tới" />
            @else
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($upcomingSchedules as $schedule)
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3 font-medium text-gray-900">{{ $schedule->title }}</td>
                                <td class="px-5 py-3 text-gray-500">{{ $schedule->class_date->format('d/m/Y') }}, {{ $schedule->start_time->format('H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <div class="mt-6 bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 text-sm font-semibold text-gray-900">Học viên được phân công cho tôi</div>
        @if ($assignedMembers->isEmpty())
            <x-empty-state icon="users" title="Chưa có hội viên nào được phân công cho bạn" description="Chủ Gym hoặc nhân viên sẽ gán hội viên cho bạn." />
        @else
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left font-medium text-gray-500">Hội viên</th>
                        <th class="px-5 py-3 text-left font-medium text-gray-500">Trạng thái</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($assignedMembers as $member)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-medium text-gray-900">{{ $member->member_code }} - {{ $member->user->name }}</td>
                            <td class="px-5 py-3"><x-status-badge :status="$member->status" /></td>
                            <td class="px-5 py-3 text-right space-x-3">
                                <a href="{{ route('members.measurements.index', $member) }}" class="text-indigo-600 hover:text-indigo-900">Chỉ số cơ thể</a>
                                <a href="{{ route('members.workout-plans.index', $member) }}" class="text-indigo-600 hover:text-indigo-900">Kế hoạch tập</a>
                                <a href="{{ route('members.nutrition-plans.index', $member) }}" class="text-indigo-600 hover:text-indigo-900">Dinh dưỡng</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</x-app-layout>
