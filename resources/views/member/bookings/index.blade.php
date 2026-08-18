<x-app-layout>
    <x-slot name="header">Booking của tôi</x-slot>

    <div class="mb-4">
        <a href="{{ route('member.schedules.index') }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; Xem lớp sắp diễn ra</a>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Lớp</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Ngày / giờ</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Trạng thái</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($bookings as $booking)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $booking->schedule->title }}</td>
                        <td class="px-4 py-3">{{ $booking->schedule->class_date->format('d/m/Y') }}, {{ $booking->schedule->start_time->format('H:i') }}–{{ $booking->schedule->end_time->format('H:i') }}</td>
                        <td class="px-4 py-3">
                            <span @class([
                                'px-2 py-0.5 rounded-full text-xs font-medium',
                                'bg-emerald-100 text-emerald-700' => $booking->status === 'booked',
                                'bg-gray-100 text-gray-600' => $booking->status !== 'booked',
                            ])>{{ $booking->status === 'booked' ? 'Đã đặt' : 'Đã huỷ' }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if ($booking->status === 'booked')
                                <form method="POST" action="{{ route('member.bookings.destroy', $booking) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900 text-sm">Huỷ</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-gray-500">Bạn chưa đặt lớp nào.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $bookings->links() }}</div>
</x-app-layout>
