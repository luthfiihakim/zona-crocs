<?php

namespace App\Services;

use App\Models\ProductVariant;

class CartService
{
    public function lines(): array
    {
        $cart = session('cart', []);

        $variants = ProductVariant::with(['product', 'size'])
            ->whereIn('id', array_keys($cart))
            ->get()
            ->keyBy('id');

        $lines = [];
        $total = 0;

        foreach ($cart as $id => $qty) {
            $v = $variants[$id] ?? null;

            if (! $v || $v->product->trashed() || ! $v->product->is_active) {
                continue;
            }

            $price = $v->product->base_price;
            $lines[] = ['variant' => $v, 'qty' => $qty, 'price' => $price, 'sub' => $qty * $price];
            $total += $qty * $price;
        }

        return [$lines, $total];
    }
}