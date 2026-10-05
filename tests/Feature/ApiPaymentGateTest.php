<?php

namespace Tests\Feature;

use App\Models\Booth;
use App\Models\Payment;
use App\Models\PhotoPackage;
use App\Models\Session;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApiPaymentGateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'photobooth.require_payment' => true,
            'services.midtrans.server_key' => 'test-server-key',
            'services.midtrans.client_key' => 'test-client-key',
            'services.midtrans.base_url' => 'https://app.sandbox.midtrans.com',
            'services.midtrans.core_base_url' => 'https://api.sandbox.midtrans.com',
        ]);
    }

    private function seedBoothAndPackage(int $takes = 2): array
    {
        $booth = Booth::create(['name' => 'Test Booth', 'slug' => 'test-booth', 'is_active' => true]);
        $package = PhotoPackage::create([
            'name' => 'Classic',
            'takes' => $takes,
            'price' => 35000,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        return [$booth, $package];
    }

    private function fakeMidtrans(string $statusAfterPolling = 'pending'): void
    {
        Http::fake([
            'app.sandbox.midtrans.com/snap/v1/transactions' => Http::response([
                'token' => 'snap-token-123',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/snap-token-123',
            ], 201),
            'api.sandbox.midtrans.com/v2/*/status' => Http::response([
                'transaction_status' => $statusAfterPolling,
                'fraud_status' => 'accept',
                'payment_type' => 'qris',
                'transaction_id' => 'tx-1',
            ], 200),
            'api.sandbox.midtrans.com/v2/*/cancel' => Http::response([
                'transaction_status' => 'cancel',
            ], 200),
        ]);
    }

    public function test_packages_endpoint_lists_active_packages(): void
    {
        $this->seedBoothAndPackage();

        $this->getJson('/api/packages')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Classic')
            ->assertJsonPath('data.0.takes', 2);
    }

    public function test_create_payment_returns_qr_url_and_pending_session(): void
    {
        [$booth, $package] = $this->seedBoothAndPackage();
        $this->fakeMidtrans();

        $this->postJson('/api/payments', [
            'photo_package_id' => $package->id,
            'booth_id' => $booth->id,
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.paid', false)
            ->assertJsonPath('data.payment_url', 'https://app.sandbox.midtrans.com/snap/v2/vtweb/snap-token-123')
            ->assertJsonPath('data.session.allowed_takes', 2)
            ->assertJsonPath('data.session.payment_status', 'pending');
    }

    public function test_payment_status_turns_paid_after_midtrans_settlement(): void
    {
        [$booth, $package] = $this->seedBoothAndPackage();
        $this->fakeMidtrans('settlement');

        $orderId = $this->postJson('/api/payments', [
            'photo_package_id' => $package->id,
            'booth_id' => $booth->id,
        ])->json('data.order_id');

        $this->getJson("/api/payments/{$orderId}")
            ->assertOk()
            ->assertJsonPath('data.paid', true)
            ->assertJsonPath('data.session.payment_status', 'paid');
    }

    public function test_cancel_marks_pending_payment_as_cancelled(): void
    {
        [$booth, $package] = $this->seedBoothAndPackage();
        $this->fakeMidtrans('pending');

        $orderId = $this->postJson('/api/payments', [
            'photo_package_id' => $package->id,
            'booth_id' => $booth->id,
        ])->json('data.order_id');

        $this->postJson("/api/payments/{$orderId}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancel')
            ->assertJsonPath('data.expired', true);
    }

    public function test_upload_is_blocked_until_paid_and_limited_by_takes(): void
    {
        Storage::fake('public');
        [$booth, $package] = $this->seedBoothAndPackage(2);

        $session = Session::create([
            'booth_id' => $booth->id,
            'photo_package_id' => $package->id,
            'allowed_takes' => 2,
            'payment_status' => 'pending',
            'session_code' => 'GATE001',
            'total_files' => 0,
            'total_size' => 0,
            'taken_at' => now(),
        ]);

        $photo = fn () => UploadedFile::fake()->image('a.jpg');

        // Belum dibayar -> ditolak.
        $this->post("/api/photo-sessions/{$session->id}/photos", ['photos' => [$photo()]], ['Accept' => 'application/json'])
            ->assertStatus(403);

        $session->update(['payment_status' => 'paid', 'paid_at' => now()]);

        // Lebih dari jatah take -> ditolak.
        $this->post("/api/photo-sessions/{$session->id}/photos", ['photos' => [$photo(), $photo(), $photo()]], ['Accept' => 'application/json'])
            ->assertStatus(422);

        // Sesuai jatah -> lolos.
        $this->post("/api/photo-sessions/{$session->id}/photos", ['photos' => [$photo(), $photo()]], ['Accept' => 'application/json'])
            ->assertCreated();

        $this->assertSame(2, $session->fresh()->total_files);
    }

    public function test_legacy_session_creation_is_closed_when_payment_required(): void
    {
        [$booth] = $this->seedBoothAndPackage();

        $this->postJson('/api/photo-sessions', ['booth_id' => $booth->id])->assertStatus(403);
        $this->postJson('/api/sessions', ['booth_id' => $booth->id])->assertStatus(403);
    }
}
