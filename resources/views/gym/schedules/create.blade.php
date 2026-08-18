<x-app-layout>
    <x-slot name="header">Thêm lớp tập</x-slot>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 max-w-2xl">
        <form method="POST" action="{{ route('gym.schedules.store') }}">
            @csrf
            @include('gym.schedules._form')

            <div class="mt-6 flex items-center gap-3">
                <x-primary-button>Tạo lớp</x-primary-button>
                <a href="{{ route('gym.schedules.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Hủy</a>
            </div>
        </form>
    </div>
</x-app-layout>
