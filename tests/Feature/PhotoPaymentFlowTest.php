<?php

namespace Tests\Feature;

use App\Models\Booth;
use App\Models\Payment;
use App\Models\PhotoFrame;
use App\Models\PhotoPackage;
use App\Models\Session;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhotoPaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.midtrans.server_key' => 'test-server-key',
            'services.midtrans.client_key' => 'test-client-key',
            'services.midtrans.base_url' => 'https://app.sandbox.midtrans.com',
        ]);
    }

    private function makePackage(array $overrides = []): PhotoPackage
    {
        return PhotoPackage::create(array_merge([
            'name' => 'Classic (2 foto)',
            'takes' => 2,
            'price' => 35000,
            'is_active' => true,
            'sort_order' => 1,
        ], $overrides));
    }

    private function makeBooth(): Booth
    {
        return Booth::create([
            'name' => 'Test Booth',
            'slug' => 'test-booth',
            'is_active' => true,
        ]);
    }

    private function makeFrame(int $slots = 2): PhotoFrame
    {
        $layout = [];
        for ($i = 0; $i < $slots; $i++) {
            $layout[] = ['x' => $i, 'y' => 0, 'w' => 1, 'h' => 1, 'label' => 'Foto ' . ($i + 1)];
        }

        return PhotoFrame::create([
            'name' => "Frame {$slots}",
            'is_active' => true,
            'layout_json' => ['slots' => $layout],
        ]);
    }

    private function makeSession(PhotoPackage $package, string $code, string $status = 'pending'): Session
    {
        return Session::create([
            'booth_id' => Booth::first()->id,
            'photo_package_id' => $package->id,
            'allowed_takes' => $package->takes,
            'payment_status' => $status,
            'session_code' => $code,
            'total_files' => 0,
            'total_size' => 0,
            'taken_at' => now(),
        ]);
    }

    public function test_checkout_page_lists_active_packages(): void
    {
        $this->makePackage();
        $this->makeBooth();

        $this->get(route('checkout.index'))
            ->assertOk()
            ->assertSee('Classic (2 foto)');
    }

    public function test_checkout_creates_pending_session_and_redirects_to_midtrans(): void
    {
        $this->makeBooth();
        $package = $this->makePackage();

        Http::fake([
            'app.sandbox.midtrans.com/snap/v1/transactions' => Http::response([
                'token' => 'snap-token-123',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/snap-token-123',
            ], 201),
        ]);

        $this->post(route('checkout.store'), ['photo_package_id' => $package->id])
            ->assertRedirect('https://app.sandbox.midtrans.com/snap/v2/vtweb/snap-token-123');

        $session = Session::firstOrFail();
        $this->assertSame('pending', $session->payment_status);
        $this->assertSame(2, $session->allowed_takes);
        $this->assertFalse($session->isPaid());

        $this->assertDatabaseHas('payments', [
            'session_id' => $session->id,
            'status' => 'pending',
            'takes' => 2,
        ]);
    }

    public function test_capture_is_blocked_until_payment_is_settled(): void
    {
        $this->makeBooth();
        $package = $this->makePackage();
        $session = $this->makeSession($package, 'PENDING001', 'pending');

        $this->get(route('capture.show', $session))
            ->assertRedirect(route('checkout.index'));
    }

    public function test_midtrans_notification_marks_session_paid_and_unlocks_capture(): void
    {
        $this->makeBooth();
        $package = $this->makePackage();
        $session = $this->makeSession($package, 'PAID001', 'pending');

        $payment = Payment::create([
            'order_id' => 'PHB-ORDER-1',
            'session_id' => $session->id,
            'photo_package_id' => $package->id,
            'takes' => $package->takes,
            'amount' => $package->price,
            'status' => 'pending',
        ]);

        $signature = hash('sha512', 'PHB-ORDER-1' . '200' . '35000.00' . 'test-server-key');

        $this->postJson(route('midtrans.notification'), [
            'order_id' => 'PHB-ORDER-1',
            'status_code' => '200',
            'gross_amount' => '35000.00',
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'payment_type' => 'bank_transfer',
            'transaction_id' => 'tx-1',
            'signature_key' => $signature,
        ])->assertOk()->assertJson(['message' => 'OK']);

        $this->assertSame('settlement', $payment->fresh()->status);
        $this->assertTrue($session->fresh()->isPaid());

        $this->get(route('capture.show', $session))->assertOk();
    }

    public function test_notification_with_invalid_signature_is_rejected(): void
    {
        $this->makeBooth();
        $package = $this->makePackage();
        $session = $this->makeSession($package, 'FRAUD001', 'pending');

        Payment::create([
            'order_id' => 'PHB-ORDER-2',
            'session_id' => $session->id,
            'photo_package_id' => $package->id,
            'takes' => $package->takes,
            'amount' => $package->price,
            'status' => 'pending',
        ]);

        $this->postJson(route('midtrans.notification'), [
            'order_id' => 'PHB-ORDER-2',
            'status_code' => '200',
            'gross_amount' => '35000.00',
            'transaction_status' => 'settlement',
            'signature_key' => 'invalid-signature',
        ])->assertStatus(403);

        $this->assertSame('pending', $session->fresh()->payment_status);
    }

    public function test_capture_enforces_number_of_paid_takes(): void
    {
        Storage::fake('public');

        $this->makeBooth();
        $package = $this->makePackage(['takes' => 2]);
        $frame = $this->makeFrame(2);
        $session = $this->makeSession($package, 'CAPTURE01', 'paid');

        $this->post(route('capture.store', $session), [
            'photo_frame_id' => $frame->id,
            'photos' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('remaining_takes', 0);

        $this->assertSame(2, $session->fresh()->total_files);

        // Jatah sudah habis -> upload berikutnya ditolak.
        $this->post(route('capture.store', $session), [
            'photo_frame_id' => $frame->id,
            'photos' => [UploadedFile::fake()->image('c.jpg')],
        ], ['Accept' => 'application/json'])
            ->assertStatus(422);
    }
}
