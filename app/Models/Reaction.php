<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGym;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['gym_id', 'post_id', 'user_id', 'type'])]
class Reaction extends Model
{
    use BelongsToGym, HasFactory;

    public const TYPE_LIKE = 'like';

    public const TYPE_LOVE = 'love';

    public const TYPE_WOW = 'wow';

    public const TYPES = [self::TYPE_LIKE, self::TYPE_LOVE, self::TYPE_WOW];

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
