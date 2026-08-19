<?php

namespace App\Http\Requests\WorkoutPlan;

use App\Models\WorkoutPlan;
use Illuminate\Foundation\Http\FormRequest;

class StoreWorkoutPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [WorkoutPlan::class, $this->route('member')]);
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
