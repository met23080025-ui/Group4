<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Reaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReactionController extends Controller
{
    /**
     * Toggle: bấm lại đúng loại reaction đã có -> gỡ; bấm loại khác -> đổi
     * loại; chưa có -> tạo mới. `reactions.unique(post_id,user_id)` (Ngày 1)
     * đảm bảo 1 user chỉ có 1 reaction/post tại 1 thời điểm.
     */
    public function store(Request $request, Post $post): RedirectResponse
    {
        $this->authorize('create', [Reaction::class, $post]);

        $type = $request->validate(['type' => ['required', Rule::in(Reaction::TYPES)]])['type'];

        $existing = $post->reactions()->where('user_id', $request->user()->id)->first();

        if ($existing && $existing->type === $type) {
            $existing->delete();
        } elseif ($existing) {
            $existing->update(['type' => $type]);
        } else {
            $post->reactions()->create(['user_id' => $request->user()->id, 'type' => $type]);
        }

        return redirect()->route('community.index');
    }
}
