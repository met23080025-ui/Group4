<?php

namespace App\Http\Requests\Package;

use App\Models\Package;
use Illuminate\Foundation\Http\FormRequest;

class StorePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Package::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'pt_sessions' => ['required', 'integer', 'min:0', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
