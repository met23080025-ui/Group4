<x-app-layout>
    <x-slot name="header">Tổng quan nền tảng (Platform Admin)</x-slot>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <p class="text-gray-700">
            Xin chào, <strong>{{ auth()->user()->name }}</strong>. Đây là trang tổng quan dành cho
            <strong>Platform Admin</strong> — nơi quản lý toàn bộ các Gym/tenant trên nền tảng GymHub.
        </p>
        <p class="mt-3 text-sm text-gray-500">
            Dashboard đầy đủ (danh sách Gym, thống kê toàn nền tảng, kích hoạt/vô hiệu hóa Gym) sẽ được xây dựng ở Ngày 3.
        </p>
    </div>
</x-app-layout>
