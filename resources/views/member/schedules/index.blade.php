<x-app-layout>
    <x-slot name="header">Lớp sắp diễn ra</x-slot>

    <div class="mb-4 flex items-center justify-between">
        <div class="text-sm text-gray-500">{{ $schedules->total() }} lớp sắp diễn ra</div>
        <a href="{{ route('member.bookings.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900">Booking của tôi &rarr;</a>
    </div>

    @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Tiêu đề</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Ngày / giờ</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Trainer</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Loại</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Chỗ trống</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($schedules as $schedule)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $schedule->title }}</td>
                        <td class="px-4 py-3">{{ $schedule->class_date->format('d/m/Y') }}, {{ $schedule->start_time->format('H:i') }}–{{ $schedule->end_time->format('H:i') }}</td>
                        <td class="px-4 py-3">{{ $schedule->trainer?->user->name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @if ($schedule->is_pt_session)
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">Buổi PT</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Lớp nhóm</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $schedule->capacity - $schedule->booked_count }}/{{ $schedule->capacity }}</td>
                        <td class="px-4 py-3 text-right">
                            @if (in_array($schedule->id, $myBookedScheduleIds, true))
                                <span class="text-xs text-emerald-700 font-medium">Đã đặt</span>
                            @elseif ($schedule->booked_count >= $schedule->capacity)
                                <span class="text-xs text-gray-400">Hết chỗ</span>
                            @else
                                <form method="POST" action="{{ route('member.schedules.book', $schedule) }}">
                                    @csrf
                                    <button type="submit" class="text-indigo-600 hover:text-indigo-900 text-sm">Đặt lớp</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-gray-500">Chưa có lớp nào sắp diễn ra.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $schedules->links() }}</div>
</x-app-layout>
