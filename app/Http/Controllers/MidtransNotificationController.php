<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\MidtransService;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class MidtransNotificationController extends Controller
{
    public function __invoke(Request $request, MidtransService $midtrans, PaymentService $payments)
    {
        $p = $request->all();

        if (! $midtrans->validSignature($p)) {
            return response()->json(['message' => 'Signature tidak valid.'], 403);
        }

        $payment = Payment::where('order_id', $p['order_id'] ?? '')->first();

        if ($payment) {
            $payments->apply($payment, $p);
        }

        return response()->json(['message' => 'OK']);
    }
}