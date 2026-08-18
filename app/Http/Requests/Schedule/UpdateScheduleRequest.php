<?php

namespace App\Http\Requests\Schedule;

use App\Models\ClassBooking;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('schedule'));
    }

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

    /**
     * Không cho hạ capacity xuống dưới số chỗ đã đặt (booked) hiện tại —
     * tránh làm lớp "âm chỗ trống" một cách âm thầm.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $capacity = $this->integer('capacity');
            $schedule = $this->route('schedule');

            if (! $capacity || ! $schedule) {
                return;
            }

            $bookedCount = $schedule->classBookings()->where('status', ClassBooking::STATUS_BOOKED)->count();

            if ($capacity < $bookedCount) {
                $validator->errors()->add(
                    'capacity',
                    "Không thể đặt capacity nhỏ hơn số chỗ đã đặt hiện tại ({$bookedCount})."
                );
            }
        });
    }
}
