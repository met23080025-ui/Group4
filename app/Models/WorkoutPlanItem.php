<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'workout_plan_id', 'exercise_name', 'sets', 'reps', 'weight',
    'rest_seconds', 'day_of_week', 'notes', 'sort_order',
])]
class WorkoutPlanItem extends Model
{
    // Không dùng BelongsToGym: bảng không có cột gym_id, được scope gián tiếp
    // qua workout_plan_id (WorkoutPlan đã bị lọc theo gym_id).
    use HasFactory;

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
        ];
    }

    public function workoutPlan(): BelongsTo
    {
        return $this->belongsTo(WorkoutPlan::class);
    }
}
