<x-app-layout>
    <x-slot name="header">Sửa gói tập {{ $package->name }}</x-slot>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 max-w-2xl">
        <form method="POST" action="{{ route('gym.packages.update', $package) }}">
            @csrf
            @method('PUT')
            @include('gym.packages._form')

            <div class="mt-6 flex items-center gap-3">
                <x-primary-button>Lưu thay đổi</x-primary-button>
                <a href="{{ route('gym.packages.show', $package) }}" class="text-sm text-gray-600 hover:text-gray-900">Hủy</a>
            </div>
        </form>
    </div>
</x-app-layout>
