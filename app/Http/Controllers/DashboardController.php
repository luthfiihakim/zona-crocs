<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $dayRange = [now()->startOfDay(), now()->endOfDay()];
        $monthRange = [now()->startOfMonth(), now()->endOfMonth()];

        $stats = [
            'revenue_today' => (int) $this->paid()->whereBetween('paid_at', $dayRange)->sum('total'),
            'revenue_month' => (int) $this->paid()->whereBetween('paid_at', $monthRange)->sum('total'),
            'sales_month' => $this->paid()->whereBetween('paid_at', $monthRange)->count(),
            'pending' => Sale::where('status', 'pending')->count(),
            'active_products' => Product::where('is_active', true)->count(),
            'low_stock' => $this->lowStockQuery()->count(),
        ];

        // Omzet 7 hari terakhir
        $from = now()->subDays(6)->startOfDay();
        $daily = $this->paid()
            ->whereBetween('paid_at', [$from, now()->endOfDay()])
            ->selectRaw('DATE(paid_at) as d, SUM(total) as total')
            ->groupBy('d')
            ->pluck('total', 'd');

        $trend = collect(range(6, 0))->map(function ($i) use ($daily) {
            $day = now()->subDays($i);

            return [
                'label' => $day->format('d/m'),
                'total' => (int) ($daily[$day->toDateString()] ?? 0),
            ];
        });
        $trendMax = max(1, $trend->max('total'));

        // Produk terlaris bulan ini
        $topProducts = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('product_variants', 'product_variants.id', '=', 'sale_items.product_variant_id')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->where('sales.status', 'paid')
            ->whereBetween('sales.paid_at', $monthRange)
            ->groupBy('products.id', 'products.name')
            ->selectRaw('products.name, SUM(sale_items.quantity) as qty, SUM(sale_items.quantity * sale_items.unit_price) as revenue')
            ->orderByDesc('qty')
            ->limit(5)
            ->get();

        $lowStock = $this->lowStockQuery()->orderBy('stock')->orderBy('sku')->limit(8)->get();
        $recent = Sale::with('customer')->latest('id')->limit(5)->get();

        return view('dashboard', compact('stats', 'trend', 'trendMax', 'topProducts', 'lowStock', 'recent'));
    }

    public function lowStock(Request $request)
    {
        $q = trim((string) $request->query('q'));

        $variants = $this->lowStockQuery()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('sku', 'ilike', "%{$q}%")
                      ->orWhereHas('product', fn ($p) => $p->where('name', 'ilike', "%{$q}%"));
                });
            })
            ->orderBy('stock')
            ->orderBy('sku')
            ->paginate(15)
            ->withQueryString();

        return view('stock.low', compact('variants', 'q'));
    }

    private function paid()
    {
        return Sale::where('status', 'paid');
    }

    private function lowStockQuery()
    {
        return ProductVariant::with(['product', 'size'])
            ->withLiveProduct()
            ->whereHas('product', fn ($p) => $p->where('is_active', true))
            ->whereHas('size', fn ($s) => $s->where('is_preorder', false))
            ->whereColumn('stock', '<=', 'min_stock');
    }
}