<?php

namespace App\Http\Requests\Review;

use App\Models\Review;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Review::class);
    }

    /**
     * trainer_id (nếu có) phải là trainer thuộc ĐÚNG Gym của member đang review
     * — raw query (Rule::exists), không đi qua Eloquent global scope, nên phải
     * tự lọc gym_id ở đây, cùng pattern đã dùng ở StoreMembershipRequest.
     */
    public function rules(): array
    {
        $gymId = $this->user()->gym_id;

        return [
            'trainer_id' => ['nullable', 'integer', Rule::exists('trainers', 'id')->where('gym_id', $gymId)],
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
