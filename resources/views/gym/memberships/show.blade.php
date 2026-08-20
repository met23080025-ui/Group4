<x-app-layout>
    <x-slot name="header">Đăng ký gói tập #{{ $membership->id }}</x-slot>

    <div class="mb-4">
        <a href="{{ route('gym.memberships.index') }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; Quay lại danh sách đăng ký gói tập</a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 max-w-2xl">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-semibold text-gray-900">
                {{ $membership->member->member_code }} - {{ $membership->member->user->name }}
            </h3>
            <x-status-badge :status="$membership->status" />
        </div>

        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div>
                <dt class="text-gray-500">Gói tập</dt>
                <dd class="mt-1 text-gray-900">{{ $membership->package->name }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Khuyến mãi</dt>
                <dd class="mt-1 text-gray-900">{{ $membership->promotion?->code ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Ngày bắt đầu</dt>
                <dd class="mt-1 text-gray-900">{{ $membership->start_date->format('d/m/Y') }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Ngày kết thúc</dt>
                <dd class="mt-1 text-gray-900">{{ $membership->end_date->format('d/m/Y') }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Giá gốc</dt>
                <dd class="mt-1 text-gray-900">{{ number_format($membership->original_price, 0, ',', '.') }} đ</dd>
            </div>
            <div>
                <dt class="text-gray-500">Giảm giá</dt>
                <dd class="mt-1 text-gray-900">{{ number_format($membership->discount_amount, 0, ',', '.') }} đ</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-gray-500">Thành tiền cần thanh toán</dt>
                <dd class="mt-1 text-xl font-semibold text-gray-900">{{ number_format($membership->final_price, 0, ',', '.') }} đ</dd>
            </div>
            <div>
                <dt class="text-gray-500">Số buổi PT còn lại</dt>
                <dd class="mt-1 text-gray-900">{{ $membership->remaining_pt_sessions }}</dd>
            </div>
        </dl>

        @if ($membership->status === 'pending')
            <div class="mt-6 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 text-sm">
                @if ($latestPayment)
                    Đăng ký gói tập đang chờ thanh toán. Đã tạo thanh toán <strong>{{ $latestPayment->transaction_code }}</strong>.
                    <a href="{{ route('gym.payments.show', $latestPayment) }}" class="underline font-medium">Xem QR &amp; trạng thái</a>.
                @else
                    Đăng ký gói tập đang chờ thanh toán, chưa tạo yêu cầu thanh toán nào.
                    <form method="POST" action="{{ route('gym.memberships.payment.store', $membership) }}" class="inline">
                        @csrf
                        <button type="submit" class="ml-1 underline font-medium">Tạo thanh toán (sinh QR VietQR)</button>
                    </form>
                @endif
            </div>
        @endif
    </div>
</x-app-layout>
