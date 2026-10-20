<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseService
{
    public function create(array $data, User $user): Purchase
    {
        return DB::transaction(function () use ($data, $user) {
            $items = collect($data['items']);
            $skus = $items->pluck('sku')->all();

            // Kunci baris varian selama transaksi, urut by id agar tidak deadlock
            $variants = ProductVariant::whereIn('sku', $skus)
                ->whereHas('product')
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('sku');

            $missing = collect($skus)->diff($variants->keys());
            if ($missing->isNotEmpty()) {
                throw new DomainException('SKU tidak tersedia: '.$missing->implode(', '));
            }

            // Total dihitung di server, bukan dari input browser
            $total = $items->sum(fn ($i) => $i['quantity'] * $i['unit_cost']);

            $purchase = Purchase::create([
                'number' => 'TMP-'.Str::uuid(),
                'supplier_id' => $data['supplier_id'],
                'user_id' => $user->id,
                'purchase_date' => $data['purchase_date'],
                'total' => $total,
                'status' => 'received',
                'notes' => $data['notes'] ?? null,
            ]);

            $purchase->update([
                'number' => 'PUR-'.now()->format('Ymd').'-'.str_pad((string) $purchase->id, 5, '0', STR_PAD_LEFT),
            ]);

            foreach ($items as $item) {
                $variant = $variants[$item['sku']];

                $purchase->items()->create([
                    'product_variant_id' => $variant->id,
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                ]);

                $variant->increment('stock', $item['quantity']);
            }

            return $purchase;
        });
    }

    public function cancel(Purchase $purchase): void
    {
        DB::transaction(function () use ($purchase) {
            $purchase = Purchase::whereKey($purchase->id)->lockForUpdate()->firstOrFail();

            if ($purchase->status !== 'received') {
                throw new DomainException('Pembelian ini sudah dibatalkan.');
            }

            $items = $purchase->items()->get();

            $variants = ProductVariant::whereIn('id', $items->pluck('product_variant_id'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($items as $item) {
                $variant = $variants[$item->product_variant_id];

                if ($variant->stock < $item->quantity) {
                    throw new DomainException(
                        "Tidak bisa dibatalkan: stok {$variant->sku} ({$variant->stock}) kurang dari jumlah yang dibeli ({$item->quantity}). Barang sudah terpakai."
                    );
                }
            }

            foreach ($items as $item) {
                $variants[$item->product_variant_id]->decrement('stock', $item->quantity);
            }

            $purchase->update(['status' => 'cancelled']);
        });
    }
}