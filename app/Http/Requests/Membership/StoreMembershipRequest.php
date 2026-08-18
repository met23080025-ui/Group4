<?php

namespace App\Http\Requests\Membership;

use App\Models\Membership;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMembershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Membership::class);
    }

    /**
     * Chặn cross-tenant ngay ở lớp validate: Rule::exists() là raw query,
     * KHÔNG đi qua Eloquent global scope, nên phải tự lọc gym_id ở đây —
     * nếu không, user có thể POST thẳng package_id/promotion_id của Gym khác.
     */
    public function rules(): array
    {
        $gymId = $this->user()->gym_id;

        return [
            'member_id' => ['required', 'integer', Rule::exists('members', 'id')->where('gym_id', $gymId)],
            'package_id' => ['required', 'integer', Rule::exists('packages', 'id')->where('gym_id', $gymId)->where('is_active', true)],
            'promotion_id' => ['nullable', 'integer', Rule::exists('promotions', 'id')->where('gym_id', $gymId)],
            'start_date' => ['nullable', 'date'],
        ];
    }
}
