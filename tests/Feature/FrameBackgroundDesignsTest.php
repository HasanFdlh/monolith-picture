<?php

namespace Tests\Feature;

use App\Models\FrameBackground;
use App\Models\User;
use App\Services\ImageResizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FrameBackgroundDesignsTest extends TestCase
{
    use RefreshDatabase;

    // PNG 1x1 valid, supaya tidak butuh ekstensi GD.
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    private function png(string $name = 'bg.png'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, base64_decode(self::PNG));
    }

    private function design(string $key, string $name, int $order, bool $active = true): FrameBackground
    {
        return FrameBackground::create([
            'frame_key' => $key,
            'name' => $name,
            'image_path' => "frame-backgrounds/{$name}.png",
            'width' => 600,
            'height' => 800,
            'is_active' => $active,
            'sort_order' => $order,
        ]);
    }

    public function test_api_mengembalikan_banyak_desain_per_frame_berurutan_dan_tanpa_yang_nonaktif(): void
    {
        $this->design('main-character', 'Pantai', 2);
        $this->design('main-character', 'Bunga', 1);
        $this->design('main-character', 'Mati', 3, active: false);
        $this->design('spotlight', 'Neon', 1);

        $rows = $this->getJson('/api/frame-backgrounds')->assertOk()->json('data');

        $this->assertSame(
            ['main-character:Bunga', 'main-character:Pantai', 'spotlight:Neon'],
            array_map(fn ($r) => $r['frame_key'] . ':' . $r['name'], $rows)
        );
        $this->assertArrayHasKey('id', $rows[0]);
        $this->assertArrayHasKey('area', $rows[0]);
    }

    public function test_dashboard_upload_banyak_background_sekaligus_untuk_semua_frame(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());

        $this->post(route('frame-backgrounds.store'), [
            'files' => [$this->png('pantai_biru.png'), $this->png('Bunga-Merah.png')],
        ])->assertRedirect(route('frame-backgrounds.index'));

        $rows = FrameBackground::ordered()->get();
        $this->assertCount(2, $rows);
        // Semua background berlaku untuk semua frame.
        $this->assertSame([FrameBackground::UNIVERSAL_KEY, FrameBackground::UNIVERSAL_KEY], $rows->pluck('frame_key')->all());
        // Nama awal diambil dari nama file; urutan mengikuti urutan upload.
        $this->assertSame(['pantai biru', 'Bunga Merah'], $rows->pluck('name')->all());
        $this->assertGreaterThan($rows[0]->sort_order, $rows[1]->sort_order);
    }

    public function test_upload_tanpa_file_ditolak(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('frame-backgrounds.store'), [])->assertSessionHasErrors('files');
        $this->assertSame(0, FrameBackground::count());
    }

    public function test_toggle_rename_dan_hapus_satu_desain_tidak_mengganggu_desain_lain(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());
        $a = $this->design('straight-3', 'A', 1);
        $b = $this->design('straight-3', 'B', 2);

        $this->patch(route('frame-backgrounds.toggle', $a))->assertRedirect();
        $this->assertFalse($a->fresh()->is_active);

        $this->patch(route('frame-backgrounds.rename', $b), ['name' => 'Baru'])->assertRedirect();
        $this->assertSame('Baru', $b->fresh()->name);

        $this->delete(route('frame-backgrounds.destroy', $a))->assertRedirect();
        $this->assertNull(FrameBackground::find($a->id));
        $this->assertNotNull(FrameBackground::find($b->id));
    }

    public function test_area_foto_disimpan_per_desain(): void
    {
        $this->actingAs(User::factory()->create());
        $a = $this->design('row-3', 'A', 1);
        $b = $this->design('row-3', 'B', 2);

        $this->post(route('frame-backgrounds.area', $b), [
            'area_x' => 10, 'area_y' => 20, 'area_w' => 50, 'area_h' => 40,
        ])->assertRedirect();

        $this->assertEquals(10.0, $b->fresh()->area['x']);
        $this->assertEquals(FrameBackground::DEFAULT_AREA['x'], $a->fresh()->area['x']);
    }

    public function test_gambar_desain_dilayani_lewat_api_tanpa_symlink_storage(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('frame-backgrounds/pantai.png', base64_decode(self::PNG));
        $design = $this->design('main-character', 'Pantai', 1);
        $design->update(['image_path' => 'frame-backgrounds/pantai.png']);

        // URL yang dikirim ke aplikasi menunjuk ke route API, bukan /storage/...
        $url = $this->getJson('/api/frame-backgrounds')->assertOk()->json('data.0.image_url');
        $this->assertStringContainsString("/api/frame-backgrounds/{$design->id}/image", $url);

        $this->get("/api/frame-backgrounds/{$design->id}/image")
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }

    public function test_gambar_desain_404_bila_file_hilang(): void
    {
        Storage::fake('public');
        $design = $this->design('main-character', 'Hilang', 1);

        $this->get("/api/frame-backgrounds/{$design->id}/image")->assertNotFound();
    }

    public function test_desain_semua_frame_digandakan_ke_setiap_frame_dan_tampil_setelah_desain_khusus(): void
    {
        $this->design('main-character', 'Khusus', 1);
        $this->design(FrameBackground::UNIVERSAL_KEY, 'Sekolah', 1);

        $rows = $this->getJson('/api/frame-backgrounds')->assertOk()->json('data');

        // 1 desain khusus + 1 desain universal x 8 frame bawaan.
        $this->assertCount(1 + count(FrameBackground::CATALOG), $rows);

        $universal = array_values(array_filter($rows, fn ($r) => $r['universal']));
        $this->assertEqualsCanonicalizing(
            array_column(FrameBackground::CATALOG, 'key'),
            array_column($universal, 'frame_key')
        );
        // Semua salinan menunjuk desain (id) yang sama: file diupload satu kali.
        $this->assertCount(1, array_unique(array_column($universal, 'id')));

        // Untuk main-character: desain khusus muncul sebelum yang universal.
        $mc = array_values(array_filter($rows, fn ($r) => $r['frame_key'] === 'main-character'));
        $this->assertSame(['Khusus', 'Sekolah'], array_column($mc, 'name'));
    }

    public function test_area_foto_background_umum_bisa_diatur(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());
        $this->post(route('frame-backgrounds.store'), ['files' => [$this->png()]]);

        $bg = FrameBackground::firstOrFail();
        $this->post(route('frame-backgrounds.area', $bg), [
            'area_x' => 12, 'area_y' => 11, 'area_w' => 76, 'area_h' => 79,
        ])->assertRedirect();

        $this->assertEquals(76.0, $bg->fresh()->area['w']);
    }

    private function realPng(int $w, int $h): string
    {
        $im = imagecreatetruecolor($w, $h);
        imagefill($im, 0, 0, imagecolorallocate($im, 200, 100, 50));
        ob_start();
        imagepng($im);

        return (string) ob_get_clean();
    }

    private function requireGd(): void
    {
        if (!ImageResizer::available()) {
            $this->markTestSkipped('Ekstensi GD tidak aktif.');
        }
    }

    public function test_upload_otomatis_dijadikan_ukuran_standar_465x659_apa_pun_bentuk_aslinya(): void
    {
        $this->requireGd();
        Storage::fake('public');
        $this->actingAs(User::factory()->create());

        $this->post(route('frame-backgrounds.store'), [
            'files' => [
                UploadedFile::fake()->createWithContent('portrait.png', $this->realPng(465, 659)),
                UploadedFile::fake()->createWithContent('landscape.png', $this->realPng(465, 262)),
                UploadedFile::fake()->createWithContent('besar.png', $this->realPng(1200, 1800)),
            ],
        ])->assertRedirect(route('frame-backgrounds.index'));

        $rows = FrameBackground::ordered()->get();
        $this->assertCount(3, $rows);
        foreach ($rows as $row) {
            $this->assertSame([465, 659], [$row->width, $row->height]);
            [$w, $h] = getimagesizefromstring(Storage::disk('public')->get($row->image_path));
            $this->assertSame([465, 659], [$w, $h]);
        }
    }

    public function test_ukuran_standar_bisa_diubah_lewat_config(): void
    {
        $this->requireGd();
        Storage::fake('public');
        config(['photobooth.background_width' => 600, 'photobooth.background_height' => 900]);
        $this->actingAs(User::factory()->create());

        $this->post(route('frame-backgrounds.store'), [
            'files' => [UploadedFile::fake()->createWithContent('a.png', $this->realPng(465, 659))],
        ]);

        $this->assertSame([600, 900], [FrameBackground::first()->width, FrameBackground::first()->height]);
    }

    public function test_ganti_gambar_juga_dijadikan_ukuran_standar(): void
    {
        $this->requireGd();
        Storage::fake('public');
        $this->actingAs(User::factory()->create());
        $bg = $this->design(FrameBackground::UNIVERSAL_KEY, 'A', 1);

        $this->post(route('frame-backgrounds.replace', $bg), [
            'file' => UploadedFile::fake()->createWithContent('baru.png', $this->realPng(800, 400)),
        ])->assertRedirect(route('frame-backgrounds.index'));

        $bg->refresh();
        $this->assertSame([465, 659], [$bg->width, $bg->height]);
        [$w, $h] = getimagesizefromstring(Storage::disk('public')->get($bg->image_path));
        $this->assertSame([465, 659], [$w, $h]);
    }

    public function test_samakan_semua_hanya_mengubah_yang_beda_dan_mereset_area_bila_bentuk_berubah(): void
    {
        $this->requireGd();
        Storage::fake('public');
        $this->actingAs(User::factory()->create());

        // A: sudah standar (tidak boleh disentuh). B: landscape beda ukuran (area lama harus direset).
        $a = $this->design(FrameBackground::UNIVERSAL_KEY, 'A', 1);
        Storage::disk('public')->put($a->image_path, $this->realPng(465, 659));
        $a->update(['width' => 465, 'height' => 659, 'area_x' => 12, 'area_y' => 11, 'area_w' => 76, 'area_h' => 79]);

        $b = $this->design(FrameBackground::UNIVERSAL_KEY, 'B', 2);
        Storage::disk('public')->put($b->image_path, $this->realPng(465, 262));
        $b->update(['width' => 465, 'height' => 262, 'area_x' => 4, 'area_y' => 3, 'area_w' => 38, 'area_h' => 90]);

        $this->post(route('frame-backgrounds.normalize'))
            ->assertRedirect(route('frame-backgrounds.index'))
            ->assertSessionHas('success');

        $this->assertSame([465, 659], [$b->fresh()->width, $b->fresh()->height]);
        [$w, $h] = getimagesizefromstring(Storage::disk('public')->get($b->image_path));
        $this->assertSame([465, 659], [$w, $h]);
        // Bentuk berubah -> area kembali ke bawaan.
        $this->assertEquals(FrameBackground::DEFAULT_AREA['w'], $b->fresh()->area['w']);

        // Yang sudah standar tidak diubah, area-nya tetap.
        $this->assertEquals(76.0, $a->fresh()->area['w']);
    }
}
