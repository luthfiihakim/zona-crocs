<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

class PhotoGapService
{
    /** Satu entri per produk, berisi baris per warna beserta status fotonya. */
    public function rows(string $q = '', bool $onlyActive = false): Collection
    {
        return Product::with(['category', 'variants:id,product_id,color', 'images'])
            ->when($q !== '', fn ($query) => $query->where('name', 'ilike', "%{$q}%"))
            ->when($onlyActive, fn ($query) => $query->where('is_active', true))
            ->orderBy('name')
            ->get()
            ->map(function (Product $p) {
                $colors = $p->variants->pluck('color')->unique()->sort()->values();
                $single = $colors->isEmpty() || ($colors->count() === 1 && $colors->first() === 'Original');

                $items = ($single ? collect([null]) : $colors)->map(function ($color) use ($p, $single, $colors) {
                    $urls = $p->imageUrls($single ? $colors->first() : $color);

                    return [
                        'color' => $single ? null : $color,
                        'label' => $single ? 'Semua warna' : $color,
                        'urls' => $urls,
                        'missing' => $urls === [],
                    ];
                });

                return [
                    'product' => $p,
                    'items' => $items,
                    'missing' => $items->where('missing', true)->count(),
                ];
            });
    }
}