<?php

namespace Tests\Feature;

use App\Models\ProductVariant;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsShop;
use Tests\TestCase;

class OnlineOrderTest extends TestCase
{
    use RefreshDatabase, BuildsShop;

    private const ADDRESS = 'Penerima Uji, Jl. Contoh No. 1, Medan, 20111, 081234567890';

    private function addToCart(ProductVariant $variant, int $qty = 1)
    {
        return $this->post(route('cart.add'), ['variant_id' => $variant->id, 'quantity' => $qty]);
    }

    private function checkout()
    {
        return $this->post(route('checkout.store'), ['shipping_address' => self::ADDRESS]);
    }

    public function test_checkout_creates_pending_order_and_reserves_stock(): void
    {
        $customer = $this->makeUser('customer');
        $variant = $this->makeVariant(stock: 5);

        $this->actingAs($customer);
        $this->addToCart($variant, 2)->assertRedirect(route('cart.index'));
        $this->checkout()->assertRedirect();

        $sale = Sale::firstOrFail();
        $this->assertSame('pending', $sale->status);
        $this->assertSame('online', $sale->channel);
        $this->assertSame($customer->id, $sale->customer_id);
        $this->assertNull($sale->user_id);
        $this->assertSame(3, $variant->fresh()->stock);
        $this->assertEmpty(session('cart', []));
    }

    public function test_checkout_is_rejected_when_stock_changed_meanwhile(): void
    {
        $variant = $this->makeVariant(stock: 5);

        $this->actingAs($this->makeUser('customer'));
        $this->addToCart($variant, 3);
        $variant->update(['stock' => 1]); // terjual di tempat lain

        $this->checkout()->assertSessionHas('error');

        $this->assertDatabaseCount('sales', 0);
        $this->assertSame(1, $variant->fresh()->stock);
        $this->assertNotEmpty(session('cart'));
    }

    public function test_checkout_is_rejected_when_product_deactivated_after_adding_to_cart(): void
    {
        $variant = $this->makeVariant(stock: 5);

        $this->actingAs($this->makeUser('customer'));
        $this->addToCart($variant, 1);
        $variant->product->update(['is_active' => false]);

        $this->checkout()->assertRedirect(route('cart.index'));

        $this->assertDatabaseCount('sales', 0);
        $this->assertSame(5, $variant->fresh()->stock);
    }

    public function test_preorder_size_cannot_be_added_to_cart(): void
    {
        $variant = $this->makeVariant(stock: 5, po: true);

        $this->actingAs($this->makeUser('customer'));
        $this->addToCart($variant)->assertSessionHas('error');

        $this->assertEmpty(session('cart', []));
    }

    public function test_cannot_add_more_than_available_stock(): void
    {
        $variant = $this->makeVariant(stock: 2);

        $this->actingAs($this->makeUser('customer'));
        $this->addToCart($variant, 3)->assertSessionHas('error');

        $this->assertEmpty(session('cart', []));
    }

    public function test_customer_is_limited_to_three_pending_orders(): void
    {
        $customer = $this->makeUser('customer');

        for ($i = 0; $i < 3; $i++) {
            $this->placeOnlineOrder($customer, $this->makeVariant());
        }

        $variant = $this->makeVariant(stock: 5);
        $this->actingAs($customer);
        $this->addToCart($variant);
        $this->checkout()->assertSessionHas('error');

        $this->assertDatabaseCount('sales', 3);
        $this->assertSame(5, $variant->fresh()->stock);
    }

    public function test_expired_orders_are_cancelled_and_stock_returns(): void
    {
        $customer = $this->makeUser('customer');
        $oldVariant = $this->makeVariant(stock: 5);
        $newVariant = $this->makeVariant(stock: 5);
        $old = $this->placeOnlineOrder($customer, $oldVariant, 2);
        $fresh = $this->placeOnlineOrder($customer, $newVariant, 1);

        Sale::whereKey($old->id)->update(['created_at' => now()->subMinutes(61)]);

        $this->artisan('sales:expire')->assertSuccessful();

        $this->assertSame('expired', $old->fresh()->status);
        $this->assertSame(5, $oldVariant->fresh()->stock);
        $this->assertSame('pending', $fresh->fresh()->status);
        $this->assertSame(4, $newVariant->fresh()->stock);
    }

    public function test_customer_can_cancel_own_pending_order_and_stock_returns(): void
    {
        $owner = $this->makeUser('customer');
        $variant = $this->makeVariant(stock: 5);
        $sale = $this->placeOnlineOrder($owner, $variant, 2);

        $this->actingAs($owner)->post(route('orders.cancel', $sale))->assertRedirect();

        $this->assertSame('cancelled', $sale->fresh()->status);
        $this->assertSame(5, $variant->fresh()->stock);
    }
}