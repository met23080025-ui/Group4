<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGym;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['gym_id', 'member_id', 'points', 'reason', 'reference_type', 'reference_id', 'balance_after'])]
class LoyaltyPointTransaction extends Model
{
    use BelongsToGym, HasFactory;

    public const REASON_CHECK_IN = 'check_in';

    public const REASON_REVIEW = 'review';

    public const REASON_CHALLENGE_COMPLETION = 'challenge_completion';

    public const REASON_REWARD_REDEMPTION = 'reward_redemption';

    // Số điểm cộng cho từng hành động (mục 17 của đề bài) — đặt tập trung ở đây
    // để tránh hard-code rải rác trong các Service.
    public const POINTS_CHECK_IN = 10;

    public const POINTS_REVIEW = 20;

    public const POINTS_CHALLENGE_COMPLETION = 50;

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
