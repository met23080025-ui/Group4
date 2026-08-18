<x-app-layout>
    <x-slot name="header">Thêm khuyến mãi</x-slot>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 max-w-2xl">
        <form method="POST" action="{{ route('gym.promotions.store') }}">
            @csrf
            @include('gym.promotions._form')

            <div class="mt-6 flex items-center gap-3">
                <x-primary-button>Tạo khuyến mãi</x-primary-button>
                <a href="{{ route('gym.promotions.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Hủy</a>
            </div>
        </form>
    </div>
</x-app-layout>
