<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGym;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'gym_id', 'trainer_id', 'title', 'description', 'class_date',
    'start_time', 'end_time', 'capacity', 'status', 'is_pt_session',
])]
class Schedule extends Model
{
    use BelongsToGym, HasFactory, SoftDeletes;

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_COMPLETED = 'completed';

    public const STATUSES = [self::STATUS_SCHEDULED, self::STATUS_CANCELLED, self::STATUS_COMPLETED];

    protected function casts(): array
    {
        return [
            'class_date' => 'date',
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
            'is_pt_session' => 'boolean',
        ];
    }

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class);
    }

    public function classBookings(): HasMany
    {
        return $this->hasMany(ClassBooking::class);
    }

    public function bookedCount(): int
    {
        return $this->classBookings()->where('status', ClassBooking::STATUS_BOOKED)->count();
    }

    public function hasAvailableSlot(): bool
    {
        return $this->bookedCount() < $this->capacity;
    }
}
