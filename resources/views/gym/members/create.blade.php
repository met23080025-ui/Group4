<x-app-layout>
    <x-slot name="header">Thêm hội viên</x-slot>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 max-w-3xl">
        <form method="POST" action="{{ route('gym.members.store') }}">
            @csrf

            @include('gym.members._form')

            <div class="mt-6 flex items-center gap-3">
                <x-primary-button>Tạo hội viên</x-primary-button>
                <a href="{{ route('gym.members.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Hủy</a>
            </div>
        </form>
    </div>
</x-app-layout>
