<?php

namespace Database\Seeders;

use App\Models\PhotoFrame;
use Illuminate\Database\Seeder;

class PhotoFrameSeeder extends Seeder
{
    public function run(): void
    {
        PhotoFrame::firstOrCreate(
            ['name' => 'The (5)'],
            [
                'category' => 'All',
                'scope' => 'Generic',
                'status' => 'active',
                'print_size' => 'Normal',
                'printer_setting' => 'Primary Printer',
                'file_path' => 'frames/demo-1.png',
                'thumbnail_path' => 'frames/demo-1.png',
                'is_active' => true,
                'layout_json' => json_encode([
                    'slots' => [
                        ['x' => 0, 'y' => 0, 'w' => 1, 'h' => 1, 'label' => 'The Main Character'],
                        ['x' => 1, 'y' => 0, 'w' => 1, 'h' => 1, 'label' => 'The Ride or Die'],
                        ['x' => 0, 'y' => 1, 'w' => 1, 'h' => 1, 'label' => 'The Trendsetter'],
                        ['x' => 1, 'y' => 1, 'w' => 1, 'h' => 1, 'label' => 'The Chaos'],
                    ],
                ]),
            ]
        );

        PhotoFrame::firstOrCreate(
            ['name' => 'The (4)'],
            [
                'category' => 'All',
                'scope' => 'Generic',
                'status' => 'active',
                'print_size' => 'Normal',
                'printer_setting' => 'Primary Printer',
                'file_path' => 'frames/demo-2.png',
                'thumbnail_path' => 'frames/demo-2.png',
                'is_active' => true,
                'layout_json' => json_encode([
                    'slots' => [
                        ['x' => 0, 'y' => 0, 'w' => 1, 'h' => 1, 'label' => 'The Main Character'],
                        ['x' => 1, 'y' => 0, 'w' => 1, 'h' => 1, 'label' => 'The Ride or Die'],
                    ],
                ]),
            ]
        );

        PhotoFrame::firstOrCreate(
            ['name' => 'Landscape'],
            [
                'category' => 'All',
                'scope' => 'Generic',
                'status' => 'active',
                'print_size' => 'Normal',
                'printer_setting' => 'Primary Printer',
                'file_path' => 'frames/demo-3.png',
                'thumbnail_path' => 'frames/demo-3.png',
                'is_active' => true,
                'layout_json' => json_encode([
                    'slots' => [
                        ['x' => 0, 'y' => 0, 'w' => 1, 'h' => 1, 'label' => 'The Main Character'],
                        ['x' => 1, 'y' => 0, 'w' => 1, 'h' => 1, 'label' => 'The Ride or Die'],
                        ['x' => 0, 'y' => 1, 'w' => 1, 'h' => 1, 'label' => 'The Trendsetter'],
                        ['x' => 1, 'y' => 1, 'w' => 1, 'h' => 1, 'label' => 'The Chaos'],
                    ],
                ]),
            ]
        );
    }
}
