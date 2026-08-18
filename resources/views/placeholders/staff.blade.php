<x-app-layout>
    <x-slot name="header">Tổng quan nhân viên</x-slot>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <p class="text-gray-700">
            Xin chào, <strong>{{ auth()->user()->name }}</strong>. Đây là trang tổng quan dành cho
            <strong>Nhân viên</strong> của <strong>{{ auth()->user()->gym?->name }}</strong>.
        </p>
        <p class="mt-3 text-sm text-gray-500">
            Check-in hôm nay, thanh toán chờ xác nhận, lịch tập... sẽ được xây dựng trong các khối tiếp theo.
        </p>
    </div>
</x-app-layout>
