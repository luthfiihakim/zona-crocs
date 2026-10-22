<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Services\PaymentService;
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

    public function show(Sale $sale, SaleService $service, PaymentService $payments)
    {
        Gate::authorize('view', $sale);

        if ($sale->status === 'pending') {
            $payments->syncSale($sale);
            $sale->refresh();
        }

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

    public function cancel(Sale $sale, SaleService $service, PaymentService $payments)
    {
        Gate::authorize('cancel', $sale);

        // Cek dulu apakah ternyata sudah dibayar
        $payments->syncSale($sale);

        if ($sale->fresh()->status !== 'pending') {
            return back()->with('error', 'Pesanan sudah dibayar dan tidak bisa dibatalkan.');
        }

        $payments->cancelOpen($sale);

        try {
            $service->cancel($sale);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('orders.show', $sale)
            ->with('success', 'Pesanan dibatalkan dan stok dikembalikan.');
    }
}