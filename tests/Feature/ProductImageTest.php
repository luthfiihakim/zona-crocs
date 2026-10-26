<?php

namespace Tests\Feature;

use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Concerns\BuildsShop;
use Tests\TestCase;
use App\Services\ProductImageService;

class ProductImageTest extends TestCase
{
    use RefreshDatabase, BuildsShop;

    private array $dirsBefore = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->dirsBefore = glob(public_path('images/products/*'), GLOB_ONLYDIR) ?: [];
    }

    protected function tearDown(): void
    {
        // hapus hanya folder yang dibuat oleh tes ini
        foreach (glob(public_path('images/products/*'), GLOB_ONLYDIR) ?: [] as $dir) {
            if (! in_array($dir, $this->dirsBefore, true)) {
                array_map('unlink', glob($dir.'/*') ?: []);
                rmdir($dir);
            }
        }

        parent::tearDown();
    }

    public function test_staff_can_upload_several_photos_for_a_product(): void
    {
        $product = $this->makeProduct();

        $this->actingAs($this->makeUser('staff'))
            ->post(route('products.images.store', $product), [
                'photos' => [$this->fakeImage('a.png'), $this->fakeImage('b.png')],
            ])->assertRedirect(route('products.show', $product));

        $images = $product->images()->get();
        $this->assertCount(2, $images);
        $this->assertSame([1, 2], $images->pluck('sort_order')->all());
        $images->each(fn ($i) => $this->assertFileExists(public_path($i->path)));
    }

    public function test_invalid_files_are_rejected_and_nothing_is_saved(): void
    {
        $product = $this->makeProduct();
        $staff = $this->makeUser('staff');
        $url = route('products.images.store', $product);

        $this->actingAs($staff)->post($url, ['photos' => [UploadedFile::fake()->create('skrip.php', 5, 'text/plain')]])
            ->assertSessionHasErrors('photos.0');

        $this->actingAs($staff)->post($url, ['photos' => [$this->fakeImage('kecil.png', 100, 100)]])
            ->assertSessionHasErrors('photos.0');

        $this->actingAs($staff)->post($url, ['photos' => array_map(fn ($i) => $this->fakeImage("f{$i}.png"), range(1, 9))])
            ->assertSessionHasErrors('photos');

        $this->assertDatabaseCount('product_images', 0);
    }

    public function test_color_must_belong_to_the_product(): void
    {
        $product = $this->makeVariant()->product;
        $staff = $this->makeUser('staff');
        $url = route('products.images.store', $product);

        $this->actingAs($staff)->post($url, ['photos' => [$this->fakeImage()], 'color' => 'Merah Jambu'])
            ->assertSessionHasErrors('color');

        $this->actingAs($staff)->post($url, ['photos' => [$this->fakeImage()], 'color' => 'Original'])
            ->assertRedirect();

        $this->assertDatabaseHas('product_images', ['product_id' => $product->id, 'color' => 'Original']);
    }

    public function test_replacing_a_photo_swaps_the_file_and_keeps_the_record(): void
    {
        $product = $this->makeProduct();
        $staff = $this->makeUser('staff');

        $this->actingAs($staff)->post(route('products.images.store', $product), ['photos' => [$this->fakeImage('lama.png')]]);
        $image = $product->images()->firstOrFail();
        $oldPath = $image->path;

        $this->actingAs($staff)->put(route('product-images.update', $image), [
            'color' => null,
            'sort_order' => 3,
            'file' => $this->fakeImage('baru.png'),
        ])->assertRedirect(route('products.show', $product));

        $image->refresh();
        $this->assertNotSame($oldPath, $image->path);
        $this->assertFileDoesNotExist(public_path($oldPath));
        $this->assertFileExists(public_path($image->path));
        $this->assertSame(3, $image->sort_order);
        $this->assertDatabaseCount('product_images', 1);
    }

    public function test_deleting_a_photo_removes_record_and_file(): void
    {
        $product = $this->makeProduct();
        $staff = $this->makeUser('staff');

        $this->actingAs($staff)->post(route('products.images.store', $product), ['photos' => [$this->fakeImage()]]);
        $image = $product->images()->firstOrFail();
        $path = $image->path;

        $this->actingAs($staff)->delete(route('product-images.destroy', $image))->assertRedirect();

        $this->assertDatabaseCount('product_images', 0);
        $this->assertFileDoesNotExist(public_path($path));
    }

    public function test_customer_cannot_manage_photos(): void
    {
        $product = $this->makeProduct();
        $customer = $this->makeUser('customer');
        $image = ProductImage::create([
            'product_id' => $product->id, 'color' => null,
            'path' => "images/products/{$product->slug}/x.jpg", 'sort_order' => 1,
        ]);

        $this->actingAs($customer)->post(route('products.images.store', $product), ['photos' => [$this->fakeImage()]])->assertForbidden();
        $this->actingAs($customer)->delete(route('product-images.destroy', $image))->assertForbidden();

        $this->assertDatabaseCount('product_images', 1);
    }

    public function test_color_photos_come_first_then_general_photos(): void
    {
        $product = $this->makeProduct();

        foreach ([['Black', 2, 'hitam-2'], ['Black', 1, 'hitam-1'], [null, 1, 'umum']] as [$color, $order, $file]) {
            ProductImage::create([
                'product_id' => $product->id, 'color' => $color,
                'path' => "images/products/{$product->slug}/{$file}.jpg", 'sort_order' => $order,
            ]);
        }

        $name = fn (array $urls) => collect($urls)->map(fn ($u) => pathinfo($u, PATHINFO_FILENAME))->all();
        $product->load('images');

        $this->assertSame(['hitam-1', 'hitam-2', 'umum'], $name($product->imageUrls('black')));
        $this->assertSame(['umum'], $name($product->imageUrls('White')));
        $this->assertSame([], $this->makeProduct()->imageUrls('Black'));
    }

    public function test_photos_can_be_attached_while_creating_a_product(): void
    {
        $category = $this->makeCategory();

        $this->actingAs($this->makeUser('staff'))->post(route('products.store'), [
            'category_id' => $category->id,
            'name' => 'Produk Foto Baru',
            'base_price' => 379000,
            'is_active' => 1,
            'photos' => [$this->fakeImage('depan.png'), $this->fakeImage('samping.png')],
        ])->assertRedirect();

        $this->assertDatabaseCount('product_images', 2);
    }

    public function test_customer_gallery_lists_all_photos_in_order(): void
    {
        $product = $this->makeVariant(stock: 3)->product;

        foreach ([2, 1, 3] as $order) {
            ProductImage::create([
                'product_id' => $product->id, 'color' => null,
                'path' => "images/products/{$product->slug}/foto-{$order}.jpg", 'sort_order' => $order,
            ]);
        }

        $gallery = $this->actingAs($this->makeUser('customer'))
            ->get(route('catalog.show', $product->slug))->assertOk()->viewData('gallery');

        $names = collect($gallery['Original'])->map(fn ($u) => pathinfo($u, PATHINFO_FILENAME))->all();
        $this->assertSame(['foto-1', 'foto-2', 'foto-3'], $names);
    }

    public function test_edit_form_can_add_replace_and_delete_photos_in_one_save(): void
    {
        $product = $this->makeVariant()->product;
        $service = app(ProductImageService::class);

        $a = $service->add($product, $this->fakeImage('a.png'));
        $b = $service->add($product, $this->fakeImage('b.png'));
        $oldA = $a->path;
        $oldB = $b->path;

        $this->actingAs($this->makeUser('staff'))->put(route('products.update', $product), [
            'category_id' => $product->category_id,
            'name' => $product->name,
            'base_price' => 379000,
            'is_active' => 1,
            'images' => [
                $a->id => ['sort_order' => 1, 'delete' => '1'],
                $b->id => ['color' => 'Original', 'sort_order' => 2, 'file' => $this->fakeImage('ganti.png')],
            ],
            'photos' => [$this->fakeImage('n1.png'), $this->fakeImage('n2.png')],
            'photo_colors' => ['Original', ''],
        ])->assertRedirect(route('products.show', $product));

        $this->assertDatabaseMissing('product_images', ['id' => $a->id]);
        $this->assertFileDoesNotExist(public_path($oldA));

        $b->refresh();
        $this->assertSame('Original', $b->color);
        $this->assertNotSame($oldB, $b->path);
        $this->assertFileDoesNotExist(public_path($oldB));

        $images = $product->images()->get();
        $this->assertCount(3, $images);
        $newColors = $images->where('id', '>', $b->id)->pluck('color')->all();
        $this->assertEqualsCanonicalizing(['Original', null], $newColors);
        $images->each(fn ($i) => $this->assertFileExists(public_path($i->path)));
    }

    public function test_edit_form_cannot_touch_photos_of_another_product(): void
    {
        $mine = $this->makeProduct();
        $other = $this->makeProduct();
        $foreign = app(ProductImageService::class)->add($other, $this->fakeImage('milik-lain.png'));

        $this->actingAs($this->makeUser('staff'))->put(route('products.update', $mine), [
            'category_id' => $mine->category_id,
            'name' => $mine->name,
            'base_price' => 379000,
            'is_active' => 1,
            'images' => [$foreign->id => ['delete' => '1']],
        ])->assertRedirect();

        $this->assertDatabaseHas('product_images', ['id' => $foreign->id]);
        $this->assertFileExists(public_path($foreign->path));
    }

    public function test_edit_form_rejects_a_color_the_product_does_not_have(): void
    {
        $product = $this->makeVariant()->product;

        $this->actingAs($this->makeUser('staff'))->put(route('products.update', $product), [
            'category_id' => $product->category_id,
            'name' => $product->name,
            'base_price' => 379000,
            'is_active' => 1,
            'photos' => [$this->fakeImage()],
            'photo_colors' => ['Merah Jambu'],
        ])->assertSessionHasErrors('photo_colors.0');

        $this->assertDatabaseCount('product_images', 0);
    }
}