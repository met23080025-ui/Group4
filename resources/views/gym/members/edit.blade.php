<x-app-layout>
    <x-slot name="header">Sửa hội viên {{ $member->member_code }}</x-slot>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 max-w-3xl">
        <form method="POST" action="{{ route('gym.members.update', $member) }}">
            @csrf
            @method('PUT')

            @include('gym.members._form')

            <div class="mt-6 flex items-center gap-3">
                <x-primary-button>Lưu thay đổi</x-primary-button>
                <a href="{{ route('gym.members.show', $member) }}" class="text-sm text-gray-600 hover:text-gray-900">Hủy</a>
            </div>
        </form>
    </div>
</x-app-layout>
