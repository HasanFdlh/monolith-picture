<?php

namespace Tests\Feature;

use App\Models\Booth;
use App\Models\Media;
use App\Models\Session;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrintedSessionsTest extends TestCase
{
    use RefreshDatabase;

    private function sessionWithPhoto(string $code = 'PRINT0001'): Session
    {
        $booth = Booth::create(['name' => 'Test Booth', 'slug' => 'test-booth-' . $code, 'is_active' => true]);
        $session = Session::create([
            'booth_id' => $booth->id,
            'session_code' => $code,
            'taken_at' => now(),
        ]);
        Media::create([
            'session_id' => $session->id,
            'type' => 'photo',
            'file_name' => 'a.jpg',
            'path' => "storage/sessions/{$code}/a.jpg",
            'size' => 10,
        ]);

        return $session;
    }

    public function test_tombol_cetak_menandai_sesi_sebagai_dicetak(): void
    {
        config(['photobooth.require_payment' => false]);
        $session = $this->sessionWithPhoto();

        $this->postJson("/api/photo-sessions/{$session->id}/printed")->assertOk();
        $this->postJson("/api/photo-sessions/{$session->id}/printed")->assertOk();

        $session->refresh();
        $this->assertNotNull($session->printed_at);
        $this->assertSame(2, $session->print_count);
    }

    public function test_sesi_tanpa_foto_tidak_bisa_ditandai_dicetak(): void
    {
        config(['photobooth.require_payment' => false]);
        $booth = Booth::create(['name' => 'B', 'slug' => 'b', 'is_active' => true]);
        $session = Session::create(['booth_id' => $booth->id, 'session_code' => 'EMPTY001', 'taken_at' => now()]);

        $this->postJson("/api/photo-sessions/{$session->id}/printed")->assertStatus(422);
    }

    public function test_halaman_hasil_print_hanya_menampilkan_sesi_yang_dicetak(): void
    {
        config(['photobooth.require_payment' => false]);
        $printed = $this->sessionWithPhoto('DICETAK01');
        $this->sessionWithPhoto('BELUM0001');
        $printed->markPrinted();

        $this->actingAs(User::factory()->create())
            ->get(route('printed.index'))
            ->assertOk()
            ->assertSee('DICETAK01')
            ->assertDontSee('BELUM0001');
    }

    public function test_keluarkan_dari_daftar_tidak_menghapus_sesi(): void
    {
        config(['photobooth.require_payment' => false]);
        $session = $this->sessionWithPhoto('KELUAR001');
        $session->markPrinted();

        $this->actingAs(User::factory()->create())
            ->post(route('printed.unmark', $session->id))
            ->assertRedirect();

        $session->refresh();
        $this->assertNull($session->printed_at);
        $this->assertSame(0, $session->print_count);
        $this->assertSame(1, $session->media()->count());
    }

    public function test_hapus_permanen_menghapus_sesi_dan_media(): void
    {
        $session = $this->sessionWithPhoto('HAPUS0001');
        $session->markPrinted();

        $this->actingAs(User::factory()->create())
            ->delete(route('printed.destroy', $session->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('sessions', ['id' => $session->id]);
        $this->assertDatabaseMissing('media', ['session_id' => $session->id]);
    }

    public function test_aksi_massal_hanya_menyentuh_sesi_yang_dicetak(): void
    {
        $printed = $this->sessionWithPhoto('MASSAL001');
        $printed->markPrinted();
        $other = $this->sessionWithPhoto('MASSAL002'); // belum dicetak

        $this->actingAs(User::factory()->create())
            ->post(route('printed.bulk'), ['action' => 'delete', 'ids' => [$printed->id, $other->id]])
            ->assertRedirect();

        $this->assertDatabaseMissing('sessions', ['id' => $printed->id]);
        $this->assertDatabaseHas('sessions', ['id' => $other->id]);
    }
}
