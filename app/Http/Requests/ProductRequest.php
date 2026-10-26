<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Models\ProductVariant;

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
        $colors = $product ? ProductVariant::where('product_id', $product->id)->distinct()->pluck('color')->all() : [];
        $file = ['image', 'mimes:jpg,jpeg,png,webp', 'max:3072', 'dimensions:min_width=300,min_height=300'];
        
        return [
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['required', Rule::unique('products', 'slug')->ignore($product)],
            'description' => ['nullable', 'string', 'max:1000'],
            'base_price' => ['required', 'integer', 'min:0'],
            'compare_price' => ['nullable', 'integer', 'gte:base_price'],
            'is_active' => ['boolean'],
            'photos' => ['nullable', 'array', 'max:8'],
            'photos.*' => $file,
            'photo_colors' => ['nullable', 'array'],
            'photo_colors.*' => ['nullable', Rule::in($colors)],
            'images' => ['nullable', 'array'],
            'images.*.color' => ['nullable', Rule::in($colors)],
            'images.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:99'],
            'images.*.delete' => ['nullable', 'boolean'],
            'images.*.file' => array_merge(['nullable'], $file),
        ];
    }

    public function messages(): array
    {
        return [
            'slug.unique' => 'Nama produk ini sudah ada.',
            'compare_price.gte' => 'Harga coret harus lebih besar atau sama dengan harga jual.',
            'photo_colors.*.in' => 'Warna foto itu tidak ada pada varian produk ini.',
            'images.*.color.in' => 'Warna foto itu tidak ada pada varian produk ini.',
            'images.*.file.image' => 'Berkas pengganti harus berupa gambar.',
            'images.*.file.mimes' => 'Format foto harus JPG, PNG, atau WEBP.',
            'images.*.file.max' => 'Ukuran foto maksimal 3 MB.',
            'images.*.file.dimensions' => 'Foto minimal 300 × 300 piksel.',
        ];
    }
}