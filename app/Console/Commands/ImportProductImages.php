<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductImage;
use App\Services\ProductImageService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ImportProductImages extends Command
{
    protected $signature = 'images:import';

    protected $description = 'Daftarkan berkas di public/images/products ke database (aman dijalankan berulang)';

    public function handle(): int
    {
        $base = public_path(ProductImageService::DIR);

        if (! is_dir($base)) {
            $this->error("Folder {$base} tidak ditemukan.");

            return self::FAILURE;
        }

        $added = $skipped = 0;

        foreach (glob($base.'/*', GLOB_ONLYDIR) ?: [] as $dir) {
            $slug = basename($dir);
            $product = Product::withTrashed()->where('slug', $slug)->first();

            if (! $product) {
                $this->warn("Lewati folder '{$slug}': tidak ada produk dengan slug itu.");
                continue;
            }

            $colors = $product->variants()->pluck('color')->unique()
                ->mapWithKeys(fn ($c) => [Str::slug($c) => $c]);

            $files = glob($dir.'/*.{jpg,jpeg,png,webp}', GLOB_BRACE) ?: [];
            sort($files);

            foreach ($files as $file) {
                $relative = ProductImageService::DIR.'/'.$slug.'/'.basename($file);

                if (ProductImage::where('path', $relative)->exists()) {
                    $skipped++;
                    continue;
                }

                preg_match('/^(.*?)(?:-(\d+))?$/', pathinfo($file, PATHINFO_FILENAME), $m);
                $key = $m[1];
                $order = isset($m[2]) ? (int) $m[2] : 1;

                if ($key === 'cover') {
                    $color = null;
                    $order = 0;
                } elseif ($key === 'default') {
                    $color = null;
                } elseif ($colors->has($key)) {
                    $color = $colors[$key];
                } else {
                    $this->warn("Lewati {$slug}/".basename($file).": warna '{$key}' tidak ada pada produk.");
                    continue;
                }

                ProductImage::create(['product_id' => $product->id, 'color' => $color, 'path' => $relative, 'sort_order' => $order]);
                $added++;
            }
        }

        $this->info("{$added} foto didaftarkan, {$skipped} sudah terdaftar.");

        return self::SUCCESS;
    }
}