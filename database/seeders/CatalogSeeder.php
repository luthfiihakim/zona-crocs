<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Size;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        mt_srand(20261020); // stok acak tapi hasilnya sama setiap seeding

        $catalog = [
            ['Classic Edition', 'CLS', 279000, 300000, [
                'Classic Clog' => [
                    'Maroon', 'Coffee', 'Lilac', 'Pink Crush', 'Bone', 'Quartz',
                    'Blue Forest', 'Pink Milk', 'Elephant', 'Army', 'Navy',
                    'Atmosphere', 'Black', 'White',
                ],
            ]],
            ['Collab Edition', 'COL', 379000, 400000, [
                'Batman' => ['Original'],
                'Minion' => ['Original'],
                'McQueen' => ['Original'],
                'Itachi' => ['Original'],
                'Toy Story' => ['Original'],
                'Patrick' => ['Original'],
                'R2D2' => ['Original'],
                'Snoopy' => ['Original'],
                'Minecraft' => ['Original'],
                'Darth Vader' => ['Original'],
                'Mater' => ['Original'],
                'Yoda' => ['Original'],
                'Lego' => ['Black', 'Pink'],
                'Loopy' => ['Original'],
                'Sasuke' => ['Original'],
                'Kakashi' => ['Original'],
                'Chopper' => ['Original'],
                'Zoro' => ['Original'],
                'Luffy' => ['Original'],
                'Bape' => ['Pink', 'Green', 'Blue', 'Black'],
            ]],
            ['Platform Edition', 'PLT', 379000, 400000, [
                'Platform Chunky' => ['Black', 'Pearl'],
                'Platform Classic' => ['Black'],
            ]],
            ['Geo Edition', 'GEO', 379000, 400000, [
                'Geo Clog' => ['Black', 'Navy', 'Cream', 'Pale Yellow', 'White', 'Pink'],
            ]],
        ];

        $sizes = Size::orderBy('sort_order')->get();

        DB::transaction(function () use ($catalog, $sizes) {
            foreach ($catalog as [$catName, $prefix, $price, $compare, $products]) {
                $category = Category::create([
                    'name' => $catName,
                    'slug' => Str::slug($catName),
                ]);

                foreach ($products as $name => $colors) {
                    $product = Product::create([
                        'category_id' => $category->id,
                        'name' => $name,
                        'slug' => Str::slug($name),
                        'base_price' => $price,
                        'compare_price' => $compare,
                    ]);

                    $rows = [];
                    foreach ($colors as $color) {
                        foreach ($sizes as $size) {
                            $parts = [$prefix, Str::slug($name)];
                            if ($color !== 'Original') {
                                $parts[] = Str::slug($color);
                            }
                            $parts[] = Str::before($size->us_label, '/');

                            $rows[] = [
                                'product_id' => $product->id,
                                'size_id' => $size->id,
                                'sku' => strtoupper(implode('-', $parts)),
                                'color' => $color,
                                'stock' => $size->is_preorder ? 0 : mt_rand(0, 10),
                                'min_stock' => 2,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                    }

                    ProductVariant::insert($rows);
                }
            }
        });
    }
}