<x-app-layout>
    <x-slot name="header">Check-in hội viên</x-slot>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">{{ session('error') }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-3">Quét / nhập mã QR của hội viên</h3>
            <form method="POST" action="{{ route('gym.checkin.store') }}">
                @csrf
                <label for="token" class="block text-sm text-gray-600 mb-1">Mã token</label>
                <input type="text" name="token" id="token" autofocus
                    class="w-full rounded-md border-gray-300 text-sm font-mono"
                    placeholder="Quét mã bằng máy quét (tự điền) hoặc dán mã tại đây">
                @error('token')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
                <button type="submit" class="mt-3 rounded-md bg-indigo-600 px-4 py-2 text-white text-sm font-medium hover:bg-indigo-700">
                    Check-in
                </button>
            </form>
            <p class="mt-3 text-xs text-gray-500">
                Máy quét mã vạch/QR cầm tay hoạt động như bàn phím — chỉ cần focus vào ô trên và quét.
            </p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-x-auto">
            <div class="px-4 py-3 border-b border-gray-100 text-sm font-semibold text-gray-900">
                Đã check-in hôm nay ({{ $todayCheckIns->count() }})
            </div>
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Hội viên</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Giờ check-in</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Nguồn</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($todayCheckIns as $attendance)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $attendance->member->member_code }} — {{ $attendance->member->user->name }}</td>
                            <td class="px-4 py-3">{{ $attendance->check_in_time->format('H:i') }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $attendance->source === 'qr' ? 'QR' : 'Thủ công' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-6 text-center text-gray-500">Chưa có hội viên nào check-in hôm nay.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
