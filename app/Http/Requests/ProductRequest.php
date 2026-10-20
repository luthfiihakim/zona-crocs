<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug((string) $this->name),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['required', Rule::unique('products', 'slug')->ignore($product)],
            'description' => ['nullable', 'string', 'max:1000'],
            'base_price' => ['required', 'integer', 'min:0'],
            'compare_price' => ['nullable', 'integer', 'gte:base_price'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.unique' => 'Nama produk ini sudah ada.',
            'compare_price.gte' => 'Harga coret harus lebih besar atau sama dengan harga jual.',
        ];
    }
}