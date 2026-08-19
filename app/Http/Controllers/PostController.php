<?php

namespace App\Http\Controllers;

use App\Http\Requests\Post\StorePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Community feed (Khối 1, Ngày 3) — dùng chung cho Owner/Staff/Trainer/
 * Member (giống PaymentController/BodyMeasurementController), PostPolicy
 * phân định đúng quyền theo từng action.
 */
class PostController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Post::class);

        $posts = Post::query()
            ->with(['user', 'comments.user', 'reactions'])
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->paginate(10);

        return view('community.index', ['posts' => $posts]);
    }

    public function store(StorePostRequest $request): RedirectResponse
    {
        Post::create($request->validated() + [
            'user_id' => $request->user()->id,
            'published_at' => now(),
        ]);

        return redirect()->route('community.index')->with('success', 'Đã đăng bài.');
    }

    public function update(UpdatePostRequest $request, Post $post): RedirectResponse
    {
        $post->update($request->validated());

        return redirect()->route('community.index')->with('success', 'Đã cập nhật bài viết.');
    }

    public function destroy(Post $post): RedirectResponse
    {
        $this->authorize('delete', $post);

        $post->delete();

        return redirect()->route('community.index')->with('success', 'Đã xoá bài viết.');
    }

    public function pin(Post $post): RedirectResponse
    {
        $this->authorize('pin', $post);

        $post->update(['is_pinned' => ! $post->is_pinned]);

        return redirect()
            ->route('community.index')
            ->with('success', $post->is_pinned ? 'Đã ghim bài viết lên đầu feed.' : 'Đã gỡ ghim bài viết.');
    }
}
