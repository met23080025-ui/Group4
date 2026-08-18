@php
    $isStaffView = in_array(auth()->user()->role, [\App\Models\User::ROLE_GYM_OWNER, \App\Models\User::ROLE_STAFF], true);
@endphp
<x-app-layout>
    <x-slot name="header">Thanh toán {{ $payment->transaction_code }}</x-slot>

    <div class="mb-4">
        @if ($isStaffView)
            <a href="{{ route('gym.payments.index') }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; Quay lại danh sách thanh toán</a>
        @else
            <a href="{{ route('member.payments.index') }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; Quay lại thanh toán của tôi</a>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 max-w-4xl">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 flex flex-col items-center">
            <h3 class="text-base font-semibold text-gray-900 mb-3">Quét mã VietQR để chuyển khoản</h3>
            @if ($payment->qr_payload)
                <img src="{{ $payment->qr_payload }}" alt="VietQR" class="w-64 h-64 object-contain border border-gray-100 rounded-lg">
            @else
                <div class="w-64 h-64 flex items-center justify-center border border-dashed border-gray-300 rounded-lg text-sm text-gray-400 text-center px-4">
                    Chưa cấu hình tài khoản ngân hàng (VIETQR_BANK_BIN / VIETQR_ACCOUNT_NO trong .env)
                </div>
            @endif

            {{-- Dạng chữ, phòng khi ảnh QR không tải được lúc demo. --}}
            <dl class="mt-4 w-full max-w-xs text-sm border-t border-gray-100 pt-4 space-y-2">
                <div class="flex justify-between gap-2">
                    <dt class="text-gray-500">Ngân hàng (BIN)</dt>
                    <dd class="text-gray-900 font-mono">{{ config('services.vietqr.bank_bin') ?: '—' }}</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-gray-500">Số tài khoản</dt>
                    <dd class="text-gray-900 font-mono">{{ config('services.vietqr.account_no') ?: '—' }}</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-gray-500">Chủ tài khoản</dt>
                    <dd class="text-gray-900">{{ config('services.vietqr.account_name') ?: '—' }}</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-gray-500">Số tiền</dt>
                    <dd class="text-gray-900 font-semibold">{{ number_format($payment->amount, 0, ',', '.') }} đ</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-gray-500">Nội dung CK</dt>
                    <dd class="text-gray-900 font-mono font-semibold">{{ $payment->transaction_code }}</dd>
                </div>
            </dl>
            <p class="mt-3 text-xs text-gray-500 text-center">
                Nội dung chuyển khoản phải đúng: <strong>{{ $payment->transaction_code }}</strong> để đối chiếu tự động.
            </p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900">Thông tin thanh toán</h3>
                <span @class([
                    'px-2 py-0.5 rounded-full text-xs font-medium',
                    'bg-amber-100 text-amber-700' => $payment->status === 'pending',
                    'bg-emerald-100 text-emerald-700' => $payment->status === 'paid',
                    'bg-gray-100 text-gray-600' => in_array($payment->status, ['failed', 'cancelled']),
                ])>{{ $payment->status === 'pending' ? 'Chờ thanh toán' : $payment->status }}</span>
            </div>

            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="text-gray-500">Mã giao dịch (nội dung CK)</dt>
                    <dd class="mt-1 text-gray-900 font-mono">{{ $payment->transaction_code }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Số tiền</dt>
                    <dd class="mt-1 text-xl font-semibold text-gray-900">{{ number_format($payment->amount, 0, ',', '.') }} đ</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Hội viên</dt>
                    <dd class="mt-1 text-gray-900">{{ $payment->member->member_code }} — {{ $payment->member->user->name }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Gói tập</dt>
                    <dd class="mt-1 text-gray-900">{{ $payment->membership->package->name }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Phương thức</dt>
                    <dd class="mt-1 text-gray-900">Chuyển khoản ngân hàng</dd>
                </div>
            </dl>

            @if ($payment->status === 'pending')
                <div class="mt-6 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 text-sm">
                    @if ($isStaffView)
                        <p class="mb-3">Sau khi đối chiếu sao kê ngân hàng thấy đã nhận tiền, xác nhận thanh toán bên dưới.
                            Membership sẽ kích hoạt và hóa đơn được phát hành ngay.</p>
                        <form method="POST" action="{{ route('gym.payments.confirm', $payment) }}">
                            @csrf
                            <label for="note" class="block text-xs text-amber-700 mb-1">Ghi chú (tuỳ chọn)</label>
                            <textarea name="note" id="note" rows="2" class="w-full rounded-md border-amber-300 text-sm mb-2" placeholder="Vd: đối chiếu sao kê MBBank lúc 14:32"></textarea>
                            <button type="submit" class="rounded-md bg-emerald-600 px-4 py-2 text-white text-sm font-medium hover:bg-emerald-700">
                                Xác nhận đã nhận tiền
                            </button>
                        </form>
                    @else
                        Vui lòng chuyển khoản đúng số tiền và nội dung ở trên. Sau khi Gym xác nhận, membership của bạn
                        sẽ tự động kích hoạt.
                    @endif
                </div>
            @elseif ($payment->status === 'paid')
                <div class="mt-6 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm">
                    <p>Đã xác nhận thanh toán lúc {{ $payment->paid_at?->format('d/m/Y H:i') }}
                        @if ($payment->confirmedBy)bởi {{ $payment->confirmedBy->name }}@endif.
                        Membership đã kích hoạt.</p>
                    @if ($payment->invoice)
                        <p class="mt-1">Hóa đơn: <strong>{{ $payment->invoice->invoice_number }}</strong>
                            — {{ number_format($payment->invoice->total, 0, ',', '.') }} đ</p>
                    @endif
                    @if ($payment->note)
                        <p class="mt-1 text-xs text-emerald-700">Ghi chú: {{ $payment->note }}</p>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
