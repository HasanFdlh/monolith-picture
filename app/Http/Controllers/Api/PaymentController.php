<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booth;
use App\Models\Payment;
use App\Models\PhotoPackage;
use App\Models\Session;
use App\Services\MidtransService;
use App\Services\PaymentStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * API pembayaran untuk aplikasi Flutter (kiosk).
 *
 * Alur:
 *  1. GET  /api/packages                  -> daftar paket aktif
 *  2. POST /api/payments                  -> buat sesi (pending) + transaksi Midtrans,
 *                                            balikin `payment_url` untuk dijadikan QR
 *  3. GET  /api/payments/{order_id}       -> polling status (sinkron ke Midtrans bila pending)
 *  4. POST /api/payments/{order_id}/cancel -> kiosk batal / pelanggan keluar
 *
 * Setelah `paid = true`, Flutter boleh lanjut dan upload foto ke sesi tersebut
 * (lihat gerbang di Api\PhotoboothController).
 */
class PaymentController extends Controller
{
    public function __construct(
        private readonly MidtransService $midtrans,
        private readonly PaymentStatusService $statuses,
    ) {
    }

    public function packages(): JsonResponse
    {
        $packages = PhotoPackage::active()
            ->orderBy('sort_order')
            ->orderBy('takes')
            ->get(['id', 'name', 'takes', 'price', 'description']);

        return response()->json(['data' => $packages]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'photo_package_id' => ['required', 'integer', 'exists:photo_packages,id'],
            'booth_id' => ['nullable', 'integer', 'exists:booths,id'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        if (!$this->midtrans->isConfigured()) {
            return response()->json([
                'message' => 'Midtrans belum dikonfigurasi di dashboard (MIDTRANS_SERVER_KEY).',
            ], 503);
        }

        $package = PhotoPackage::query()->where('is_active', true)->find($data['photo_package_id']);
        if (!$package) {
            return response()->json(['message' => 'Paket tidak tersedia.'], 422);
        }

        $boothId = $data['booth_id']
            ?? Booth::query()->where('is_active', true)->value('id')
            ?? Booth::query()->value('id');

        if (!$boothId) {
            return response()->json(['message' => 'Belum ada booth yang tersedia untuk sesi ini.'], 422);
        }

        $payment = DB::transaction(function () use ($package, $boothId, $data) {
            $session = Session::create([
                'booth_id' => $boothId,
                'photo_package_id' => $package->id,
                'allowed_takes' => $package->takes,
                'payment_status' => 'pending',
                'session_code' => now()->format('YmdHis') . Str::upper(Str::random(4)),
                'customer_name' => $data['customer_name'] ?? null,
                'email' => $data['email'] ?? null,
                'total_files' => 0,
                'total_size' => 0,
                'taken_at' => now(),
            ]);

            return Payment::create([
                'order_id' => 'PHB-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(6)),
                'session_id' => $session->id,
                'photo_package_id' => $package->id,
                'takes' => $package->takes,
                'amount' => $package->price,
                'status' => 'pending',
                'expires_at' => now()->addMinutes((int) config('services.midtrans.expiry_minutes', 5)),
            ]);
        });

        try {
            // Tanpa override `callbacks`: yang membayar adalah HP pelanggan, bukan kiosk,
            // jadi cukup halaman sukses bawaan Midtrans. Kiosk mendeteksi lewat polling.
            $transaction = $this->midtrans->createTransaction($payment);
        } catch (Throwable $e) {
            report($e);

            $this->statuses->applyPayload($payment, ['transaction_status' => 'failure']);

            return response()->json([
                'message' => 'Gagal membuat transaksi pembayaran.',
                'error' => $e->getMessage(),
            ], 502);
        }

        $payment->update([
            'snap_token' => $transaction['token'] ?? null,
            'snap_redirect_url' => $transaction['redirect_url'] ?? null,
        ]);

        return response()->json(['data' => $this->payload($payment->refresh())], 201);
    }

    public function show(Payment $payment): JsonResponse
    {
        $this->sync($payment);

        return response()->json(['data' => $this->payload($payment->refresh())]);
    }

    public function cancel(Payment $payment): JsonResponse
    {
        // Sinkron dulu: kalau ternyata sudah dibayar, jangan dibatalkan.
        $this->sync($payment);
        $payment->refresh();

        if ($payment->status === 'pending') {
            $result = $this->midtrans->cancelTransaction($payment->order_id);

            $this->statuses->applyPayload(
                $payment,
                ['transaction_status' => 'cancel'] + ($result ?? [])
            );
        }

        return response()->json(['data' => $this->payload($payment->refresh())]);
    }

    /**
     * Kalau callback Midtrans belum masuk (mis. server lokal tanpa URL publik),
     * tanya langsung ke Midtrans supaya kiosk tetap bisa lanjut.
     */
    private function sync(Payment $payment): void
    {
        if ($payment->status !== 'pending' || !$this->midtrans->isConfigured()) {
            return;
        }

        $status = $this->midtrans->getTransactionStatus($payment->order_id);

        if ($status && !empty($status['transaction_status'])) {
            $this->statuses->applyPayload($payment, $status);
        }
    }

    private function payload(Payment $payment): array
    {
        $session = $payment->session;

        return [
            'order_id' => $payment->order_id,
            'status' => $payment->status,
            'paid' => $payment->isPaid(),
            'expired' => $payment->isExpired(),
            'amount' => (int) round((float) $payment->amount),
            'takes' => $payment->takes,
            'expires_at' => optional($payment->expires_at)->toIso8601String(),
            // Dipakai kiosk untuk mengoreksi selisih jam perangkat vs server pada countdown.
            'server_time' => now()->toIso8601String(),
            // Isi QR di layar kiosk. Pelanggan scan -> halaman bayar Midtrans (QRIS, VA, dll).
            'payment_url' => $payment->snap_redirect_url,
            'session' => $session ? [
                'id' => $session->id,
                'session_code' => $session->session_code,
                'allowed_takes' => (int) $session->allowed_takes,
                'payment_status' => $session->payment_status,
            ] : null,
        ];
    }
}
