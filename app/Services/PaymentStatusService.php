<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\DB;

/**
 * Menerapkan payload status dari Midtrans (callback / GET status) ke Payment + Session.
 */
class PaymentStatusService
{
    public function applyPayload(Payment $payment, array $payload): Payment
    {
        $transactionStatus = strtolower((string) ($payload['transaction_status'] ?? $payment->status));
        $fraudStatus = strtolower((string) ($payload['fraud_status'] ?? ''));

        $status = $transactionStatus;

        // Card: capture dengan fraud accept dianggap settlement (uang diterima).
        if ($transactionStatus === 'capture' && $fraudStatus === 'accept') {
            $status = 'settlement';
        }

        // Card: capture dengan fraud challenge belum aman, tahan dulu sebagai pending.
        if ($transactionStatus === 'capture' && $fraudStatus === 'challenge') {
            $status = 'pending';
        }

        return DB::transaction(function () use ($payment, $payload, $status) {
            $isPaid = in_array($status, Payment::PAID_STATUSES, true);

            $payment->update([
                'status' => $status,
                'payment_type' => $payload['payment_type'] ?? $payment->payment_type,
                'transaction_id' => $payload['transaction_id'] ?? $payment->transaction_id,
                'raw_response' => $payload,
                'paid_at' => $isPaid ? ($payment->paid_at ?? now()) : $payment->paid_at,
            ]);

            $session = $payment->session;

            if ($session) {
                $session->update([
                    'payment_status' => $this->sessionStatusFor($status),
                    'paid_at' => $isPaid ? ($session->paid_at ?? now()) : $session->paid_at,
                    // Pastikan jatah take mengikuti paket yang benar-benar dibayar.
                    'allowed_takes' => max((int) $session->allowed_takes, (int) $payment->takes),
                ]);
            }

            return $payment->refresh();
        });
    }

    private function sessionStatusFor(string $status): string
    {
        return match ($status) {
            'settlement', 'capture' => 'paid',
            'expire' => 'expired',
            'deny', 'cancel', 'failure' => 'failed',
            default => 'pending',
        };
    }
}
