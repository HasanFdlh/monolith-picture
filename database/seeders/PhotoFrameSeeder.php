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
                'layout_json' => [
                    'slots' => [
                        ['x' => 0, 'y' => 0, 'w' => 1, 'h' => 1, 'label' => 'The Main Character'],
                        ['x' => 1, 'y' => 0, 'w' => 1, 'h' => 1, 'label' => 'The Ride or Die'],
                        ['x' => 0, 'y' => 1, 'w' => 1, 'h' => 1, 'label' => 'The Trendsetter'],
                        ['x' => 1, 'y' => 1, 'w' => 1, 'h' => 1, 'label' => 'The Chaos'],
                    ],
                ],
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
                'layout_json' => [
                    'slots' => [
                        ['x' => 0, 'y' => 0, 'w' => 1, 'h' => 1, 'label' => 'The Main Character'],
                        ['x' => 1, 'y' => 0, 'w' => 1, 'h' => 1, 'label' => 'The Ride or Die'],
                    ],
                ],
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
                'layout_json' => [
                    'slots' => [
                        ['x' => 0, 'y' => 0, 'w' => 1, 'h' => 1, 'label' => 'The Main Character'],
                        ['x' => 1, 'y' => 0, 'w' => 1, 'h' => 1, 'label' => 'The Ride or Die'],
                        ['x' => 0, 'y' => 1, 'w' => 1, 'h' => 1, 'label' => 'The Trendsetter'],
                        ['x' => 1, 'y' => 1, 'w' => 1, 'h' => 1, 'label' => 'The Chaos'],
                    ],
                ],
            ]
        );

        PhotoFrame::firstOrCreate(
            ['name' => 'Frame 4x4'],
            [
                'category' => 'All',
                'scope' => 'Generic',
                'status' => 'active',
                'print_size' => 'Normal',
                'printer_setting' => 'Primary Printer',
                'is_active' => true,
                'layout_json' => [
                    'width' => 1000,
                    'height' => 1000,
                    'slots' => $this->gridSlots(4, 4),
                ],
            ]
        );
    }

    private function gridSlots(int $columns, int $rows): array
    {
        $slots = [];
        $width = 100 / $columns;
        $height = 100 / $rows;

        for ($row = 0; $row < $rows; $row++) {
            for ($column = 0; $column < $columns; $column++) {
                $number = count($slots) + 1;
                $slots[] = [
                    'id' => "photo-{$number}",
                    'label' => "Foto {$number}",
                    'x' => $column * $width,
                    'y' => $row * $height,
                    'w' => $width,
                    'h' => $height,
                ];
            }
        }

        return $slots;
    }
}
