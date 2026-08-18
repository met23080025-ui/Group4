<?php

namespace App\Http\Requests\Promotion;

use App\Models\Promotion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Promotion::class);
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required', 'string', 'max:50', 'alpha_dash',
                Rule::unique('promotions', 'code')->where('gym_id', $this->user()->gym_id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'discount_type' => ['required', Rule::in(Promotion::DISCOUNT_TYPES)],
            'discount_value' => [
                'required', 'numeric', 'min:0.01',
                function ($attribute, $value, $fail) {
                    if ($this->input('discount_type') === Promotion::DISCOUNT_TYPE_PERCENT && $value > 100) {
                        $fail('Phần trăm giảm giá không được vượt quá 100.');
                    }
                },
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
