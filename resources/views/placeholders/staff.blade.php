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

        <div class="mt-4 flex flex-wrap gap-4 text-sm border-t border-gray-100 pt-4">
            <a href="{{ route('community.index') }}" class="text-indigo-600 hover:text-indigo-900">Cộng đồng Gym &rarr;</a>
            <a href="{{ route('reviews.index') }}" class="text-indigo-600 hover:text-indigo-900">Đánh giá &rarr;</a>
        </div>
    </div>
</x-app-layout>
