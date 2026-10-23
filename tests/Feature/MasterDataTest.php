<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsShop;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase, BuildsShop;

    public function test_product_rejects_negative_price(): void
    {
        $category = $this->makeCategory();

        $this->actingAs($this->makeUser('staff'))->post(route('products.store'), [
            'category_id' => $category->id,
            'name' => 'Produk Uji',
            'base_price' => -5,
            'is_active' => 1,
        ])->assertSessionHasErrors('base_price');

        $this->assertDatabaseCount('products', 0);
    }

    public function test_product_rejects_compare_price_below_price(): void
    {
        $category = $this->makeCategory();

        $this->actingAs($this->makeUser('staff'))->post(route('products.store'), [
            'category_id' => $category->id,
            'name' => 'Produk Uji',
            'base_price' => 379000,
            'compare_price' => 100000,
            'is_active' => 1,
        ])->assertSessionHasErrors('compare_price');
    }

    public function test_category_name_must_be_unique(): void
    {
        $category = $this->makeCategory('Geo Edition');

        $this->actingAs($this->makeUser('staff'))
            ->post(route('categories.store'), ['name' => 'Geo Edition'])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('categories', 1);
    }

    public function test_variant_sku_must_be_unique(): void
    {
        $a = $this->makeVariant();
        $b = $this->makeVariant();

        $this->actingAs($this->makeUser('staff'))
            ->put(route('variants.update', $a), ['sku' => $b->sku, 'color' => 'Original', 'min_stock' => 2])
            ->assertSessionHasErrors('sku');
    }

    public function test_product_with_stock_cannot_be_deleted(): void
    {
        $variant = $this->makeVariant(stock: 3);

        $this->actingAs($this->makeUser('admin'))
            ->delete(route('products.destroy', $variant->product_id))
            ->assertSessionHas('error');

        $this->assertNotSoftDeleted('products', ['id' => $variant->product_id]);
    }

    public function test_product_without_stock_can_be_soft_deleted(): void
    {
        $variant = $this->makeVariant(stock: 0);

        $this->actingAs($this->makeUser('admin'))
            ->delete(route('products.destroy', $variant->product_id))
            ->assertRedirect();

        $this->assertSoftDeleted('products', ['id' => $variant->product_id]);
    }

    public function test_database_rejects_negative_stock(): void
    {
        $variant = $this->makeVariant(stock: 1);

        $this->expectException(QueryException::class);

        ProductVariant::whereKey($variant->id)->update(['stock' => -1]);
    }

    public function test_database_rejects_duplicate_sku(): void
    {
        $variant = $this->makeVariant();

        $this->expectException(QueryException::class);

        ProductVariant::create([
            'product_id' => $variant->product_id,
            'size_id' => $variant->size_id,
            'sku' => $variant->sku,
            'color' => 'Lain',
            'stock' => 0,
            'min_stock' => 2,
        ]);
    }
}