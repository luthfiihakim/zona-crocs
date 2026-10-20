<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Rapikan SKU dan buang baris yang sama sekali kosong
        $items = collect($this->input('items', []))
            ->filter(fn ($i) => is_array($i) && (
                filled($i['sku'] ?? null) || filled($i['quantity'] ?? null) || filled($i['unit_cost'] ?? null)
            ))
            ->map(fn ($i) => array_merge($i, ['sku' => strtoupper(trim((string) ($i['sku'] ?? '')))]))
            ->values()
            ->all();

        $this->merge(['items' => $items]);
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'exists:suppliers,id,deleted_at,NULL'],
            'purchase_date' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.sku' => ['required', 'string', 'distinct:ignore_case', 'exists:product_variants,sku'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'items.*.unit_cost' => ['required', 'integer', 'min:0', 'max:100000000'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Tambahkan minimal satu item.',
            'items.min' => 'Tambahkan minimal satu item.',
            'items.*.sku.exists' => 'SKU :input tidak ditemukan.',
            'items.*.sku.distinct' => 'SKU :input dipakai di lebih dari satu baris. Gabungkan jumlahnya.',
            'purchase_date.before_or_equal' => 'Tanggal pembelian tidak boleh di masa depan.',
        ];
    }

    public function attributes(): array
    {
        return [
            'supplier_id' => 'supplier',
            'purchase_date' => 'tanggal',
            'items.*.sku' => 'SKU',
            'items.*.quantity' => 'jumlah',
            'items.*.unit_cost' => 'harga beli',
        ];
    }
}