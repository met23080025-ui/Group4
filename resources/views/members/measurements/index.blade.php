<x-app-layout>
    <x-slot name="header">Chỉ số cơ thể — {{ $member->member_code }}</x-slot>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-3">Nhập chỉ số mới</h3>
            <form method="POST" action="{{ route('members.measurements.store', $member) }}" class="space-y-3 text-sm">
                @csrf
                <div>
                    <label class="block text-gray-600 mb-1">Ngày đo</label>
                    <input type="date" name="measured_at" value="{{ now()->toDateString() }}" class="w-full rounded-md border-gray-300 text-sm">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-gray-600 mb-1">Chiều cao (cm)</label>
                        <input type="number" step="0.1" name="height" required class="w-full rounded-md border-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-gray-600 mb-1">Cân nặng (kg)</label>
                        <input type="number" step="0.1" name="weight" required class="w-full rounded-md border-gray-300 text-sm">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-gray-600 mb-1">% mỡ cơ thể</label>
                        <input type="number" step="0.1" name="body_fat_percent" class="w-full rounded-md border-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-gray-600 mb-1">Khối cơ (kg)</label>
                        <input type="number" step="0.1" name="muscle_mass" class="w-full rounded-md border-gray-300 text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-gray-600 mb-1">Ghi chú</label>
                    <textarea name="notes" rows="2" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                </div>
                @error('height')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                @error('weight')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-white text-sm font-medium hover:bg-indigo-700">Lưu</button>
            </form>
        </div>

        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 shadow-sm overflow-x-auto">
            <div class="px-4 py-3 border-b border-gray-100 text-sm font-semibold text-gray-900">Lịch sử đo</div>
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Ngày</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Cao / Nặng</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">BMI</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">% mỡ</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Khối cơ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($measurements as $m)
                        <tr>
                            <td class="px-4 py-3">{{ $m->measured_at->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">{{ $m->height }} cm / {{ $m->weight }} kg</td>
                            <td class="px-4 py-3 font-semibold">{{ $m->bmi }}</td>
                            <td class="px-4 py-3">{{ $m->body_fat_percent ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $m->muscle_mass ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">Chưa có dữ liệu đo.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
