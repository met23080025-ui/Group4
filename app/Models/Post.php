<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGym;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['gym_id', 'user_id', 'content', 'image_path', 'type', 'is_pinned', 'published_at'])]
class Post extends Model
{
    use BelongsToGym, HasFactory, SoftDeletes;

    public const TYPE_POST = 'post';

    public const TYPE_ANNOUNCEMENT = 'announcement';

    public const TYPE_EVENT = 'event';

    public const TYPE_CHALLENGE = 'challenge';

    public const TYPES = [self::TYPE_POST, self::TYPE_ANNOUNCEMENT, self::TYPE_EVENT, self::TYPE_CHALLENGE];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class);
    }
}
