<?php

namespace App\Console\Commands;

use App\Models\Sale;
use App\Services\PaymentService;
use App\Services\SaleService;
use DomainException;
use Illuminate\Console\Command;

class ExpirePendingSales extends Command
{
    protected $signature = 'sales:expire';

    protected $description = 'Batalkan pesanan online yang lewat batas bayar dan kembalikan stoknya';

    public function handle(SaleService $service, PaymentService $payments): int
    {
        $count = 0;

        Sale::where('status', 'pending')
            ->where('channel', 'online')
            ->where('created_at', '<=', now()->subMinutes(Sale::PAY_WINDOW_MINUTES))
            ->orderBy('id')
            ->each(function (Sale $sale) use ($service, $payments, &$count) {
                // Jangan kedaluwarsakan pesanan yang ternyata sudah dibayar
                $payments->syncSale($sale);
                $sale->refresh();

                if ($sale->status !== 'pending') {
                    return;
                }

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