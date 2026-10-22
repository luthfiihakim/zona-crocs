<?php

namespace App\Services;

use App\Models\Sale;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MidtransService
{
    private function http(): PendingRequest
    {
        $key = (string) config('services.midtrans.server_key');

        if ($key === '') {
            throw new RuntimeException('Kunci Midtrans belum diatur di .env.');
        }

        return Http::withBasicAuth($key, '')->acceptJson()->asJson()->timeout(15);
    }

    private function snapUrl(): string
    {
        return config('services.midtrans.is_production')
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';
    }

    private function apiUrl(): string
    {
        return config('services.midtrans.is_production')
            ? 'https://api.midtrans.com/v2'
            : 'https://api.sandbox.midtrans.com/v2';
    }

    public function createSnapToken(Sale $sale, string $orderId, int $minutes): string
    {
        $sale->loadMissing(['customer', 'items.variant.product', 'items.variant.size']);

        $items = $sale->items->map(fn ($i) => [
            'id' => $i->variant->sku,
            'price' => (int) $i->unit_price,
            'quantity' => (int) $i->quantity,
            'name' => mb_substr($i->variant->product->name.' '.$i->variant->color.' '.$i->variant->size->us_label, 0, 50),
        ])->values()->all();

        $payload = [
            'transaction_details' => ['order_id' => $orderId, 'gross_amount' => (int) $sale->total],
            'item_details' => $items,
            'customer_details' => ['first_name' => mb_substr((string) $sale->customer?->name, 0, 50)],
            'expiry' => ['unit' => 'minutes', 'duration' => max(1, $minutes)],
            'callbacks' => ['finish' => route('orders.show', $sale)],
        ];

        $res = $this->http()->post($this->snapUrl(), $payload);

        if (! $res->successful() || ! $res->json('token')) {
            Log::error('Midtrans snap gagal', ['status' => $res->status(), 'body' => $res->json()]);
            throw new RuntimeException('Gagal membuat transaksi pembayaran. Coba lagi sebentar lagi.');
        }

        return $res->json('token');
    }

    public function status(string $orderId): ?array
    {
        try {
            $res = $this->http()->get($this->apiUrl().'/'.rawurlencode($orderId).'/status');
        } catch (\Throwable $e) {
            Log::warning('Midtrans status error: '.$e->getMessage());

            return null;
        }

        $body = $res->json();

        return is_array($body) && isset($body['transaction_status']) ? $body : null;
    }

    public function cancel(string $orderId): void
    {
        try {
            $this->http()->post($this->apiUrl().'/'.rawurlencode($orderId).'/cancel');
        } catch (\Throwable $e) {
            Log::warning('Midtrans cancel error: '.$e->getMessage());
        }
    }

    public function validSignature(array $p): bool
    {
        $expected = hash('sha512',
            ($p['order_id'] ?? '').($p['status_code'] ?? '').($p['gross_amount'] ?? '').config('services.midtrans.server_key')
        );

        return hash_equals($expected, (string) ($p['signature_key'] ?? ''));
    }
}