<?php

namespace Tests\Feature;

use App\Models\Booth;
use App\Models\Media;
use App\Models\Session;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionCompositionTest extends TestCase
{
    use RefreshDatabase;

    private function sessionWithPhotos(int $count = 3): Session
    {
        $booth = Booth::create(['name' => 'Test Booth', 'slug' => 'comp-booth', 'is_active' => true]);
        $session = Session::create([
            'booth_id' => $booth->id,
            'session_code' => 'COMP0001',
            'taken_at' => now(),
        ]);
        for ($i = 1; $i <= $count; $i++) {
            Media::create([
                'session_id' => $session->id,
                'type' => 'photo',
                'file_name' => "p{$i}.jpg",
                'path' => "storage/sessions/COMP0001/p{$i}.jpg",
                'size' => 10,
            ]);
        }

        return $session;
    }

    private function composition(): array
    {
        return [
            'version' => 1,
            'frame_id' => 'main-character',
            'frame_name' => 'Main Character 3x2',
            'print_mode' => 'different',
            'layout' => 'gridPortrait',
            'cols' => 2,
            'rows' => 3,
            'cells' => [0, 1, 2, -1, -1, -1],
            'copies' => 1,
            'copies_direction' => 'row',
            'filter' => 'cool',
            'background' => null,
            'template' => null,
        ];
    }

    public function test_kiosk_menyimpan_susunan_akhir_lewat_update(): void
    {
        config(['photobooth.require_payment' => false]);
        $session = $this->sessionWithPhotos();

        $this->putJson("/api/photo-sessions/{$session->id}", ['composition' => $this->composition()])
            ->assertOk();

        $this->assertSame('Main Character 3x2', $session->fresh()->composition['frame_name']);
    }

    public function test_cetak_menyimpan_susunan_dan_filter_terbaru(): void
    {
        config(['photobooth.require_payment' => false]);
        $session = $this->sessionWithPhotos();

        $this->postJson("/api/photo-sessions/{$session->id}/printed", ['composition' => $this->composition()])
            ->assertOk();

        $session->refresh();
        $this->assertNotNull($session->printed_at);
        $this->assertSame('cool', $session->filter);
        $this->assertSame('cool', $session->composition['filter']);
    }

    public function test_detail_sesi_menampilkan_hasil_akhir_bila_ada_susunan(): void
    {
        $session = $this->sessionWithPhotos();
        $session->update(['composition' => $this->composition()]);

        $this->actingAs(User::factory()->create())
            ->get(route('sessions.show', $session->id))
            ->assertOk()
            ->assertSee('Hasil Akhir')
            ->assertSee('Pilih filter');
    }

    public function test_detail_sesi_tanpa_susunan_tetap_galeri_biasa(): void
    {
        $session = $this->sessionWithPhotos();

        $this->actingAs(User::factory()->create())
            ->get(route('sessions.show', $session->id))
            ->assertOk()
            ->assertDontSee('Hasil Akhir');
    }

    public function test_memilih_filter_dari_dashboard_ikut_memperbarui_susunan(): void
    {
        $session = $this->sessionWithPhotos();
        $session->update(['composition' => $this->composition(), 'filter' => 'cool']);

        $this->actingAs(User::factory()->create())
            ->putJson(route('sessions.update', $session->id), ['filter' => 'sepia'])
            ->assertOk();

        $session->refresh();
        $this->assertSame('sepia', $session->filter);
        $this->assertSame('sepia', $session->composition['filter']);
    }
}
