<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\Sale;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SaleService
{
    public function create(array $data, array $attrs): Sale
    {
        return DB::transaction(function () use ($data, $attrs) {
            $items = collect($data['items']);
            $skus = $items->pluck('sku')->all();

            // Kunci baris varian (urut by id agar tidak deadlock)
            $variants = ProductVariant::with(['product', 'size'])
                ->whereIn('sku', $skus)
                ->withLiveProduct()
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('sku');

            $missing = collect($skus)->diff($variants->keys());
            if ($missing->isNotEmpty()) {
                throw new DomainException('SKU tidak tersedia: '.$missing->implode(', '));
            }

            $problems = [];
            foreach ($items as $item) {
                $v = $variants[$item['sku']];

                if ($v->size->is_preorder) {
                    $problems[] = "{$v->sku} berukuran PO dan tidak dijual dari stok";
                } elseif ($v->stock < $item['quantity']) {
                    $problems[] = "{$v->sku}: diminta {$item['quantity']}, stok tersisa {$v->stock}";
                }
            }

            if ($problems) {
                throw new DomainException('Stok tidak cukup. '.implode('; ', $problems).'.');
            }

            // Harga dari database, total dihitung di server
            $total = $items->sum(fn ($i) => $i['quantity'] * $variants[$i['sku']]->product->base_price);

            $sale = Sale::create(array_merge($attrs, [
                'number' => 'TMP-'.Str::uuid(),
                'total' => $total,
                'notes' => $data['notes'] ?? null,
            ]));

            $sale->update([
                'number' => 'SAL-'.now()->format('Ymd').'-'.str_pad((string) $sale->id, 5, '0', STR_PAD_LEFT),
            ]);

            foreach ($items as $item) {
                $v = $variants[$item['sku']];

                $sale->items()->create([
                    'product_variant_id' => $v->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $v->product->base_price,
                ]);

                $v->decrement('stock', $item['quantity']);
            }

            return $sale;
        });
    }

    public function cancel(Sale $sale, string $newStatus = 'cancelled'): void
    {
        DB::transaction(function () use ($sale, $newStatus) {
            $sale = Sale::whereKey($sale->id)->lockForUpdate()->firstOrFail();

            if (! in_array($sale->status, ['pending', 'paid'], true)) {
                throw new DomainException('Penjualan ini tidak bisa dibatalkan.');
            }

            $items = $sale->items()->get();

            $variants = ProductVariant::whereIn('id', $items->pluck('product_variant_id'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($items as $item) {
                $variants[$item->product_variant_id]->increment('stock', $item->quantity);
            }

            $sale->update(['status' => $newStatus]);
        });
    }
}