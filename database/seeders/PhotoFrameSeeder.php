<?php

namespace Database\Seeders;

use App\Models\PhotoFrame;
use Illuminate\Database\Seeder;

/**
 * Template bawaan. Semuanya strip vertikal (memanjang ke bawah, kanvas
 * 600 x 1800) dan posisi slot disimpan dalam persen, sama seperti yang
 * ditulis editor layout di dashboard.
 *
 * Memakai updateOrCreate supaya `php artisan db:seed --class=PhotoFrameSeeder`
 * juga mengubah template lama (grid landscape) menjadi vertikal.
 */
class PhotoFrameSeeder extends Seeder
{
    public function run(): void
    {
        $captions = ['The Main Character', 'The Ride or Die', 'The Trendsetter', 'The Chaos'];

        $frames = [
            ['name' => 'The (5)', 'file' => 'frames/demo-1.png', 'slots' => 4, 'labels' => $captions],
            ['name' => 'The (4)', 'file' => 'frames/demo-2.png', 'slots' => 3, 'labels' => $captions],
            ['name' => 'Landscape', 'file' => 'frames/demo-3.png', 'slots' => 4, 'labels' => []],
            ['name' => 'Frame 4x4', 'file' => null, 'slots' => 4, 'labels' => []],
        ];

        foreach ($frames as $frame) {
            PhotoFrame::updateOrCreate(
                ['name' => $frame['name']],
                [
                    'category' => 'All',
                    'scope' => 'Generic',
                    'status' => 'active',
                    'print_size' => 'Normal',
                    'printer_setting' => 'Primary Printer',
                    'file_path' => $frame['file'],
                    'thumbnail_path' => $frame['file'],
                    'is_active' => true,
                    'layout_json' => [
                        'width' => PhotoFrame::DEFAULT_WIDTH,
                        'height' => PhotoFrame::DEFAULT_HEIGHT,
                        'slots' => PhotoFrame::verticalSlots($frame['slots'], $frame['labels']),
                    ],
                ]
            );
        }
    }
}
