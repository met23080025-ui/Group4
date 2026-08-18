<?php

namespace App\Http\Requests\Schedule;

use App\Models\Schedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Schedule::class);
    }

    /**
     * trainer_id lọc theo gym_id ngay ở validate (Rule::exists là raw query,
     * không đi qua global scope BelongsToGym) — cùng pattern StoreMembershipRequest.
     */
    public function rules(): array
    {
        $gymId = $this->user()->gym_id;

        return [
            'trainer_id' => ['nullable', 'integer', Rule::exists('trainers', 'id')->where('gym_id', $gymId)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'class_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'capacity' => ['required', 'integer', 'min:1', 'max:200'],
            'is_pt_session' => ['sometimes', 'boolean'],
        ];
    }
}
