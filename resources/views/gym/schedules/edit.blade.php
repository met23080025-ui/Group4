<x-app-layout>
    <x-slot name="header">Sửa lớp: {{ $schedule->title }}</x-slot>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 max-w-2xl">
        <form method="POST" action="{{ route('gym.schedules.update', $schedule) }}">
            @csrf
            @method('PUT')
            @include('gym.schedules._form')

            <div class="mt-6 flex items-center gap-3">
                <x-primary-button>Lưu thay đổi</x-primary-button>
                <a href="{{ route('gym.schedules.show', $schedule) }}" class="text-sm text-gray-600 hover:text-gray-900">Hủy</a>
            </div>
        </form>
    </div>
</x-app-layout>
