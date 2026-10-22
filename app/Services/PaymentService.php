<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public function __construct(
        private MidtransService $midtrans,
        private SaleService $sales,
    ) {}

    public function mapStatus(array $p): string
    {
        $s = $p['transaction_status'] ?? '';
        $fraud = $p['fraud_status'] ?? 'accept';

        return match (true) {
            $s === 'settlement' => 'paid',
            $s === 'capture' && $fraud === 'accept' => 'paid',
            in_array($s, ['deny', 'cancel', 'failure'], true) => 'failed',
            $s === 'expire' => 'expired',
            in_array($s, ['refund', 'partial_refund', 'chargeback'], true) => 'refunded',
            default => 'pending',
        };
    }

    public function apply(Payment $payment, array $p): void
    {
        DB::transaction(function () use ($payment, $p) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            // Idempotent: pembayaran yang sudah final tidak diproses ulang
            if (in_array($payment->status, ['paid', 'refunded'], true)) {
                return;
            }

            $amount = (int) round((float) ($p['gross_amount'] ?? 0));
            if ($amount !== (int) $payment->amount) {
                Log::warning('Midtrans: nominal tidak cocok', [
                    'order_id' => $payment->order_id,
                    'diterima' => $amount,
                    'seharusnya' => $payment->amount,
                ]);

                return;
            }

            $new = $this->mapStatus($p);

            $payment->update([
                'status' => $new,
                'method' => $p['payment_type'] ?? $payment->method,
                'transaction_id' => $p['transaction_id'] ?? $payment->transaction_id,
                'payload' => $p,
                'paid_at' => $new === 'paid' ? now() : $payment->paid_at,
            ]);

            if ($new === 'paid') {
                $outcome = $this->sales->markPaid($payment->sale()->first(), $p['payment_type'] ?? null);

                if ($outcome === 'refund_needed') {
                    Log::warning('Pembayaran masuk tetapi stok sudah habis, perlu refund', ['order_id' => $payment->order_id]);
                }
            }
        });
    }

    public function syncSale(Sale $sale): void
    {
        $sale->payments()->where('status', 'pending')->get()->each(function (Payment $payment) {
            $status = $this->midtrans->status($payment->order_id);

            if ($status) {
                $this->apply($payment, $status);
            }
        });
    }

    public function cancelOpen(Sale $sale): void
    {
        $sale->payments()->where('status', 'pending')->get()->each(function (Payment $payment) {
            $this->midtrans->cancel($payment->order_id);
            $payment->update(['status' => 'failed']);
        });
    }
}