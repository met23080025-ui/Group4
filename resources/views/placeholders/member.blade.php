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

        @if (auth()->user()->member)
            <div class="mt-4 flex flex-wrap gap-4 text-sm border-t border-gray-100 pt-4">
                <a href="{{ route('member.qr.show') }}" class="text-indigo-600 hover:text-indigo-900">QR check-in của tôi &rarr;</a>
                <a href="{{ route('members.measurements.index', auth()->user()->member) }}" class="text-indigo-600 hover:text-indigo-900">Chỉ số cơ thể của tôi &rarr;</a>
                <a href="{{ route('members.workout-plans.index', auth()->user()->member) }}" class="text-indigo-600 hover:text-indigo-900">Kế hoạch tập của tôi &rarr;</a>
                <a href="{{ route('members.nutrition-plans.index', auth()->user()->member) }}" class="text-indigo-600 hover:text-indigo-900">Dinh dưỡng của tôi &rarr;</a>
            </div>
        @endif
    </div>
</x-app-layout>
