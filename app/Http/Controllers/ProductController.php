<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Size;
use Illuminate\Http\Request;

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

    public function store(ProductRequest $request)
    {
        $product = Product::create($request->validated());

        return redirect()->route('products.show', $product)
            ->with('success', 'Produk dibuat. Tambahkan varian warna dan ukuran di bawah.');
    }

    public function show(Product $product)
    {
        $product->load(['category', 'variants.size']);

        $variants = $product->variants->sortBy([['color', 'asc'], ['size.sort_order', 'asc']]);
        $sizes = Size::orderBy('sort_order')->get();

        return view('products.show', compact('product', 'variants', 'sizes'));
    }

    public function edit(Product $product)
    {
        $categories = Category::orderBy('name')->get();

        return view('products.edit', compact('product', 'categories'));
    }

    public function update(ProductRequest $request, Product $product)
    {
        $product->update($request->validated());

        return redirect()->route('products.show', $product)
            ->with('success', 'Produk berhasil diperbarui.');
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