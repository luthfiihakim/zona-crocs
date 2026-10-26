<?php

namespace App\Http\Requests;

use App\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $image = $this->route('image');
        $product = $image ? $image->product : $this->route('product');

        $colors = ProductVariant::where('product_id', $product->id)->distinct()->pluck('color')->all();
        $file = ['image', 'mimes:jpg,jpeg,png,webp', 'max:3072', 'dimensions:min_width=300,min_height=300'];

        $rules = ['color' => ['nullable', Rule::in($colors)]];

        if ($image) {
            $rules['sort_order'] = ['required', 'integer', 'min:0', 'max:99'];
            $rules['file'] = array_merge(['nullable'], $file);
        } else {
            $rules['photos'] = ['nullable', 'array', 'max:8'];
            $rules['photos.*'] = $file;
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'photos.max' => 'Maksimal 8 foto sekali unggah.',
            'photos.*.image' => 'Berkas harus berupa gambar.',
            'photos.*.mimes' => 'Format foto harus JPG, PNG, atau WEBP.',
            'photos.*.max' => 'Ukuran satu foto maksimal 3 MB.',
            'photos.*.dimensions' => 'Foto minimal 300 × 300 piksel.',
            'photos.*.uploaded' => 'Foto gagal diunggah. Cek batas upload_max_filesize di php.ini.',
            'file.image' => 'Berkas pengganti harus berupa gambar.',
            'file.mimes' => 'Format foto harus JPG, PNG, atau WEBP.',
            'file.max' => 'Ukuran foto maksimal 3 MB.',
            'file.dimensions' => 'Foto minimal 300 × 300 piksel.',
            'color.in' => 'Warna itu tidak ada pada varian produk ini.',
        ];
    }
}