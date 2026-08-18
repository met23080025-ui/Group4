<x-app-layout>
    <x-slot name="header">Thanh toán của tôi</x-slot>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Mã giao dịch</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Gói</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Số tiền</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Trạng thái</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($payments as $payment)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $payment->transaction_code }}</td>
                        <td class="px-4 py-3">{{ $payment->membership->package->name }}</td>
                        <td class="px-4 py-3 font-semibold">{{ number_format($payment->amount, 0, ',', '.') }} đ</td>
                        <td class="px-4 py-3">
                            <span @class([
                                'px-2 py-0.5 rounded-full text-xs font-medium',
                                'bg-amber-100 text-amber-700' => $payment->status === 'pending',
                                'bg-emerald-100 text-emerald-700' => $payment->status === 'paid',
                                'bg-gray-100 text-gray-600' => in_array($payment->status, ['failed', 'cancelled']),
                            ])>{{ $payment->status === 'pending' ? 'Chờ thanh toán' : $payment->status }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('member.payments.show', $payment) }}" class="text-indigo-600 hover:text-indigo-900">Xem</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-500">Bạn chưa có thanh toán nào.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $payments->links() }}</div>
</x-app-layout>
