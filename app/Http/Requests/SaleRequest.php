<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))
            ->filter(fn ($i) => is_array($i) && (filled($i['sku'] ?? null) || filled($i['quantity'] ?? null)))
            ->map(fn ($i) => array_merge($i, ['sku' => strtoupper(trim((string) ($i['sku'] ?? '')))]))
            ->values()
            ->all();

        $this->merge(['items' => $items]);
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', Rule::exists('users', 'id')->where('role', 'customer')],
            'payment_method' => ['required', 'in:cash,transfer,qris'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.sku' => ['required', 'string', 'distinct:ignore_case', 'exists:product_variants,sku'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Tambahkan minimal satu item.',
            'items.min' => 'Tambahkan minimal satu item.',
            'items.*.sku.exists' => 'SKU :input tidak ditemukan.',
            'items.*.sku.distinct' => 'SKU :input dipakai di lebih dari satu baris. Gabungkan jumlahnya.',
            'payment_method.in' => 'Pilih metode pembayaran.',
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_id' => 'pembeli',
            'payment_method' => 'metode pembayaran',
            'items.*.sku' => 'SKU',
            'items.*.quantity' => 'jumlah',
        ];
    }
}