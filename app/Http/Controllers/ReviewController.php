<?php

namespace App\Http\Controllers;

use App\Http\Requests\Review\StoreReviewRequest;
use App\Models\Review;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Dùng chung cho Owner/Staff (kiểm duyệt)/Trainer (chỉ review về mình)/
 * Member (viết review + xem review công khai) — cùng pattern với
 * PaymentController: 1 controller, nội dung theo role phân định ở
 * Controller (query) + ReviewPolicy (create/moderate).
 */
class ReviewController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Review::class);

        $user = auth()->user();

        if (in_array($user->role, [User::ROLE_GYM_OWNER, User::ROLE_STAFF], true)) {
            // Kiểm duyệt: thấy TẤT CẢ review trong Gym, kể cả đã ẩn.
            $reviews = Review::query()->with(['member.user', 'trainer.user'])->latest()->paginate(15);
        } elseif ($user->role === User::ROLE_TRAINER) {
            // Trainer CHỈ thấy review VỀ MÌNH (kể cả đã ẩn — vẫn là phản hồi cá nhân).
            $reviews = $user->trainer
                ? Review::query()->where('trainer_id', $user->trainer->id)->with('member.user')->latest()->paginate(15)
                : Review::query()->whereRaw('1 = 0')->paginate(15);
        } else {
            // Member: review công khai (is_visible) của cả Gym, để biết đã review gì.
            $reviews = Review::query()->where('is_visible', true)->with(['member.user', 'trainer.user'])->latest()->paginate(15);
        }

        $myReviews = $user->member
            ? Review::query()->where('member_id', $user->member->id)->with('trainer.user')->latest()->get()
            : collect();

        $trainers = $user->gym_id
            ? Trainer::query()->where('is_active', true)->with('user')->orderBy('id')->get()
            : collect();

        return view('reviews.index', [
            'reviews' => $reviews,
            'myReviews' => $myReviews,
            'trainers' => $trainers,
        ]);
    }

    public function store(StoreReviewRequest $request): RedirectResponse
    {
        Review::create($request->validated() + [
            'member_id' => $request->user()->member->id,
            'is_visible' => true,
        ]);

        return redirect()->route('reviews.index')->with('success', 'Đã gửi đánh giá, cảm ơn bạn!');
    }

    public function toggleVisibility(Review $review): RedirectResponse
    {
        $this->authorize('moderate', $review);

        $review->update(['is_visible' => ! $review->is_visible]);

        return redirect()
            ->route('reviews.index')
            ->with('success', $review->is_visible ? 'Đã hiện lại đánh giá.' : 'Đã ẩn đánh giá không phù hợp.');
    }
}
