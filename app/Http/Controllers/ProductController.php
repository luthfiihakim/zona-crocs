<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Size;
use Illuminate\Http\Request;
use App\Services\ProductImageService;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $categoryId = $request->query('category');
        $status = $request->query('status');

        $products = Product::with('category')
            ->withCount('variants')
            ->withSum('variants', 'stock')
            ->when($q !== '', fn ($query) => $query->where('name', 'ilike', "%{$q}%"))
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $categories = Category::orderBy('name')->get();

        return view('products.index', compact('products', 'categories', 'q', 'categoryId', 'status'));
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();

        return view('products.create', compact('categories'));
    }

    public function store(ProductRequest $request, ProductImageService $images)
    {
        $product = Product::create($request->safe()->except(['photos', 'photo_colors', 'images']));
        $count = $images->addMany($product, $request->file('photos', []));

        return redirect()->route('products.show', $product)
            ->with('success', 'Produk dibuat'.($count ? " dengan {$count} foto" : '').'. Tambahkan varian warna dan ukuran di bawah.');
    }

    public function show(Product $product)
    {
        $product->load(['category', 'variants.size', 'images']);

        $variants = $product->variants->sortBy([['color', 'asc'], ['size.sort_order', 'asc']]);
        $colors = $product->variants->pluck('color')->unique()->sort()->values();
        $sizes = Size::orderBy('sort_order')->get();

        return view('products.show', compact('product', 'variants', 'sizes', 'colors'));
    }

    public function edit(Product $product)
    {
        $product->load(['images', 'variants:id,product_id,color']);
        $categories = Category::orderBy('name')->get();

        return view('products.edit', compact('product', 'categories'));
    }

    public function update(ProductRequest $request, Product $product, ProductImageService $images)
    {
        $product->update($request->safe()->except(['photos', 'photo_colors', 'images']));

        $stats = $images->syncFromForm(
            $product,
            $request->input('images', []),
            $request->file('images', []),
            $request->file('photos', []),
            $request->input('photo_colors', []),
        );

        $parts = [];
        foreach (['added' => 'foto ditambahkan', 'replaced' => 'foto diganti', 'updated' => 'foto diubah', 'deleted' => 'foto dihapus'] as $key => $label) {
            if ($stats[$key]) {
                $parts[] = "{$stats[$key]} {$label}";
            }
        }

        return redirect()->route('products.show', $product)
            ->with('success', 'Produk berhasil diperbarui'.($parts ? ': '.implode(', ', $parts) : '').'.');
    }

    public function destroy(Product $product)
    {
        if ($product->variants()->where('stock', '>', 0)->exists()) {
            return back()->with('error', 'Produk masih punya stok, tidak bisa dihapus. Nonaktifkan lewat menu Ubah jika tidak ingin ditampilkan.');
        }

        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Produk dihapus. Riwayat transaksi tetap tersimpan.');
    }
}