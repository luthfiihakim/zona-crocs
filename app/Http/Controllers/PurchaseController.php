<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseRequest;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\PurchaseService;
use DomainException;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $supplierId = $request->query('supplier');
        $status = $request->query('status');
        $from = $request->query('from');
        $to = $request->query('to');

        $purchases = Purchase::with(['supplier', 'user'])
            ->withCount('items')
            ->when($q !== '', fn ($query) => $query->where('number', 'ilike', "%{$q}%"))
            ->when($supplierId, fn ($query) => $query->where('supplier_id', $supplierId))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($from, fn ($query) => $query->whereDate('purchase_date', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('purchase_date', '<=', $to))
            ->latest('purchase_date')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $suppliers = Supplier::orderBy('name')->get();

        return view('purchases.index', compact('purchases', 'suppliers', 'q', 'supplierId', 'status', 'from', 'to'));
    }

    public function create()
    {
        $suppliers = Supplier::orderBy('name')->get();
        $variants = ProductVariant::with(['product', 'size'])
            ->whereHas('product')
            ->orderBy('sku')
            ->get();

        return view('purchases.create', compact('suppliers', 'variants'));
    }

    public function store(PurchaseRequest $request, PurchaseService $service)
    {
        try {
            $purchase = $service->create($request->validated(), $request->user());
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('purchases.show', $purchase)
            ->with('success', "Pembelian {$purchase->number} tersimpan. Stok sudah bertambah.");
    }

    public function show(Purchase $purchase)
    {
        $purchase->load(['supplier', 'user', 'items.variant.product', 'items.variant.size']);

        return view('purchases.show', compact('purchase'));
    }

    public function cancel(Purchase $purchase, PurchaseService $service)
    {
        try {
            $service->cancel($purchase);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('purchases.show', $purchase)
            ->with('success', 'Pembelian dibatalkan dan stok dikembalikan.');
    }
}