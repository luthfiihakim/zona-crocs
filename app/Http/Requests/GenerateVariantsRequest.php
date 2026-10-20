<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateVariantsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sku_prefix' => ['required', 'alpha_dash', 'max:10'],
            'colors' => ['nullable', 'string', 'max:500'],
            'sizes' => ['required', 'array', 'min:1'],
            'sizes.*' => ['integer', 'exists:sizes,id'],
            'min_stock' => ['required', 'integer', 'min:0', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'sizes.required' => 'Pilih minimal satu ukuran.',
            'sizes.min' => 'Pilih minimal satu ukuran.',
        ];
    }
}