<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGym;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'gym_id', 'code', 'name', 'discount_type', 'discount_value',
    'start_date', 'end_date', 'usage_limit', 'used_count', 'is_active',
])]
class Promotion extends Model
{
    use BelongsToGym, HasFactory;

    public const DISCOUNT_TYPE_PERCENT = 'percent';

    public const DISCOUNT_TYPE_FIXED = 'fixed';

    public const DISCOUNT_TYPES = [self::DISCOUNT_TYPE_PERCENT, self::DISCOUNT_TYPE_FIXED];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(Package::class, 'package_promotions')->withTimestamps();
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function isValidNow(): bool
    {
        $today = now()->toDateString();

        return $this->is_active
            && $this->start_date->toDateString() <= $today
            && $this->end_date->toDateString() >= $today
            && (is_null($this->usage_limit) || $this->used_count < $this->usage_limit);
    }
}
