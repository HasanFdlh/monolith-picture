<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhotoFrame extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'scope',
        'status',
        'print_size',
        'printer_setting',
        'file_path',
        'thumbnail_path',
        'is_active',
        'layout_json',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'layout_json' => 'array',
    ];

    // Dikirim ke Flutter lewat GET /api/templates supaya app bisa langsung
    // menampilkan PNG frame tanpa menebak path storage.
    protected $appends = ['image_url', 'thumbnail_url'];

    /** Lebar x tinggi kanvas default: strip vertikal (rasio 1 : 3). */
    public const DEFAULT_WIDTH = 600;
    public const DEFAULT_HEIGHT = 1800;

    public function getImageUrlAttribute(): ?string
    {
        return $this->publicUrl($this->file_path);
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->publicUrl($this->thumbnail_path ?: $this->file_path);
    }

    private function publicUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        return asset('storage/' . ltrim(preg_replace('#^/?storage/#', '', $path), '/'));
    }

    /**
     * Slot foto tersusun vertikal (atas ke bawah), semua dalam persen kanvas.
     * Sisa ruang di bagian bawah dipakai untuk logo/branding strip.
     */
    public static function verticalSlots(int $count, array $labels = []): array
    {
        $marginX = 8.0;
        $top = 4.0;
        $bottom = 14.0;
        $gap = 2.0;

        $height = (100 - $top - $bottom - $gap * ($count - 1)) / $count;
        $slots = [];

        for ($i = 0; $i < $count; $i++) {
            $slots[] = [
                'id' => 'photo-' . ($i + 1),
                'label' => $labels[$i] ?? 'Foto ' . ($i + 1),
                'x' => $marginX,
                'y' => round($top + $i * ($height + $gap), 3),
                'w' => 100 - 2 * $marginX,
                'h' => round($height, 3),
            ];
        }

        return $slots;
    }
}
