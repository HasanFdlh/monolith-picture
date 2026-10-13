<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Gambar background untuk frame BAWAAN aplikasi Flutter. Gambar ini mengisi
 * area putih di sekeliling dan di sela slot foto pada lembar frame.
 */
class FrameBackground extends Model
{
    protected $fillable = [
        'frame_key', 'name', 'image_path', 'width', 'height',
        'area_x', 'area_y', 'area_w', 'area_h', 'is_active', 'sort_order',
    ];

    protected $casts = ['is_active' => 'boolean', 'sort_order' => 'integer'];

    protected $appends = ['image_url', 'area'];

    /** Area foto bawaan: hampir memenuhi lembar. */
    public const DEFAULT_AREA = ['x' => 4.0, 'y' => 3.0, 'w' => 92.0, 'h' => 94.0];

    /**
     * Daftar frame bawaan Flutter (harus sama dengan `kFrameOptions` di
     * lib/models/booth_models.dart). `cols` x `rows` hanya untuk pratinjau
     * di dashboard, `aspect` = lebar / tinggi lembar.
     */
    public const CATALOG = [
        ['key' => 'main-character',   'name' => 'Main Character 3x2', 'slots' => 6, 'cols' => 2, 'rows' => 3, 'aspect' => 0.72],
        ['key' => 'main-character-4', 'name' => 'Main Character 4x2', 'slots' => 8, 'cols' => 2, 'rows' => 4, 'aspect' => 0.60],
        ['key' => 'spotlight',        'name' => 'Spotlight 3x2',      'slots' => 6, 'cols' => 3, 'rows' => 2, 'aspect' => 1.60],
        ['key' => 'spotlight-4',      'name' => 'Spotlight 4x2',      'slots' => 8, 'cols' => 4, 'rows' => 2, 'aspect' => 2.00],
        ['key' => 'straight-3',       'name' => 'Lurus 3 Foto',       'slots' => 3, 'cols' => 1, 'rows' => 3, 'aspect' => 0.55],
        ['key' => 'straight-4',       'name' => 'Lurus 4 Foto',       'slots' => 4, 'cols' => 1, 'rows' => 4, 'aspect' => 0.45],
        ['key' => 'row-3',            'name' => 'Sebaris 3 Foto',     'slots' => 3, 'cols' => 3, 'rows' => 1, 'aspect' => 2.40],
        ['key' => 'minimal-slate',    'name' => 'Minimal Slate',      'slots' => 4, 'cols' => 4, 'rows' => 1, 'aspect' => 3.00],
    ];

    /** Urutan tampil di aplikasi: sort_order lalu urutan upload. */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * frame_key khusus: desain yang berlaku untuk SEMUA frame bawaan (upload
     * sekali). API menggandakannya per frame; aplikasi menyesuaikan susunan
     * foto dengan bentuk area desain.
     */
    public const UNIVERSAL_KEY = 'all';

    public const UNIVERSAL = [
        'key' => self::UNIVERSAL_KEY,
        'name' => 'Semua Frame',
        // Hanya untuk pratinjau di dashboard (2 kolom x 4 baris).
        'slots' => 8, 'cols' => 2, 'rows' => 4, 'aspect' => 0.60,
    ];

    /** Entri katalog frame, atau entri "Semua Frame" untuk key `all`. */
    public static function entry(string $key): ?array
    {
        return $key === self::UNIVERSAL_KEY ? self::UNIVERSAL : self::catalogEntry($key);
    }

    public static function catalogEntry(string $key): ?array
    {
        foreach (self::CATALOG as $entry) {
            if ($entry['key'] === $key) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * Area foto (persen dari lembar). Foto hanya ditempatkan di dalam kotak
     * ini, sisanya (logo, tulisan, hiasan) tetap terlihat dari background.
     */
    public function getAreaAttribute(): array
    {
        $d = self::DEFAULT_AREA;

        return [
            'x' => (float) ($this->area_x ?? $d['x']),
            'y' => (float) ($this->area_y ?? $d['y']),
            'w' => (float) ($this->area_w ?? $d['w']),
            'h' => (float) ($this->area_h ?? $d['h']),
        ];
    }

    /** Lebar / tinggi gambar, atau null bila belum diketahui. */
    public function aspect(): ?float
    {
        return ($this->width && $this->height) ? $this->width / $this->height : null;
    }

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image_path) {
            return null;
        }

        // Dilayani lewat route API (bukan asset('storage/...')) supaya gambar
        // tetap tampil walau `php artisan storage:link` belum dijalankan, dan
        // membawa header CORS bila aplikasi Flutter dijalankan di web/Chrome.
        // ?v= supaya aplikasi mengambil gambar baru setelah di-upload ulang.
        return route('api.frame-backgrounds.image', ['background' => $this->id])
            . '?v=' . ($this->updated_at?->timestamp ?? 0);
    }
}
