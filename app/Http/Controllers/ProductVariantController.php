<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateVariantsRequest;
use App\Http\Requests\VariantRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Size;
use Illuminate\Support\Str;

class ProductVariantController extends Controller
{
    public function store(GenerateVariantsRequest $request, Product $product)
    {
        $data = $request->validated();

        $colors = collect(explode(',', (string) ($data['colors'] ?? '')))
            ->map(fn ($c) => trim($c))
            ->filter()
            ->unique(fn ($c) => mb_strtolower($c))
            ->values();

        if ($colors->isEmpty()) {
            $colors = collect(['Original']);
        }

        $sizes = Size::whereIn('id', $data['sizes'])->orderBy('sort_order')->get();
        $prefix = strtoupper($data['sku_prefix']);

        $existing = $product->variants()->get()
            ->map(fn ($v) => $v->size_id.'|'.mb_strtolower($v->color))
            ->all();

        $rows = [];
        foreach ($colors as $color) {
            foreach ($sizes as $size) {
                if (in_array($size->id.'|'.mb_strtolower($color), $existing, true)) {
                    continue;
                }

                $parts = [$prefix, Str::slug($product->name)];
                if (mb_strtolower($color) !== 'original') {
                    $parts[] = Str::slug($color);
                }
                $parts[] = Str::before($size->us_label, '/');

                $rows[] = [
                    'product_id' => $product->id,
                    'size_id' => $size->id,
                    'sku' => strtoupper(implode('-', $parts)),
                    'color' => $color,
                    'stock' => 0,
                    'min_stock' => $data['min_stock'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (empty($rows)) {
            return back()->with('error', 'Semua kombinasi warna dan ukuran itu sudah ada.');
        }

        $taken = ProductVariant::whereIn('sku', array_column($rows, 'sku'))->pluck('sku');
        if ($taken->isNotEmpty()) {
            return back()->withInput()
                ->with('error', 'SKU bentrok: '.$taken->implode(', ').'. Ubah kode SKU lalu coba lagi.');
        }

        ProductVariant::insert($rows);

        return redirect()->route('products.show', $product)
            ->with('success', count($rows).' varian ditambahkan dengan stok 0.');
    }

    public function edit(ProductVariant $variant)
    {
        $variant->load('product', 'size');

        return view('variants.edit', compact('variant'));
    }

    public function update(VariantRequest $request, ProductVariant $variant)
    {
        $variant->update($request->validated());

        return redirect()->route('products.show', $variant->product_id)
            ->with('success', 'Varian berhasil diperbarui.');
    }

    public function destroy(ProductVariant $variant)
    {
        if ($variant->stock > 0 || $variant->purchaseItems()->exists() || $variant->saleItems()->exists()) {
            return back()->with('error', 'Varian masih punya stok atau sudah dipakai transaksi, tidak bisa dihapus.');
        }

        $productId = $variant->product_id;
        $variant->delete();

        return redirect()->route('products.show', $productId)
            ->with('success', 'Varian dihapus.');
    }
}