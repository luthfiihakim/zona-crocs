<?php

namespace Tests\Feature;

use App\Models\Sale;
use App\Services\SaleService;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsShop;
use Tests\TestCase;

class SaleTest extends TestCase
{
    use RefreshDatabase, BuildsShop;

    public function test_pos_sale_decreases_stock_and_uses_database_price(): void
    {
        $variant = $this->makeVariant(stock: 5, price: 379000);

        $this->actingAs($this->makeUser('staff'))->post(route('sales.store'), [
            'payment_method' => 'cash',
            'items' => [['sku' => $variant->sku, 'quantity' => 2, 'unit_price' => 1]], // harga dari form diabaikan
        ])->assertRedirect();

        $sale = Sale::with('items')->firstOrFail();
        $this->assertSame(3, $variant->fresh()->stock);
        $this->assertSame(758000, (int) $sale->total);
        $this->assertSame(379000, (int) $sale->items->first()->unit_price);
        $this->assertSame('paid', $sale->status);
        $this->assertSame('pos', $sale->channel);
    }

    public function test_sale_with_insufficient_stock_is_rejected_without_changes(): void
    {
        $variant = $this->makeVariant(stock: 1);

        $this->actingAs($this->makeUser('staff'))->post(route('sales.store'), [
            'payment_method' => 'cash',
            'items' => [['sku' => $variant->sku, 'quantity' => 3]],
        ])->assertSessionHas('error');

        $this->assertSame(1, $variant->fresh()->stock);
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_sale_with_one_bad_item_changes_nothing(): void
    {
        $ok = $this->makeVariant(stock: 5);
        $short = $this->makeVariant(stock: 1);

        $this->actingAs($this->makeUser('staff'))->post(route('sales.store'), [
            'payment_method' => 'cash',
            'items' => [
                ['sku' => $ok->sku, 'quantity' => 1],
                ['sku' => $short->sku, 'quantity' => 3],
            ],
        ])->assertSessionHas('error');

        $this->assertSame(5, $ok->fresh()->stock);
        $this->assertSame(1, $short->fresh()->stock);
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_preorder_size_is_not_sold_from_stock(): void
    {
        $variant = $this->makeVariant(stock: 5, po: true);

        $this->actingAs($this->makeUser('staff'))->post(route('sales.store'), [
            'payment_method' => 'cash',
            'items' => [['sku' => $variant->sku, 'quantity' => 1]],
        ])->assertSessionHas('error');

        $this->assertSame(5, $variant->fresh()->stock);
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_deleted_product_sku_cannot_be_sold(): void
    {
        $variant = $this->makeVariant(stock: 5);
        $variant->product->delete();

        $this->actingAs($this->makeUser('staff'))->post(route('sales.store'), [
            'payment_method' => 'cash',
            'items' => [['sku' => $variant->sku, 'quantity' => 1]],
        ])->assertSessionHas('error');

        $this->assertSame(5, $variant->fresh()->stock);
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_last_pair_cannot_be_sold_twice(): void
    {
        $staff = $this->makeUser('staff');
        $variant = $this->makeVariant(stock: 1);

        $this->placePosSale($staff, $variant, 1);
        $this->assertSame(0, $variant->fresh()->stock);

        $this->actingAs($staff)->post(route('sales.store'), [
            'payment_method' => 'cash',
            'items' => [['sku' => $variant->sku, 'quantity' => 1]],
        ])->assertSessionHas('error');

        $this->assertSame(0, $variant->fresh()->stock);
        $this->assertDatabaseCount('sales', 1);
    }

    public function test_failed_item_rolls_back_whole_sale(): void
    {
        $staff = $this->makeUser('staff');
        $a = $this->makeVariant(stock: 5);
        $b = $this->makeVariant(stock: 5);

        try {
            app(SaleService::class)->create([
                'items' => [
                    ['sku' => $a->sku, 'quantity' => 2],
                    ['sku' => $b->sku, 'quantity' => 0], // ditolak CHECK database saat disimpan
                ],
            ], ['customer_id' => null, 'user_id' => $staff->id, 'channel' => 'pos', 'status' => 'paid',
                'payment_method' => 'cash', 'paid_at' => now()]);

            $this->fail('Seharusnya gagal karena jumlah 0 melanggar constraint database.');
        } catch (QueryException $e) {
            // diharapkan
        }

        $this->assertSame(5, $a->fresh()->stock);
        $this->assertSame(5, $b->fresh()->stock);
        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('sale_items', 0);
    }

    public function test_admin_can_cancel_sale_and_stock_returns(): void
    {
        $staff = $this->makeUser('staff');
        $variant = $this->makeVariant(stock: 5);
        $sale = $this->placePosSale($staff, $variant, 2);
        $this->assertSame(3, $variant->fresh()->stock);

        $this->actingAs($this->makeUser('admin'))->post(route('sales.cancel', $sale))->assertRedirect();

        $this->assertSame(5, $variant->fresh()->stock);
        $this->assertSame('cancelled', $sale->fresh()->status);
    }

    public function test_staff_cannot_cancel_sale(): void
    {
        $staff = $this->makeUser('staff');
        $variant = $this->makeVariant(stock: 5);
        $sale = $this->placePosSale($staff, $variant, 2);

        $this->actingAs($staff)->post(route('sales.cancel', $sale))->assertForbidden();

        $this->assertSame(3, $variant->fresh()->stock);
        $this->assertSame('paid', $sale->fresh()->status);
    }

    public function test_sale_cannot_be_cancelled_twice(): void
    {
        $variant = $this->makeVariant(stock: 5);
        $sale = $this->placePosSale($this->makeUser('staff'), $variant, 2);
        $service = app(SaleService::class);

        $service->cancel($sale);
        $this->assertSame(5, $variant->fresh()->stock);

        try {
            $service->cancel($sale->fresh());
            $this->fail('Pembatalan kedua seharusnya ditolak.');
        } catch (DomainException) {
            // diharapkan
        }

        $this->assertSame(5, $variant->fresh()->stock); // stok tidak dikembalikan dua kali
    }
}