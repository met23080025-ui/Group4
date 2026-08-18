<x-app-layout>
    <x-slot name="header">Thêm gói tập</x-slot>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 max-w-2xl">
        <form method="POST" action="{{ route('gym.packages.store') }}">
            @csrf
            @include('gym.packages._form')

            <div class="mt-6 flex items-center gap-3">
                <x-primary-button>Tạo gói tập</x-primary-button>
                <a href="{{ route('gym.packages.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Hủy</a>
            </div>
        </form>
    </div>
</x-app-layout>
