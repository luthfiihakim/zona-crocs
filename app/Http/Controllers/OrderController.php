<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Services\SaleService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Sale::where('customer_id', $request->user()->id)
            ->withCount('items')
            ->latest('id')
            ->paginate(10);

        return view('orders.index', compact('orders'));
    }

    public function show(Sale $sale, SaleService $service)
    {
        Gate::authorize('view', $sale);

        // Kedaluwarsa dicek juga saat halaman dibuka, tidak hanya oleh jadwal
        if ($sale->status === 'pending' && $sale->expiresAt()->isPast()) {
            try {
                $service->cancel($sale, 'expired');
            } catch (DomainException $e) {
                // sudah diproses di tempat lain
            }
            $sale->refresh();
        }

        $sale->load(['items.variant.product', 'items.variant.size']);

        return view('orders.show', compact('sale'));
    }

    public function cancel(Sale $sale, SaleService $service)
    {
        Gate::authorize('cancel', $sale);

        try {
            $service->cancel($sale);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('orders.show', $sale)
            ->with('success', 'Pesanan dibatalkan dan stok dikembalikan.');
    }
}