<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGym;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['gym_id', 'member_id', 'trainer_id', 'class_booking_id', 'check_in_date', 'check_in_time', 'source'])]
class Attendance extends Model
{
    use BelongsToGym, HasFactory;

    public const SOURCE_QR = 'qr';

    public const SOURCE_MANUAL = 'manual';

    protected function casts(): array
    {
        return [
            'check_in_date' => 'date',
            'check_in_time' => 'datetime',
        ];
    }

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class);
    }

    public function classBooking(): BelongsTo
    {
        return $this->belongsTo(ClassBooking::class);
    }
}
