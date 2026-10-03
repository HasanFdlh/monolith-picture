<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\MidtransService;
use App\Services\PaymentStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MidtransCallbackController extends Controller
{
    public function __construct(
        private readonly MidtransService $midtrans,
        private readonly PaymentStatusService $statuses,
    ) {
    }

    /**
     * HTTP Notification dari Midtrans.
     * Set URL ini di Dashboard Midtrans: SETTINGS > CONFIGURATION > Payment Notification URL
     * Contoh: https://domain-anda.com/midtrans/notification
     */
    public function notification(Request $request)
    {
        $payload = $request->all();

        if (!$this->midtrans->verifySignature($payload)) {
            Log::warning('Midtrans callback: signature tidak valid', [
                'order_id' => $payload['order_id'] ?? null,
            ]);

            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $payment = Payment::query()->where('order_id', $payload['order_id'] ?? null)->first();

        if (!$payment) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $this->statuses->applyPayload($payment, $payload);

        return response()->json(['message' => 'OK']);
    }
}
