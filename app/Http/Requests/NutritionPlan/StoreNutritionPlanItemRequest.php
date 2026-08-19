<?php

namespace App\Http\Requests\NutritionPlan;

use Illuminate\Foundation\Http\FormRequest;

class StoreNutritionPlanItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('nutritionPlan'));
    }

    public function rules(): array
    {
        return [
            'meal_name' => ['required', 'string', 'max:255'],
            'meal_time' => ['nullable', 'date_format:H:i'],
            'food' => ['required', 'string', 'max:255'],
            'calories' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'protein' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'carbs' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'fat' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
