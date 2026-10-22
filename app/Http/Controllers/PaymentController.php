<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Services\MidtransService;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

class PaymentController extends Controller
{
    public function create(Sale $sale, MidtransService $midtrans, PaymentService $payments)
    {
        Gate::authorize('pay', $sale);

        // Pastikan status terbaru dulu: mungkin sudah dibayar di tab lain
        $payments->syncSale($sale);
        $sale->refresh();

        if ($sale->status !== 'pending') {
            return response()->json(['message' => 'Pesanan ini sudah tidak menunggu pembayaran.'], 422);
        }

        $minutes = (int) ceil(now()->diffInSeconds($sale->expiresAt(), false) / 60);
        if ($minutes < 1) {
            return response()->json(['message' => 'Batas waktu pembayaran sudah habis.'], 422);
        }

        // Pakai ulang transaksi yang masih terbuka supaya tidak ada tagihan ganda
        $payment = $sale->payments()
            ->where('status', 'pending')
            ->whereNotNull('snap_token')
            ->latest('id')
            ->first();

        if (! $payment) {
            $orderId = $sale->number.'-'.($sale->payments()->count() + 1);

            try {
                $token = $midtrans->createSnapToken($sale, $orderId, $minutes);
            } catch (RuntimeException $e) {
                return response()->json(['message' => $e->getMessage()], 502);
            }

            $payment = $sale->payments()->create([
                'provider' => 'midtrans',
                'order_id' => $orderId,
                'status' => 'pending',
                'amount' => $sale->total,
                'snap_token' => $token,
            ]);
        }

        return response()->json(['token' => $payment->snap_token]);
    }

    public function sync(Sale $sale, PaymentService $payments)
    {
        Gate::authorize('view', $sale);

        $payments->syncSale($sale);

        return response()->json(['status' => $sale->fresh()->status]);
    }
}