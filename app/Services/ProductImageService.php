<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ProductImageService
{
    public const DIR = 'images/products';

    public function add(Product $product, UploadedFile $file, ?string $color = null): ProductImage
    {
        $path = $this->move($product, $file);

        try {
            $last = ProductImage::where('product_id', $product->id)
                ->when($color === null, fn ($q) => $q->whereNull('color'), fn ($q) => $q->where('color', $color))
                ->max('sort_order');

            return ProductImage::create([
                'product_id' => $product->id,
                'color' => $color,
                'path' => $path,
                'sort_order' => ((int) $last) + 1,
            ]);
        } catch (\Throwable $e) {
            File::delete(public_path($path));
            throw $e;
        }
    }

    public function addMany(Product $product, array $files, ?string $color = null): int
    {
        $count = 0;

        foreach ($files as $file) {
            if ($file instanceof UploadedFile && $file->isValid()) {
                $this->add($product, $file, $color);
                $count++;
            }
        }

        return $count;
    }

    public function replace(ProductImage $image, UploadedFile $file): void
    {
        $old = $image->path;
        $new = $this->move($image->product, $file);

        try {
            $image->update(['path' => $new]);
        } catch (\Throwable $e) {
            File::delete(public_path($new));
            throw $e;
        }

        File::delete(public_path($old));
    }

    public function delete(ProductImage $image): void
    {
        $path = $image->path;
        $image->delete();
        File::delete(public_path($path));
    }

    /** Terapkan semua perubahan foto dari satu form Ubah Produk. */
    public function syncFromForm(Product $product, array $data, array $files, array $newFiles, array $newColors): array
    {
        $stats = ['added' => 0, 'updated' => 0, 'replaced' => 0, 'deleted' => 0];

        // hanya foto milik produk ini yang boleh disentuh
        foreach ($product->images()->get() as $image) {
            $row = $data[$image->id] ?? null;

            if ($row === null) {
                continue;
            }

            if (! empty($row['delete'])) {
                $this->delete($image);
                $stats['deleted']++;
                continue;
            }

            $changes = [];
            if (array_key_exists('color', $row)) {
                $changes['color'] = $row['color'] ?: null;
            }
            if (isset($row['sort_order']) && $row['sort_order'] !== '') {
                $changes['sort_order'] = (int) $row['sort_order'];
            }

            if ($changes) {
                $image->fill($changes);
                if ($image->isDirty()) {
                    $image->save();
                    $stats['updated']++;
                }
            }

            $file = $files[$image->id]['file'] ?? null;
            if ($file instanceof UploadedFile && $file->isValid()) {
                $this->replace($image, $file);
                $stats['replaced']++;
            }
        }

        foreach (array_values($newFiles) as $i => $file) {
            if ($file instanceof UploadedFile && $file->isValid()) {
                $this->add($product, $file, ($newColors[$i] ?? null) ?: null);
                $stats['added']++;
            }
        }

        return $stats;
    }
    
    private function move(Product $product, UploadedFile $file): string
    {
        $dir = self::DIR.'/'.$product->slug;
        // nama berkas dibuat sendiri; ekstensi dari isi berkas, bukan dari nama kiriman pengguna
        $name = Str::lower(Str::random(16)).'.'.$file->extension();

        $file->move(public_path($dir), $name);

        return $dir.'/'.$name;
    }
}