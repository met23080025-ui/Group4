<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGym;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'gym_id', 'schedule_id', 'member_id', 'membership_id', 'status', 'booked_at', 'cancelled_at',
])]
class ClassBooking extends Model
{
    use BelongsToGym, HasFactory;

    public const STATUS_BOOKED = 'booked';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [self::STATUS_BOOKED, self::STATUS_CANCELLED];

    protected function casts(): array
    {
        return [
            'booked_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class);
    }
}
