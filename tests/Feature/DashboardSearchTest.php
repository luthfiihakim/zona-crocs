<?php

namespace Tests\Feature;

use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsShop;
use Tests\TestCase;

class DashboardSearchTest extends TestCase
{
    use RefreshDatabase, BuildsShop;

    public function test_dashboard_counts_only_paid_sales_as_revenue(): void
    {
        $staff = $this->makeUser('staff');
        $this->placePosSale($staff, $this->makeVariant(stock: 5), 2);                      // lunas
        $this->placeOnlineOrder($this->makeUser('customer'), $this->makeVariant(stock: 5)); // belum dibayar

        $stats = $this->actingAs($staff)->get('/dashboard')->assertOk()->viewData('stats');

        $this->assertSame(758000, $stats['revenue_today']);
        $this->assertSame(1, $stats['sales_month']);
        $this->assertSame(1, $stats['pending']);
    }

    public function test_cancelled_sale_is_removed_from_revenue(): void
    {
        $staff = $this->makeUser('staff');
        $sale = $this->placePosSale($staff, $this->makeVariant(stock: 5), 2);

        app(SaleService::class)->cancel($sale);

        $stats = $this->actingAs($staff)->get('/dashboard')->viewData('stats');
        $this->assertSame(0, $stats['revenue_today']);
        $this->assertSame(0, $stats['sales_month']);
    }

    public function test_low_stock_counts_only_sellable_low_variants(): void
    {
        $this->makeVariant(stock: 1);                    // rendah (min 2): dihitung
        $this->makeVariant(stock: 10);                   // aman
        $this->makeVariant(stock: 0, po: true);          // ukuran PO: tidak dihitung
        $this->makeVariant(stock: 0, active: false);     // produk nonaktif: tidak dihitung

        $stats = $this->actingAs($this->makeUser('staff'))->get('/dashboard')->viewData('stats');

        $this->assertSame(1, $stats['low_stock']);
    }

    public function test_product_search_filters_by_name(): void
    {
        $this->makeProduct(['name' => 'Alpha Satu']);
        $this->makeProduct(['name' => 'Beta Dua']);

        $this->actingAs($this->makeUser('staff'))
            ->get(route('products.index', ['q' => 'alpha']))
            ->assertSee('Alpha Satu')
            ->assertDontSee('Beta Dua');
    }

    public function test_product_filter_by_category(): void
    {
        $a = $this->makeCategory();
        $b = $this->makeCategory();
        $this->makeProduct(['name' => 'Item Kategori A', 'category_id' => $a->id]);
        $this->makeProduct(['name' => 'Item Kategori B', 'category_id' => $b->id]);

        $this->actingAs($this->makeUser('staff'))
            ->get(route('products.index', ['category' => $a->id]))
            ->assertSee('Item Kategori A')
            ->assertDontSee('Item Kategori B');
    }

    public function test_pagination_keeps_the_search_query(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            $this->makeProduct(['name' => "Gamma {$i}"]);
        }

        $this->actingAs($this->makeUser('staff'))
            ->get(route('products.index', ['q' => 'Gamma']))
            ->assertOk()
            ->assertSee('page=2', false)
            ->assertSee('q=Gamma', false);
    }

    public function test_catalog_hides_inactive_products_from_customers(): void
    {
        $this->makeProduct(['name' => 'Tampil Aktif']);
        $hidden = $this->makeProduct(['name' => 'Sembunyi Nonaktif', 'is_active' => false]);

        $customer = $this->makeUser('customer');

        $this->actingAs($customer)->get(route('catalog'))
            ->assertSee('Tampil Aktif')
            ->assertDontSee('Sembunyi Nonaktif');

        $this->actingAs($customer)->get(route('catalog.show', $hidden->slug))->assertNotFound();
        $this->actingAs($this->makeUser('staff'))->get(route('catalog.show', $hidden->slug))->assertOk();
    }
}