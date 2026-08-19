<?php

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignTrainerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('member'));
    }

    /**
     * Chặn cross-tenant ngay ở lớp validate (raw query, không đi qua Eloquent
     * global scope) — cùng pattern đã dùng ở StoreMembershipRequest.
     */
    public function rules(): array
    {
        return [
            'trainer_id' => [
                'nullable', 'integer',
                Rule::exists('trainers', 'id')->where('gym_id', $this->user()->gym_id),
            ],
        ];
    }
}
