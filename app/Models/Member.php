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
    'gym_id', 'user_id', 'trainer_id', 'member_code', 'date_of_birth', 'gender', 'address',
    'emergency_contact', 'height', 'weight', 'status', 'joined_at', 'notes',
])]
class Member extends Model
{
    use BelongsToGym, HasFactory, SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_BLOCKED = 'blocked';

    public const STATUSES = [self::STATUS_ACTIVE, self::STATUS_EXPIRED, self::STATUS_BLOCKED];

    public const GENDER_MALE = 'male';

    public const GENDER_FEMALE = 'female';

    public const GENDER_OTHER = 'other';

    public const GENDERS = [self::GENDER_MALE, self::GENDER_FEMALE, self::GENDER_OTHER];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'joined_at' => 'date',
            'height' => 'decimal:2',
            'weight' => 'decimal:2',
            // Khóa bí mật ký token QR check-in (Khối 5) — không phải dữ liệu
            // nghiệp vụ nhập từ form nên KHÔNG có trong #[Fillable] phía trên,
            // chỉ được set qua forceFill() trong AttendanceService.
            'qr_secret' => 'encrypted',
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

    // PT phụ trách chính (Khối 6) — nullable, có thể chưa gán.
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function classBookings(): HasMany
    {
        return $this->hasMany(ClassBooking::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function bodyMeasurements(): HasMany
    {
        return $this->hasMany(BodyMeasurement::class);
    }

    public function workoutPlans(): HasMany
    {
        return $this->hasMany(WorkoutPlan::class);
    }

    public function nutritionPlans(): HasMany
    {
        return $this->hasMany(NutritionPlan::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function loyaltyPointTransactions(): HasMany
    {
        return $this->hasMany(LoyaltyPointTransaction::class);
    }

    public function rewardRedemptions(): HasMany
    {
        return $this->hasMany(RewardRedemption::class);
    }

    public function currentMembership(): ?Membership
    {
        return $this->memberships()
            ->where('status', Membership::STATUS_ACTIVE)
            ->latest('end_date')
            ->first();
    }
}
