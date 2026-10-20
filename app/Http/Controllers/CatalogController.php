<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Size;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $categoryId = $request->query('category');
        $sizeId = $request->query('size');
        $sort = $request->query('sort');

        $products = Product::with('category')
            ->where('is_active', true)
            ->withSum(['variants as ready_stock' => fn ($v) => $v->whereHas('size', fn ($s) => $s->where('is_preorder', false))], 'stock')
            ->when($q !== '', fn ($query) => $query->where('name', 'ilike', "%{$q}%"))
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->when($sizeId, fn ($query) => $query->whereHas('variants', fn ($v) => $v->where('size_id', $sizeId)->where('stock', '>', 0)))
            ->when($sort === 'price_asc', fn ($query) => $query->orderBy('base_price'))
            ->when($sort === 'price_desc', fn ($query) => $query->orderByDesc('base_price'))
            ->when(! in_array($sort, ['price_asc', 'price_desc'], true), fn ($query) => $query->orderBy('name'))
            ->paginate(12)
            ->withQueryString();

        $categories = Category::orderBy('name')->get();
        $sizes = Size::orderBy('sort_order')->get();

        return view('shop.index', compact('products', 'categories', 'sizes', 'q', 'categoryId', 'sizeId', 'sort'));
    }

    public function show(Product $product)
    {
        abort_unless($product->is_active || auth()->user()->isStaff(), 404);

        $product->load(['category', 'variants.size']);

        $variants = $product->variants->map(fn ($v) => [
            'id' => $v->id,
            'color' => $v->color,
            'size' => $v->size->us_label,
            'eu' => $v->size->eu_range,
            'insole' => $v->size->insole_cm,
            'order' => $v->size->sort_order,
            'stock' => $v->stock,
            'po' => (bool) $v->size->is_preorder,
        ])->sortBy('order')->values();

        $colors = $variants->pluck('color')->unique()->sort()->values();
        $poSizes = $variants->where('po', true)->pluck('size')->unique()->values();

        return view('shop.show', compact('product', 'variants', 'colors', 'poSizes'));
    }
}