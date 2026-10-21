<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaleRequest;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\User;
use App\Services\SaleService;
use DomainException;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $status = $request->query('status');
        $channel = $request->query('channel');
        $from = $request->query('from');
        $to = $request->query('to');

        $sales = Sale::with(['customer', 'cashier'])
            ->withCount('items')
            ->when($q !== '', fn ($query) => $query->where('number', 'ilike', "%{$q}%"))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($channel, fn ($query) => $query->where('channel', $channel))
            ->when($from, fn ($query) => $query->whereDate('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('created_at', '<=', $to))
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('sales.index', compact('sales', 'q', 'status', 'channel', 'from', 'to'));
    }

    public function create()
    {
        $customers = User::where('role', 'customer')->orderBy('name')->get(['id', 'name', 'email']);

        $variants = ProductVariant::with(['product', 'size'])
            ->withLiveProduct()
            ->orderBy('sku')
            ->get();

        $catalog = $variants->mapWithKeys(fn ($v) => [$v->sku => [
            'price' => $v->product->base_price,
            'stock' => $v->stock,
            'po' => (bool) $v->size->is_preorder,
        ]]);

        return view('sales.create', compact('customers', 'variants', 'catalog'));
    }

    public function store(SaleRequest $request, SaleService $service)
    {
        $data = $request->validated();

        try {
            $sale = $service->create($data, [
                'customer_id' => $data['customer_id'] ?? null,
                'user_id' => $request->user()->id,
                'channel' => 'pos',
                'status' => 'paid',
                'payment_method' => $data['payment_method'],
                'paid_at' => now(),
            ]);
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('sales.show', $sale)
            ->with('success', "Penjualan {$sale->number} tersimpan. Stok sudah berkurang.");
    }

    public function show(Sale $sale)
    {
        $sale->load(['customer', 'cashier', 'items.variant.product', 'items.variant.size']);

        return view('sales.show', compact('sale'));
    }

    public function cancel(Sale $sale, SaleService $service)
    {
        try {
            $service->cancel($sale);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('sales.show', $sale)
            ->with('success', 'Penjualan dibatalkan dan stok dikembalikan.');
    }
}