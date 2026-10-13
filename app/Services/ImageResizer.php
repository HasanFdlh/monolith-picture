<?php

namespace App\Services;

use RuntimeException;

/**
 * Mengubah ukuran gambar (px) memakai ekstensi GD bawaan PHP.
 */
class ImageResizer
{
    /** Potong bagian tengah agar pas (proporsi gambar terjaga, tidak gepeng). */
    public const FIT_COVER = 'cover';

    /** Regangkan ke ukuran persis (proporsi bisa berubah). */
    public const FIT_STRETCH = 'stretch';

    public static function available(): bool
    {
        return function_exists('imagecreatefromstring')
            && function_exists('imagecreatetruecolor')
            && function_exists('imagecopyresampled');
    }

    /**
     * @param  string  $binary  isi file gambar asli
     * @param  string  $format  png | jpg | jpeg | webp (format keluaran)
     * @return string  isi file gambar baru berukuran persis $width x $height
     */
    public static function resize(
        string $binary,
        int $width,
        int $height,
        string $fit = self::FIT_COVER,
        string $format = 'png',
    ): string {
        if (!self::available()) {
            throw new RuntimeException('Ekstensi PHP GD belum aktif (aktifkan extension=gd di php.ini).');
        }

        $format = strtolower($format);
        if ($format === 'webp' && !function_exists('imagewebp')) {
            throw new RuntimeException('GD di server ini belum mendukung WEBP.');
        }

        $source = @imagecreatefromstring($binary);
        if ($source === false) {
            throw new RuntimeException('Gambar tidak bisa dibaca.');
        }

        $sw = imagesx($source);
        $sh = imagesy($source);

        if ($fit === self::FIT_STRETCH) {
            [$sx, $sy, $cw, $ch] = [0, 0, $sw, $sh];
        } else {
            // cover: ambil bagian tengah dengan rasio yang sama seperti target.
            $scale = max($width / $sw, $height / $sh);
            $cw = max(1, min($sw, (int) round($width / $scale)));
            $ch = max(1, min($sh, (int) round($height / $scale)));
            $sx = (int) floor(($sw - $cw) / 2);
            $sy = (int) floor(($sh - $ch) / 2);
        }

        $target = imagecreatetruecolor($width, $height);

        // Pertahankan transparansi (PNG/WEBP).
        imagealphablending($target, false);
        imagesavealpha($target, true);
        imagefill($target, 0, 0, imagecolorallocatealpha($target, 0, 0, 0, 127));

        imagecopyresampled($target, $source, 0, 0, $sx, $sy, $width, $height, $cw, $ch);

        ob_start();
        match ($format) {
            'jpg', 'jpeg' => imagejpeg($target, null, 92),
            'webp' => imagewebp($target, null, 90),
            default => imagepng($target, null, 6),
        };

        return (string) ob_get_clean();
    }
}
