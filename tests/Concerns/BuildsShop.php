<?php

namespace Tests\Concerns;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\Size;
use App\Models\Supplier;
use App\Models\User;
use App\Services\SaleService;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;

trait BuildsShop
{
    protected function makeUser(string $role = 'customer', array $attrs = []): User
    {
        $user = new User(array_merge([
            'name' => ucfirst($role).' '.Str::random(4),
            'email' => $role.'-'.Str::lower(Str::random(8)).'@example.com',
            'password' => 'password123',
        ], $attrs));
        $user->role = $role;
        $user->is_active = true;
        $user->save();

        return $user;
    }

    protected function makeCategory(?string $name = null): Category
    {
        $name ??= 'Kategori '.Str::random(6);

        return Category::create(['name' => $name, 'slug' => Str::slug($name).'-'.Str::lower(Str::random(4))]);
    }

    protected function makeSupplier(): Supplier
    {
        return Supplier::create(['name' => 'Supplier '.Str::random(5)]);
    }

    protected function makeProduct(array $attrs = []): Product
    {
        $name = $attrs['name'] ?? 'Produk '.Str::random(6);

        return Product::create(array_merge([
            'category_id' => $attrs['category_id'] ?? $this->makeCategory()->id,
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'base_price' => 379000,
            'compare_price' => 400000,
            'is_active' => true,
        ], $attrs));
    }

    protected function makeVariant(int $stock = 5, int $price = 379000, bool $po = false, bool $active = true): ProductVariant
    {
        $product = $this->makeProduct([
            'base_price' => $price,
            'compare_price' => $price + 20000,
            'is_active' => $active,
        ]);

        $size = Size::create([
            'us_label' => 'T'.Str::upper(Str::random(8)),
            'eu_range' => '40-41',
            'insole_cm' => 26,
            'group' => 'adult',
            'is_preorder' => $po,
            'sort_order' => 1,
        ]);

        return ProductVariant::create([
            'product_id' => $product->id,
            'size_id' => $size->id,
            'sku' => 'TST-'.Str::upper(Str::random(8)),
            'color' => 'Original',
            'stock' => $stock,
            'min_stock' => 2,
        ]);
    }

    protected function placePosSale(User $staff, ProductVariant $variant, int $qty = 1): Sale
    {
        return app(SaleService::class)->create(
            ['items' => [['sku' => $variant->sku, 'quantity' => $qty]]],
            ['customer_id' => null, 'user_id' => $staff->id, 'channel' => 'pos', 'status' => 'paid',
             'payment_method' => 'cash', 'paid_at' => now()]
        );
    }

    protected function placeOnlineOrder(User $customer, ProductVariant $variant, int $qty = 1): Sale
    {
        return app(SaleService::class)->create(
            ['items' => [['sku' => $variant->sku, 'quantity' => $qty]]],
            ['customer_id' => $customer->id, 'user_id' => null, 'channel' => 'online', 'status' => 'pending',
             'payment_method' => null, 'shipping_address' => 'Alamat uji lengkap No. 123, Medan', 'paid_at' => null],
            true
        );
    }

    protected function fakeImage(string $name = 'foto.png', int $w = 400, int $h = 400): UploadedFile
    {
        $raw = str_repeat("\x00".str_repeat("\xff\xff\xff", $w), $h);
        $chunk = fn (string $type, string $data) => pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));

        $png = "\x89PNG\r\n\x1a\n"
            .$chunk('IHDR', pack('NNCCCCC', $w, $h, 8, 2, 0, 0, 0))
            .$chunk('IDAT', gzcompress($raw, 9))
            .$chunk('IEND', '');

        return UploadedFile::fake()->createWithContent($name, $png);
    }
}