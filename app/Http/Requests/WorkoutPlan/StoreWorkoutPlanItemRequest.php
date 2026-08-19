<?php

namespace App\Http\Requests\WorkoutPlan;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkoutPlanItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('workoutPlan'));
    }

    public function rules(): array
    {
        return [
            'exercise_name' => ['required', 'string', 'max:255'],
            'sets' => ['nullable', 'integer', 'min:1', 'max:50'],
            'reps' => ['nullable', 'integer', 'min:1', 'max:200'],
            'weight' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'rest_seconds' => ['nullable', 'integer', 'min:0', 'max:3600'],
            'day_of_week' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
