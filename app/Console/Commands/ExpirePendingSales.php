<?php

namespace App\Console\Commands;

use App\Models\Sale;
use App\Services\SaleService;
use DomainException;
use Illuminate\Console\Command;

class ExpirePendingSales extends Command
{
    protected $signature = 'sales:expire';

    protected $description = 'Batalkan pesanan online yang lewat batas bayar dan kembalikan stoknya';

    public function handle(SaleService $service): int
    {
        $count = 0;

        Sale::where('status', 'pending')
            ->where('channel', 'online')
            ->where('created_at', '<=', now()->subMinutes(Sale::PAY_WINDOW_MINUTES))
            ->orderBy('id')
            ->each(function (Sale $sale) use ($service, &$count) {
                try {
                    $service->cancel($sale, 'expired');
                    $count++;
                } catch (DomainException $e) {
                    $this->warn("{$sale->number}: {$e->getMessage()}");
                }
            });

        $this->info("{$count} pesanan kedaluwarsa dibatalkan.");

        return self::SUCCESS;
    }
}