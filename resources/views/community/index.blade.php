@php
    $user = auth()->user();
    $reactionLabels = ['like' => '👍 Thích', 'love' => '❤️ Yêu thích', 'wow' => '😮 Wow'];
    $typeLabels = ['post' => 'Bài viết', 'announcement' => 'Thông báo', 'event' => 'Sự kiện', 'challenge' => 'Thử thách'];
@endphp
<x-app-layout>
    <x-slot name="header">Cộng đồng Gym</x-slot>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">{{ session('error') }}</div>
    @endif

    @can('create', \App\Models\Post::class)
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-6">
            <h3 class="text-base font-semibold text-gray-900 mb-3">Đăng bài mới</h3>
            <form method="POST" action="{{ route('community.store') }}" class="space-y-3 text-sm">
                @csrf
                <div>
                    <label class="block text-gray-600 mb-1">Loại</label>
                    <select name="type" class="rounded-md border-gray-300 text-sm">
                        @foreach ($typeLabels as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-gray-600 mb-1">Nội dung</label>
                    <textarea name="content" rows="3" required class="w-full rounded-md border-gray-300 text-sm"></textarea>
                    @error('content')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-white text-sm font-medium hover:bg-indigo-700">Đăng</button>
            </form>
        </div>
    @endcan

    <div class="space-y-4">
        @forelse ($posts as $post)
            <div class="bg-white rounded-xl border {{ $post->is_pinned ? 'border-indigo-300 ring-1 ring-indigo-100' : 'border-gray-200' }} shadow-sm p-6">
                <div class="flex items-start justify-between mb-2">
                    <div>
                        @if ($post->is_pinned)
                            <span class="inline-block mb-1 px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">📌 Đã ghim</span>
                        @endif
                        <span class="inline-block mb-1 px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">{{ $typeLabels[$post->type] ?? $post->type }}</span>
                        <div class="text-sm font-semibold text-gray-900">{{ $post->user->name }}</div>
                        <div class="text-xs text-gray-500">{{ $post->published_at?->format('d/m/Y H:i') }}</div>
                    </div>
                    <div class="flex items-center gap-3 text-sm">
                        @can('pin', $post)
                            <form method="POST" action="{{ route('community.pin', $post) }}">
                                @csrf
                                <button type="submit" class="text-gray-500 hover:text-indigo-600">{{ $post->is_pinned ? 'Gỡ ghim' : 'Ghim' }}</button>
                            </form>
                        @endcan
                        @can('delete', $post)
                            <form method="POST" action="{{ route('community.destroy', $post) }}" onsubmit="return confirm('Xoá bài viết này?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700">Xoá</button>
                            </form>
                        @endcan
                    </div>
                </div>

                <p class="text-sm text-gray-800 whitespace-pre-line mb-3">{{ $post->content }}</p>

                <div class="flex items-center gap-2 border-t border-gray-100 pt-3">
                    @foreach ($reactionLabels as $value => $label)
                        @php $count = $post->reactions->where('type', $value)->count(); @endphp
                        <form method="POST" action="{{ route('community.reactions.store', $post) }}">
                            @csrf
                            <input type="hidden" name="type" value="{{ $value }}">
                            @php $mine = $post->reactions->firstWhere('user_id', $user->id); @endphp
                            <button type="submit" @class([
                                'px-2 py-1 rounded-full text-xs border',
                                'bg-indigo-50 border-indigo-300 text-indigo-700' => $mine && $mine->type === $value,
                                'border-gray-200 text-gray-600 hover:bg-gray-50' => ! ($mine && $mine->type === $value),
                            ])>{{ $label }} @if($count) ({{ $count }}) @endif</button>
                        </form>
                    @endforeach
                </div>

                <div class="mt-3 space-y-2">
                    @foreach ($post->comments as $comment)
                        <div class="flex items-start justify-between bg-gray-50 rounded-lg px-3 py-2 text-sm">
                            <div>
                                <span class="font-medium text-gray-900">{{ $comment->user->name }}:</span>
                                <span class="text-gray-700">{{ $comment->content }}</span>
                            </div>
                            @can('delete', $comment)
                                <form method="POST" action="{{ route('comments.destroy', $comment) }}" onsubmit="return confirm('Xoá bình luận này?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs text-red-500 hover:text-red-700 ml-2">Xoá</button>
                                </form>
                            @endcan
                        </div>
                    @endforeach
                </div>

                <form method="POST" action="{{ route('community.comments.store', $post) }}" class="mt-3 flex gap-2">
                    @csrf
                    <input type="text" name="content" placeholder="Viết bình luận..." required class="flex-1 rounded-md border-gray-300 text-sm">
                    <button type="submit" class="rounded-md bg-white border border-gray-300 px-3 py-2 text-gray-700 text-sm hover:bg-gray-50">Gửi</button>
                </form>
            </div>
        @empty
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 text-center text-gray-500 text-sm">
                Chưa có bài viết nào trong Gym.
            </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $posts->links() }}</div>
</x-app-layout>
