<?php

namespace Tests\Feature;

use App\Mail\SessionPrintMail;
use App\Models\Booth;
use App\Models\Media;
use App\Models\Session;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrintEmailMockTest extends TestCase
{
    use RefreshDatabase;

    private function sessionWithPhoto(): Session
    {
        $booth = Booth::create(['name' => 'Test Booth', 'slug' => 'mock-booth', 'is_active' => true]);
        $session = Session::create([
            'booth_id' => $booth->id,
            'session_code' => 'MOCK0001',
            'taken_at' => now(),
            'allowed_takes' => 1,
        ]);
        Media::create([
            'session_id' => $session->id,
            'type' => 'photo',
            'file_name' => 'a.jpg',
            'path' => 'storage/sessions/MOCK0001/a.jpg',
            'size' => 10,
        ]);
        $session->update(['total_files' => 1]);

        return $session;
    }

    public function test_upload_mock_strip_disimpan_sebagai_media_strip_tanpa_memakai_jatah_foto(): void
    {
        config(['photobooth.require_payment' => false]);
        Storage::fake('public');
        $session = $this->sessionWithPhoto();

        $this->post("/api/photo-sessions/{$session->id}/strip", [
            'strip' => UploadedFile::fake()->image('mock.png', 300, 600),
        ], ['Accept' => 'application/json'])->assertCreated();

        $session->refresh();
        $this->assertSame(1, $session->media()->where('type', 'strip')->count());
        $this->assertSame(1, $session->total_files); // hanya foto yang dihitung

        // Unggah ulang menggantikan mock lama.
        $this->post("/api/photo-sessions/{$session->id}/strip", [
            'strip' => UploadedFile::fake()->image('mock2.png', 300, 600),
        ], ['Accept' => 'application/json'])->assertCreated();

        $this->assertSame(1, $session->media()->where('type', 'strip')->count());
    }

    public function test_hanya_email_tidak_menjalankan_printer_dan_tidak_mencatat_cetak(): void
    {
        config(['mail.default' => 'smtp', 'photobooth.printer_command' => null]);
        Mail::fake();
        $session = $this->sessionWithPhoto();

        $this->postJson("/api/photo-sessions/{$session->id}/print", [
            'email' => 'a@b.com',
            'print' => false,
            'mark_printed' => false,
        ])->assertOk()
            ->assertJsonPath('email_sent', true)
            ->assertJsonPath('print_dispatched', false);

        Mail::assertSent(SessionPrintMail::class);
        $this->assertNull($session->fresh()->printed_at);
    }

    public function test_hanya_print_tanpa_email_melapor_jujur_bila_printer_belum_diatur(): void
    {
        config(['photobooth.printer_command' => null]);
        $session = $this->sessionWithPhoto();

        $this->postJson("/api/photo-sessions/{$session->id}/print", [])
            ->assertStatus(502)
            ->assertJsonPath('email_sent', false)
            ->assertJsonPath('print_dispatched', false);
    }

    public function test_tanpa_email_dan_tanpa_print_ditolak(): void
    {
        $session = $this->sessionWithPhoto();

        $this->postJson("/api/photo-sessions/{$session->id}/print", ['print' => false])
            ->assertStatus(422);
    }
}
