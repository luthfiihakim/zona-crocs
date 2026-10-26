<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class CheckProductImages extends Command
{
    protected $signature = 'images:check {--missing : Hanya tampilkan yang belum ada foto}';

    protected $description = 'Tampilkan produk dan warna yang sudah atau belum punya foto';

    public function handle(): int
    {
        $rows = [];

        Product::with(['variants:id,product_id,color', 'images'])->orderBy('name')->get()->each(function (Product $p) use (&$rows) {
            $colors = $p->variants->pluck('color')->unique()->sort()->values();
            $general = $p->images->whereNull('color')->count();

            foreach ($colors->isEmpty() ? collect(['-']) : $colors as $color) {
                $specific = $p->images
                    ->filter(fn ($i) => $i->color !== null && mb_strtolower($i->color) === mb_strtolower($color))
                    ->count();

                $rows[] = [
                    $p->name,
                    $color,
                    $specific ? "{$specific} (khusus warna)" : ($general ? "{$general} (umum)" : '0'),
                    ($specific || $general) ? 'ada' : 'BELUM',
                ];
            }
        });

        if ($this->option('missing')) {
            $rows = array_values(array_filter($rows, fn ($r) => $r[3] === 'BELUM'));
        }

        $this->table(['Produk', 'Warna', 'Foto', 'Status'], $rows);
        $this->info(count($rows).' baris'.($this->option('missing') ? ' yang belum punya foto.' : '.'));

        return self::SUCCESS;
    }
}