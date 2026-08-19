@php
    $user = auth()->user();
    $isModerator = in_array($user->role, [\App\Models\User::ROLE_GYM_OWNER, \App\Models\User::ROLE_STAFF], true);
    $isTrainer = $user->role === \App\Models\User::ROLE_TRAINER;
    $isMember = $user->role === \App\Models\User::ROLE_MEMBER;
@endphp
<x-app-layout>
    <x-slot name="header">Đánh giá & Xếp hạng</x-slot>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif

    @if ($isMember)
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-6">
            <h3 class="text-base font-semibold text-gray-900 mb-3">Viết đánh giá</h3>
            <form method="POST" action="{{ route('reviews.store') }}" class="space-y-3 text-sm">
                @csrf
                <div>
                    <label class="block text-gray-600 mb-1">Đối tượng đánh giá</label>
                    <select name="trainer_id" class="rounded-md border-gray-300 text-sm">
                        <option value="">Gym (chung)</option>
                        @foreach ($trainers as $trainer)
                            <option value="{{ $trainer->id }}">PT {{ $trainer->user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-gray-600 mb-1">Số sao (1-5)</label>
                    <select name="rating" class="rounded-md border-gray-300 text-sm">
                        @for ($i = 5; $i >= 1; $i--)
                            <option value="{{ $i }}">{{ str_repeat('⭐', $i) }} ({{ $i }})</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label class="block text-gray-600 mb-1">Nhận xét</label>
                    <textarea name="comment" rows="2" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                </div>
                @error('trainer_id')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                @error('rating')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-white text-sm font-medium hover:bg-indigo-700">Gửi đánh giá</button>
            </form>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-x-auto mb-6">
            <div class="px-4 py-3 border-b border-gray-100 text-sm font-semibold text-gray-900">Đánh giá của tôi</div>
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <tbody class="divide-y divide-gray-100">
                    @forelse ($myReviews as $review)
                        <tr>
                            <td class="px-4 py-3">{{ $review->trainer?->user->name ?? 'Gym (chung)' }}</td>
                            <td class="px-4 py-3">{{ str_repeat('⭐', $review->rating) }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $review->comment }}</td>
                            <td class="px-4 py-3 text-xs text-gray-400">{{ $review->is_visible ? '' : '(đang bị ẩn)' }}</td>
                        </tr>
                    @empty
                        <tr><td class="px-4 py-6 text-center text-gray-500">Bạn chưa viết đánh giá nào.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-x-auto">
        <div class="px-4 py-3 border-b border-gray-100 text-sm font-semibold text-gray-900">
            @if ($isModerator) Tất cả đánh giá (kiểm duyệt)
            @elseif ($isTrainer) Đánh giá về tôi
            @else Đánh giá công khai
            @endif
        </div>
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Hội viên</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Đối tượng</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Sao</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Nhận xét</th>
                    @if ($isModerator)<th class="px-4 py-3"></th>@endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($reviews as $review)
                    <tr class="{{ $review->is_visible ? '' : 'opacity-50' }}">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $review->member->user->name }}</td>
                        <td class="px-4 py-3">{{ $review->trainer?->user->name ?? 'Gym (chung)' }}</td>
                        <td class="px-4 py-3">{{ str_repeat('⭐', $review->rating) }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $review->comment }}</td>
                        @if ($isModerator)
                            <td class="px-4 py-3 text-right">
                                <form method="POST" action="{{ route('reviews.toggle-visibility', $review) }}">
                                    @csrf
                                    <button type="submit" class="text-xs text-indigo-600 hover:text-indigo-900">
                                        {{ $review->is_visible ? 'Ẩn' : 'Hiện lại' }}
                                    </button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $isModerator ? 5 : 4 }}" class="px-4 py-6 text-center text-gray-500">Chưa có đánh giá nào.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $reviews->links() }}</div>
</x-app-layout>
