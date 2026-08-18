<x-app-layout>
    <x-slot name="header">Thanh toán chờ xử lý</x-slot>

    <div class="text-sm text-gray-500 mb-4">{{ $payments->total() }} thanh toán đang chờ</div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Mã giao dịch</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Hội viên</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Gói</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Số tiền</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Tạo lúc</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($payments as $payment)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $payment->transaction_code }}</td>
                        <td class="px-4 py-3">{{ $payment->member->member_code }} — {{ $payment->member->user->name }}</td>
                        <td class="px-4 py-3">{{ $payment->membership->package->name }}</td>
                        <td class="px-4 py-3 font-semibold">{{ number_format($payment->amount, 0, ',', '.') }} đ</td>
                        <td class="px-4 py-3">{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('gym.payments.show', $payment) }}" class="text-indigo-600 hover:text-indigo-900">Xem QR</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-gray-500">Không có thanh toán nào đang chờ.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $payments->links() }}</div>
</x-app-layout>
