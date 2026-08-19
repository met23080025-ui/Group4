<?php

namespace App\Http\Requests\NutritionPlan;

use App\Models\NutritionPlan;
use Illuminate\Foundation\Http\FormRequest;

class StoreNutritionPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [NutritionPlan::class, $this->route('member')]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }
}
