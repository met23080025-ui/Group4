<?php

namespace App\Http\Controllers;

use App\Http\Requests\Comment\StoreCommentRequest;
use App\Models\Comment;
use App\Models\Notification;
use App\Models\Post;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;

class CommentController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService) {}

    public function store(StoreCommentRequest $request, Post $post): RedirectResponse
    {
        $comment = $post->comments()->create($request->validated() + ['user_id' => $request->user()->id]);

        // Trigger "comment mới" (mục 2, Ngày 3): báo cho tác giả bài viết, trừ
        // khi tự comment vào bài của chính mình.
        if ($post->user_id !== $request->user()->id) {
            $this->notificationService->notify(
                $post->user,
                Notification::TYPE_NEW_COMMENT,
                'Có bình luận mới',
                "{$request->user()->name} đã bình luận vào bài viết của bạn: \"{$comment->content}\"",
                ['post_id' => $post->id, 'comment_id' => $comment->id],
            );
        }

        return redirect()->route('community.index')->with('success', 'Đã bình luận.');
    }

    public function destroy(Comment $comment): RedirectResponse
    {
        $this->authorize('delete', $comment);

        $comment->delete();

        return redirect()->route('community.index')->with('success', 'Đã xoá bình luận.');
    }
}
