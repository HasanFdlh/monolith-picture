<?php

namespace App\Http\Controllers;

use App\Models\Booth;
use App\Models\Payment;
use App\Models\PhotoPackage;
use App\Models\Session;
use App\Services\MidtransService;
use App\Services\PaymentStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly MidtransService $midtrans,
        private readonly PaymentStatusService $statuses,
    ) {
    }

    public function index()
    {
        return view('checkout.index', [
            'title' => 'Pilih Paket Foto',
            'subtitle' => 'Pilih jumlah foto, lalu bayar untuk memulai sesi.',
            'packages' => PhotoPackage::active()->orderBy('sort_order')->orderBy('takes')->get(),
            'booths' => Booth::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'midtransConfigured' => $this->midtrans->isConfigured(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'photo_package_id' => ['required', 'integer', 'exists:photo_packages,id'],
            'booth_id' => ['nullable', 'integer', 'exists:booths,id'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $package = PhotoPackage::query()->where('is_active', true)->findOrFail($data['photo_package_id']);

        // Booth bisa ditentukan kiosk; kalau kosong pakai booth aktif pertama.
        $boothId = $data['booth_id']
            ?? Booth::query()->where('is_active', true)->value('id')
            ?? Booth::query()->value('id');

        abort_if(!$boothId, 422, 'Belum ada booth yang tersedia untuk sesi ini.');

        try {
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
                    'order_id' => $this->makeOrderId(),
                    'session_id' => $session->id,
                    'photo_package_id' => $package->id,
                    'takes' => $package->takes,
                    'amount' => $package->price,
                    'status' => 'pending',
                    'expires_at' => now()->addMinutes((int) config('services.midtrans.expiry_minutes', 5)),
                ]);
            });

            $transaction = $this->midtrans->createTransaction($payment, [
                'callbacks' => [
                    'finish' => route('checkout.status', $payment),
                    'unfinish' => route('checkout.status', $payment),
                    'error' => route('checkout.status', $payment),
                ],
            ]);

            $payment->update([
                'snap_token' => $transaction['token'] ?? null,
                'snap_redirect_url' => $transaction['redirect_url'] ?? null,
            ]);

            // Redirect full-page ke halaman pembayaran Midtrans.
            if (!empty($transaction['redirect_url'])) {
                return redirect()->away($transaction['redirect_url']);
            }

            return redirect()->route('checkout.status', $payment);
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function status(Payment $payment)
    {
        $payment->load('session.photoPackage', 'photoPackage');

        return view('checkout.status', [
            'title' => 'Menunggu Pembayaran',
            'subtitle' => 'Selesaikan pembayaran untuk memulai sesi foto.',
            'payment' => $payment,
            'expiryMinutes' => (int) config('services.midtrans.expiry_minutes', 5),
        ]);
    }

    /**
     * Endpoint polling untuk halaman status. Kalau callback belum masuk
     * (mis. saat testing lokal), kita sinkronkan langsung ke Midtrans.
     */
    public function statusJson(Payment $payment)
    {
        if ($payment->status === 'pending' && $this->midtrans->isConfigured()) {
            $status = $this->midtrans->getTransactionStatus($payment->order_id);

            if ($status && !empty($status['transaction_status'])) {
                $this->statuses->applyPayload($payment, $status);
            }
        }

        $payment->refresh();

        return response()->json([
            'status' => $payment->status,
            'paid' => $payment->isPaid(),
            'expired' => $payment->isExpired(),
            'redirect' => $payment->session
                ? route('capture.show', $payment->session)
                : null,
        ]);
    }

    private function makeOrderId(): string
    {
        return 'PHB-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(6));
    }
}
