<?php

namespace Tests\Feature;

use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\PurchaseService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsShop;
use Tests\TestCase;

class PurchaseTest extends TestCase
{
    use RefreshDatabase, BuildsShop;

    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->supplier = $this->makeSupplier();
    }

    private function payload(ProductVariant $v, int $qty = 5, int $cost = 200000): array
    {
        return [
            'supplier_id' => $this->supplier->id,
            'purchase_date' => now()->toDateString(),
            'total' => 1, // dicoba dimanipulasi, harus diabaikan
            'items' => [['sku' => $v->sku, 'quantity' => $qty, 'unit_cost' => $cost]],
        ];
    }

    public function test_purchase_increases_stock_and_computes_total_on_server(): void
    {
        $staff = $this->makeUser('staff');
        $variant = $this->makeVariant(stock: 3);

        $this->actingAs($staff)->post(route('purchases.store'), $this->payload($variant))->assertRedirect();

        $purchase = Purchase::firstOrFail();
        $this->assertSame(8, $variant->fresh()->stock);
        $this->assertSame(1000000, (int) $purchase->total);
        $this->assertSame('received', $purchase->status);
        $this->assertStringStartsWith('PUR-', $purchase->number);
    }

    public function test_purchase_with_unknown_sku_is_rejected_and_creates_nothing(): void
    {
        $payload = $this->payload($this->makeVariant());
        $payload['items'][0]['sku'] = 'TIDAK-ADA';

        $this->actingAs($this->makeUser('staff'))
            ->post(route('purchases.store'), $payload)
            ->assertSessionHasErrors('items.0.sku');

        $this->assertDatabaseCount('purchases', 0);
    }

    public function test_purchase_rejects_zero_quantity_and_future_date(): void
    {
        $staff = $this->makeUser('staff');
        $variant = $this->makeVariant();

        $zero = $this->payload($variant, 0);
        $this->actingAs($staff)->post(route('purchases.store'), $zero)
            ->assertSessionHasErrors('items.0.quantity');

        $future = $this->payload($variant);
        $future['purchase_date'] = now()->addDay()->toDateString();
        $this->actingAs($staff)->post(route('purchases.store'), $future)
            ->assertSessionHasErrors('purchase_date');

        $this->assertDatabaseCount('purchases', 0);
    }

    public function test_same_sku_on_two_rows_is_rejected(): void
    {
        $variant = $this->makeVariant();
        $payload = $this->payload($variant);
        $payload['items'][] = ['sku' => $variant->sku, 'quantity' => 1, 'unit_cost' => 1000];

        $this->actingAs($this->makeUser('staff'))
            ->post(route('purchases.store'), $payload)
            ->assertSessionHasErrors();

        $this->assertDatabaseCount('purchases', 0);
    }

    public function test_only_admin_can_cancel_and_cancel_restores_stock(): void
    {
        $staff = $this->makeUser('staff');
        $variant = $this->makeVariant(stock: 3);
        $this->actingAs($staff)->post(route('purchases.store'), $this->payload($variant));
        $purchase = Purchase::firstOrFail();

        $this->actingAs($staff)->post(route('purchases.cancel', $purchase))->assertForbidden();
        $this->assertSame(8, $variant->fresh()->stock);

        $this->actingAs($this->makeUser('admin'))->post(route('purchases.cancel', $purchase))->assertRedirect();
        $this->assertSame(3, $variant->fresh()->stock);
        $this->assertSame('cancelled', $purchase->fresh()->status);
    }

    public function test_cancel_is_rejected_when_stock_was_already_used(): void
    {
        $variant = $this->makeVariant(stock: 3);
        $this->actingAs($this->makeUser('staff'))->post(route('purchases.store'), $this->payload($variant));
        $purchase = Purchase::firstOrFail();

        $variant->update(['stock' => 2]); // sebagian barang sudah terjual

        $this->actingAs($this->makeUser('admin'))
            ->post(route('purchases.cancel', $purchase))
            ->assertSessionHas('error');

        $this->assertSame('received', $purchase->fresh()->status);
        $this->assertSame(2, $variant->fresh()->stock);
    }

    public function test_failed_item_rolls_back_whole_purchase(): void
    {
        $staff = $this->makeUser('staff');
        $a = $this->makeVariant(stock: 5);
        $b = $this->makeVariant(stock: 5);

        try {
            app(PurchaseService::class)->create([
                'supplier_id' => $this->supplier->id,
                'purchase_date' => now()->toDateString(),
                'items' => [
                    ['sku' => $a->sku, 'quantity' => 2, 'unit_cost' => 1000],
                    ['sku' => $b->sku, 'quantity' => 0, 'unit_cost' => 1000], // ditolak CHECK database
                ],
            ], $staff);

            $this->fail('Seharusnya gagal karena jumlah 0 melanggar constraint database.');
        } catch (QueryException $e) {
            // diharapkan
        }

        $this->assertSame(5, $a->fresh()->stock);
        $this->assertSame(5, $b->fresh()->stock);
        $this->assertDatabaseCount('purchases', 0);
        $this->assertDatabaseCount('purchase_items', 0);
    }
}