<x-app-layout>
    <x-slot name="header">Lớp tập</x-slot>

    <div class="flex items-center justify-between mb-4">
        <div class="text-sm text-gray-500">{{ $schedules->total() }} lớp</div>
        <a href="{{ route('gym.schedules.create') }}"
            class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm rounded-lg hover:bg-gray-700">
            + Thêm lớp
        </a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Tiêu đề</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Ngày</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Giờ</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Trainer</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Loại</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Capacity</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Trạng thái</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($schedules as $schedule)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $schedule->title }}</td>
                        <td class="px-4 py-3">{{ $schedule->class_date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">{{ $schedule->start_time->format('H:i') }}–{{ $schedule->end_time->format('H:i') }}</td>
                        <td class="px-4 py-3">{{ $schedule->trainer?->user->name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @if ($schedule->is_pt_session)
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">Buổi PT</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Lớp nhóm</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $schedule->bookedCount() }}/{{ $schedule->capacity }}</td>
                        <td class="px-4 py-3">
                            <span @class([
                                'px-2 py-0.5 rounded-full text-xs font-medium',
                                'bg-emerald-100 text-emerald-700' => $schedule->status === 'scheduled',
                                'bg-gray-100 text-gray-600' => $schedule->status !== 'scheduled',
                            ])>{{ $schedule->status }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('gym.schedules.show', $schedule) }}" class="text-indigo-600 hover:text-indigo-900">Xem</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-6 text-center text-gray-500">Chưa có lớp nào.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $schedules->links() }}</div>
</x-app-layout>
