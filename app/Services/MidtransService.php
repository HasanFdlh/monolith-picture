<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class MidtransService
{
    public function serverKey(): ?string
    {
        return config('services.midtrans.server_key');
    }

    public function isConfigured(): bool
    {
        return !empty($this->serverKey());
    }

    public function baseUrl(): string
    {
        return rtrim((string) config('services.midtrans.base_url'), '/');
    }

    public function snapUrl(): string
    {
        return $this->baseUrl() . '/snap/v1/transactions';
    }

    public function statusUrl(string $orderId): string
    {
        return $this->baseUrl() . '/v2/' . $orderId . '/status';
    }

    /**
     * Membuat transaksi Snap dan mengembalikan token + redirect_url.
     *
     * @return array{token:string, redirect_url:string}
     */
    public function createTransaction(Payment $payment, array $overrides = []): array
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('MIDTRANS_SERVER_KEY belum dikonfigurasi.');
        }

        $amount = (int) round((float) $payment->amount);

        $payload = array_merge([
            'transaction_details' => [
                'order_id' => $payment->order_id,
                'gross_amount' => $amount,
            ],
            'item_details' => [
                [
                    'id' => (string) ($payment->photo_package_id ?: 'package'),
                    'price' => $amount,
                    'quantity' => 1,
                    'name' => Str::limit($payment->photoPackage->name ?? 'Paket Foto', 50, ''),
                ],
            ],
            // Halaman pembayaran hanya berlaku X menit (default 5), sesuai flow.
            'expiry' => [
                'unit' => 'minutes',
                'duration' => (int) config('services.midtrans.expiry_minutes', 5),
            ],
        ], $overrides);

        $response = Http::withBasicAuth($this->serverKey(), '')
            ->acceptJson()
            ->post($this->snapUrl(), $payload);

        if ($response->failed()) {
            Log::error('Midtrans Snap gagal membuat transaksi', [
                'order_id' => $payment->order_id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException(
                'Gagal membuat transaksi Midtrans: '
                . ($response->json('error_messages.0') ?? $response->body())
            );
        }

        return $response->json();
    }

    /**
     * Mengambil status transaksi langsung dari Midtrans (untuk verifikasi/sinkronisasi).
     */
    public function getTransactionStatus(string $orderId): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $response = Http::withBasicAuth($this->serverKey(), '')
            ->acceptJson()
            ->get($this->statusUrl($orderId));

        return $response->successful() ? $response->json() : null;
    }

    /**
     * Verifikasi signature callback.
     * SHA512(order_id + status_code + gross_amount + ServerKey)
     */
    public function verifySignature(array $payload): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        $signature = (string) ($payload['signature_key'] ?? '');
        if ($signature === '') {
            return false;
        }

        $expected = hash('sha512',
            ($payload['order_id'] ?? '')
            . ($payload['status_code'] ?? '')
            . ($payload['gross_amount'] ?? '')
            . $this->serverKey()
        );

        return hash_equals($expected, $signature);
    }
}
