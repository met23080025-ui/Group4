<?php

namespace App\Http\Requests\Equipment;

use App\Models\Equipment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEquipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('equipment'));
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'purchase_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(Equipment::STATUSES)],
            'maintenance_interval_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
        ];
    }
}
