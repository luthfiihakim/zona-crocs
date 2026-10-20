<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['sku' => strtoupper(trim((string) $this->sku))]);
    }

    public function rules(): array
    {
        $variant = $this->route('variant');

        return [
            'sku' => ['required', 'string', 'max:60', Rule::unique('product_variants', 'sku')->ignore($variant)],
            'color' => [
                'required', 'string', 'max:50',
                Rule::unique('product_variants', 'color')
                    ->where('product_id', $variant->product_id)
                    ->where('size_id', $variant->size_id)
                    ->ignore($variant),
            ],
            'min_stock' => ['required', 'integer', 'min:0', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'sku.unique' => 'SKU sudah dipakai varian lain.',
            'color.unique' => 'Varian dengan warna dan ukuran ini sudah ada.',
        ];
    }
}