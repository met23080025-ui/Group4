<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGym;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'gym_id', 'member_id', 'recorded_by', 'measured_at', 'height', 'weight',
    'body_fat_percent', 'muscle_mass', 'bmi', 'notes',
])]
class BodyMeasurement extends Model
{
    use BelongsToGym, HasFactory;

    protected function casts(): array
    {
        return [
            'measured_at' => 'date',
            'height' => 'decimal:2',
            'weight' => 'decimal:2',
            'body_fat_percent' => 'decimal:2',
            'muscle_mass' => 'decimal:2',
            'bmi' => 'decimal:2',
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

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
