<x-app-layout>
    <x-slot name="header">Lớp: {{ $schedule->title }}</x-slot>

    <div class="mb-4">
        <a href="{{ route('gym.schedules.index') }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; Quay lại danh sách lớp</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 max-w-4xl">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900">Thông tin lớp</h3>
                <a href="{{ route('gym.schedules.edit', $schedule) }}" class="text-sm text-indigo-600 hover:text-indigo-900">Sửa</a>
            </div>

            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="text-gray-500">Loại</dt>
                    <dd class="mt-1 text-gray-900">{{ $schedule->is_pt_session ? 'Buổi PT 1-kèm-1' : 'Lớp nhóm' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Ngày / giờ</dt>
                    <dd class="mt-1 text-gray-900">{{ $schedule->class_date->format('d/m/Y') }}, {{ $schedule->start_time->format('H:i') }}–{{ $schedule->end_time->format('H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Trainer phụ trách</dt>
                    <dd class="mt-1 text-gray-900">{{ $schedule->trainer?->user->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Sức chứa</dt>
                    <dd class="mt-1 text-gray-900">{{ $schedule->bookedCount() }}/{{ $schedule->capacity }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Trạng thái</dt>
                    <dd class="mt-1 text-gray-900">{{ $schedule->status }}</dd>
                </div>
            </dl>

            <form method="POST" action="{{ route('gym.schedules.destroy', $schedule) }}" class="mt-6">
                @csrf
                @method('DELETE')
                <x-danger-button type="submit">Xóa lớp</x-danger-button>
            </form>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4">Hội viên đã đặt chỗ ({{ $schedule->classBookings->count() }})</h3>

            <ul class="divide-y divide-gray-100 text-sm">
                @forelse ($schedule->classBookings as $booking)
                    <li class="py-2">{{ $booking->member->member_code }} — {{ $booking->member->user->name }}</li>
                @empty
                    <li class="py-2 text-gray-500">Chưa có ai đặt chỗ.</li>
                @endforelse
            </ul>
        </div>
    </div>
</x-app-layout>
