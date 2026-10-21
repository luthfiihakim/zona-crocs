<?php

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use Illuminate\Http\Request;
use App\Services\CartService;

class CartController extends Controller
{
    public function index()
    {
        [$lines, $total] = app(CartService::class)->lines();

        return view('cart.index', compact('lines', 'total'));
    }

    public function add(Request $request)
    {
        $data = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
        ], [
            'variant_id.required' => 'Pilih warna dan ukuran yang tersedia terlebih dahulu.',
        ]);

        $variant = ProductVariant::with(['product', 'size'])->findOrFail($data['variant_id']);

        if ($variant->product->trashed() || ! $variant->product->is_active) {
            return back()->with('error', 'Produk ini tidak tersedia.');
        }

        if ($variant->size->is_preorder) {
            return back()->with('error', 'Ukuran '.$variant->size->us_label.' wajib PO dan belum bisa dipesan lewat keranjang.');
        }

        $cart = session('cart', []);
        $inCart = $cart[$variant->id] ?? 0;
        $newQty = $inCart + $data['quantity'];

        if ($newQty > $variant->stock) {
            return back()->with('error', "Stok tidak cukup. Tersedia {$variant->stock}, di keranjangmu sudah {$inCart}.");
        }

        $cart[$variant->id] = $newQty;
        session(['cart' => $cart]);

        return redirect()->route('cart.index')->with('success', 'Ditambahkan ke keranjang.');
    }

    public function update(Request $request, ProductVariant $variant)
    {
        $qty = (int) $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
        ])['quantity'];

        if ($qty > $variant->stock) {
            return back()->with('error', "Stok {$variant->sku} hanya {$variant->stock}.");
        }

        $cart = session('cart', []);
        if (isset($cart[$variant->id])) {
            $cart[$variant->id] = $qty;
            session(['cart' => $cart]);
        }

        return back();
    }

    public function remove(ProductVariant $variant)
    {
        $cart = session('cart', []);
        unset($cart[$variant->id]);
        session(['cart' => $cart]);

        return back()->with('success', 'Item dihapus dari keranjang.');
    }
}