<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductImageRequest;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\ProductImageService;

class ProductImageController extends Controller
{
   public function store(ProductImageRequest $request, Product $product, ProductImageService $images)
    {
        $count = $images->addMany($product, $request->file('photos', []), $request->input('color') ?: null);
        $message = "{$count} foto ditambahkan.";

        if ($request->input('from') === 'photos') {
            return redirect()->route('photos.index', array_filter($request->only('filter', 'q')))->with('success', $message);
        }

        return redirect()->route('products.show', $product)->with('success', $message);
    }

    public function update(ProductImageRequest $request, ProductImage $image, ProductImageService $images)
    {
        $data = $request->validated();

        $image->update(['color' => $data['color'] ?? null, 'sort_order' => $data['sort_order']]);

        if ($request->hasFile('file')) {
            $images->replace($image, $request->file('file'));
        }

        return redirect()->route('products.show', $image->product_id)->with('success', 'Foto diperbarui.');
    }

    public function destroy(ProductImage $image, ProductImageService $images)
    {
        $productId = $image->product_id;
        $images->delete($image);

        return redirect()->route('products.show', $productId)->with('success', 'Foto dihapus.');
    }
}