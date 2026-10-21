<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\Sale;
use App\Services\CartService;
use App\Services\SaleService;
use DomainException;

class CheckoutController extends Controller
{
    public function create(CartService $cart)
    {
        [$lines, $total] = $cart->lines();

        if (! $lines) {
            return redirect()->route('cart.index')->with('error', 'Keranjang masih kosong.');
        }

        return view('checkout.create', compact('lines', 'total'));
    }

    public function store(CheckoutRequest $request, CartService $cart, SaleService $service)
    {
        [$lines] = $cart->lines();

        if (! $lines) {
            return redirect()->route('cart.index')
                ->with('error', 'Keranjang kosong atau produknya sudah tidak tersedia.');
        }

        $user = $request->user();

        $pending = Sale::where('customer_id', $user->id)->where('status', 'pending')->count();
        if ($pending >= 3) {
            return back()->with('error', 'Kamu masih punya 3 pesanan yang belum dibayar. Bayar atau batalkan salah satunya dulu.');
        }

        $items = collect($lines)
            ->map(fn ($l) => ['sku' => $l['variant']->sku, 'quantity' => $l['qty']])
            ->all();

        $data = $request->validated();

        try {
            $sale = $service->create(
                ['items' => $items, 'notes' => $data['notes'] ?? null],
                [
                    'customer_id' => $user->id,
                    'user_id' => null,
                    'channel' => 'online',
                    'status' => 'pending',
                    'payment_method' => null,
                    'shipping_address' => $data['shipping_address'],
                    'paid_at' => null,
                ],
                true
            );
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        session()->forget('cart');

        return redirect()->route('orders.show', $sale)
            ->with('success', "Pesanan {$sale->number} dibuat. Stok ditahan sampai {$sale->expiresAt()->format('H:i')}.");
    }
}