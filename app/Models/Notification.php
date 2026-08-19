<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGym;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['gym_id', 'user_id', 'type', 'title', 'body', 'data', 'read_at'])]
class Notification extends Model
{
    use BelongsToGym, HasFactory;

    public const TYPE_MEMBERSHIP_EXPIRING = 'membership_expiring';

    public const TYPE_PAYMENT_CONFIRMED = 'payment_confirmed';

    public const TYPE_INVOICE_GENERATED = 'invoice_generated';

    public const TYPE_CLASS_BOOKED = 'class_booked';

    public const TYPE_CLASS_REMINDER = 'class_reminder';

    public const TYPE_CLASS_CANCELLED = 'class_cancelled';

    public const TYPE_TRAINER_ASSIGNED = 'trainer_assigned';

    public const TYPE_NEW_COMMENT = 'new_comment';

    public const TYPE_NEW_ANNOUNCEMENT = 'new_announcement';

    public const TYPE_NEW_PROMOTION = 'new_promotion';

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'read_at' => 'datetime',
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

    public function markAsRead(): void
    {
        if (is_null($this->read_at)) {
            $this->forceFill(['read_at' => now()])->save();
        }
    }
}
