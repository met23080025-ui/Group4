<x-app-layout>
    <x-slot name="header">QR check-in của tôi</x-slot>

    <div class="max-w-md">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 flex flex-col items-center">
            @if ($token)
                <div class="p-3 bg-white border border-gray-100 rounded-lg">
                    {!! QrCode::size(220)->generate($token) !!}
                </div>
                <p class="mt-4 text-sm text-gray-500 text-center">
                    Đưa mã này cho nhân viên quét để check-in. Mã không chứa số điện thoại hay tên
                    của bạn — chỉ nhân viên Gym của bạn mới xác thực được.
                </p>
            @else
                <div class="w-56 h-56 flex items-center justify-center border border-dashed border-gray-300 rounded-lg text-sm text-gray-400 text-center px-4">
                    Tài khoản chưa có hồ sơ hội viên, chưa thể tạo mã check-in.
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
