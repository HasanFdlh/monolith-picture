<?php

namespace Tests\Feature;

use App\Mail\SessionPrintMail;
use App\Models\Booth;
use App\Models\Media;
use App\Models\Session;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ApiConfigAndPrintTest extends TestCase
{
    use RefreshDatabase;

    private function sessionWithPhoto(): Session
    {
        $booth = Booth::create(['name' => 'Test Booth', 'slug' => 'test-booth', 'is_active' => true]);
        $session = Session::create([
            'booth_id' => $booth->id,
            'session_code' => 'TESTCODE1',
            'taken_at' => now(),
        ]);
        Media::create([
            'session_id' => $session->id,
            'type' => 'photo',
            'file_name' => 'a.jpg',
            'path' => 'storage/sessions/TESTCODE1/a.jpg',
            'size' => 10,
        ]);

        return $session;
    }

    public function test_config_mengikuti_pengaturan_server_dan_memberi_warnings(): void
    {
        config([
            'photobooth.require_payment' => false,
            'photobooth.shots_per_session' => 8,
            'mail.default' => 'log',
            'photobooth.printer_command' => null,
        ]);

        $this->getJson('/api/config')
            ->assertOk()
            ->assertJsonPath('data.require_payment', false)
            ->assertJsonPath('data.shots_per_session', 8)
            ->assertJsonStructure(['data' => ['warnings']]);

        $warnings = implode(' ', $this->getJson('/api/config')->json('data.warnings'));
        $this->assertStringContainsString('MAIL_MAILER=log', $warnings);
        $this->assertStringContainsString('PHOTOBOOTH_PRINTER_COMMAND', $warnings);
    }

    public function test_print_dengan_mailer_log_tidak_mengaku_terkirim(): void
    {
        config(['mail.default' => 'log', 'photobooth.printer_command' => null]);
        $session = $this->sessionWithPhoto();

        $this->postJson("/api/photo-sessions/{$session->id}/print", ['email' => 'a@b.com'])
            ->assertStatus(502)
            ->assertJsonPath('email_sent', false)
            ->assertJsonPath('print_dispatched', false);
    }

    public function test_print_dengan_mailer_sungguhan_mengirim_email(): void
    {
        config(['mail.default' => 'smtp', 'photobooth.printer_command' => null]);
        Mail::fake();
        $session = $this->sessionWithPhoto();

        $this->postJson("/api/photo-sessions/{$session->id}/print", ['email' => 'a@b.com'])
            ->assertOk()
            ->assertJsonPath('email_sent', true)
            ->assertJsonPath('print_dispatched', false);

        Mail::assertSent(SessionPrintMail::class);
        $this->assertSame('a@b.com', $session->fresh()->email);
    }

    public function test_frame_backgrounds_tetap_ok_walau_tabel_belum_ada(): void
    {
        \Illuminate\Support\Facades\Schema::drop('frame_backgrounds');

        $this->getJson('/api/frame-backgrounds')
            ->assertOk()
            ->assertJsonPath('data', []);
    }
}
