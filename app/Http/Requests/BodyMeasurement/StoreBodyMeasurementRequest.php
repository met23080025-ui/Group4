<?php

namespace App\Http\Requests\BodyMeasurement;

use App\Models\BodyMeasurement;
use Illuminate\Foundation\Http\FormRequest;

class StoreBodyMeasurementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [BodyMeasurement::class, $this->route('member')]);
    }

    public function rules(): array
    {
        return [
            'measured_at' => ['nullable', 'date', 'before_or_equal:today'],
            'height' => ['required', 'numeric', 'min:30', 'max:300'],
            'weight' => ['required', 'numeric', 'min:1', 'max:500'],
            'body_fat_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'muscle_mass' => ['nullable', 'numeric', 'min:0', 'max:300'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
