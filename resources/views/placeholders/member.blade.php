<x-app-layout>
    <x-slot name="header">Trang chủ</x-slot>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <p class="text-gray-700">
            Xin chào, <strong>{{ auth()->user()->name }}</strong>. Chào mừng bạn đến với cộng đồng
            <strong>{{ auth()->user()->gym?->name }}</strong> trên GymHub.
        </p>
        <p class="mt-3 text-sm text-gray-500">
            Membership, lịch tập, cộng đồng Gym... sẽ được kích hoạt sau khi bạn chọn gói tập ở bước tiếp theo.
        </p>
    </div>
</x-app-layout>
