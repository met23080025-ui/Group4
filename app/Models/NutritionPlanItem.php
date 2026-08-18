<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'nutrition_plan_id', 'meal_name', 'meal_time', 'food',
    'calories', 'protein', 'carbs', 'fat', 'notes', 'sort_order',
])]
class NutritionPlanItem extends Model
{
    // Không dùng BelongsToGym: bảng không có cột gym_id, được scope gián tiếp
    // qua nutrition_plan_id (NutritionPlan đã bị lọc theo gym_id).
    use HasFactory;

    protected function casts(): array
    {
        return [
            'meal_time' => 'datetime:H:i',
            'calories' => 'decimal:2',
            'protein' => 'decimal:2',
            'carbs' => 'decimal:2',
            'fat' => 'decimal:2',
        ];
    }

    public function nutritionPlan(): BelongsTo
    {
        return $this->belongsTo(NutritionPlan::class);
    }
}
