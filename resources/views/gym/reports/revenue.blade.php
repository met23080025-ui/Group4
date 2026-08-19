<x-app-layout>
    <x-slot name="header">Báo cáo doanh thu</x-slot>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-6">
        <form method="GET" action="{{ route('gym.reports.revenue') }}" class="flex flex-wrap items-end gap-3 text-sm">
            <div>
                <label class="block text-gray-600 mb-1">Từ ngày</label>
                <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="rounded-md border-gray-300 text-sm">
            </div>
            <div>
                <label class="block text-gray-600 mb-1">Đến ngày</label>
                <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="rounded-md border-gray-300 text-sm">
            </div>
            <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-white text-sm font-medium hover:bg-indigo-700">Lọc</button>
            @error('to')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
        </form>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <div class="text-sm text-gray-500">Tổng doanh thu ({{ \Illuminate\Support\Carbon::parse($report['from'])->format('d/m/Y') }} – {{ \Illuminate\Support\Carbon::parse($report['to'])->format('d/m/Y') }})</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($report['total'], 0, ',', '.') }} đ</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <div class="text-sm text-gray-500">Số hóa đơn</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $report['invoice_count'] }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-x-auto">
            <div class="px-4 py-3 border-b border-gray-100 text-sm font-semibold text-gray-900">Doanh thu theo tháng</div>
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <tbody class="divide-y divide-gray-100">
                    @forelse ($report['by_month'] as $month => $total)
                        <tr>
                            <td class="px-4 py-2 text-gray-700">{{ $month }}</td>
                            <td class="px-4 py-2 text-right font-medium text-gray-900">{{ number_format($total, 0, ',', '.') }} đ</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="px-4 py-6 text-center text-gray-500">Không có doanh thu trong khoảng thời gian này.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-x-auto">
            <div class="px-4 py-3 border-b border-gray-100 text-sm font-semibold text-gray-900">Doanh thu theo gói</div>
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <tbody class="divide-y divide-gray-100">
                    @forelse ($report['by_package'] as $package => $total)
                        <tr>
                            <td class="px-4 py-2 text-gray-700">{{ $package }}</td>
                            <td class="px-4 py-2 text-right font-medium text-gray-900">{{ number_format($total, 0, ',', '.') }} đ</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="px-4 py-6 text-center text-gray-500">Không có doanh thu trong khoảng thời gian này.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
