<?php

namespace Database\Seeders;

use App\Models\PhotoPackage;
use Illuminate\Database\Seeder;

class PhotoPackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            [
                'name' => 'Starter (2 foto)',
                'takes' => 2,
                'price' => 20000,
                'description' => 'Cocok untuk coba-coba, dapat 2 kali jepret.',
                'sort_order' => 1,
            ],
            [
                'name' => 'Classic (4 foto)',
                'takes' => 4,
                'price' => 35000,
                'description' => 'Paket favorit, dapat 4 kali jepret.',
                'sort_order' => 2,
            ],
            [
                'name' => 'Premium (6 foto)',
                'takes' => 6,
                'price' => 50000,
                'description' => 'Dapat 6 kali jepret untuk hasil maksimal.',
                'sort_order' => 3,
            ],
            [
                'name' => 'Unlimited (10 foto)',
                'takes' => 10,
                'price' => 75000,
                'description' => 'Paket paling puas, dapat 10 kali jepret.',
                'sort_order' => 4,
            ],
        ];

        foreach ($packages as $package) {
            PhotoPackage::firstOrCreate(
                ['name' => $package['name']],
                [
                    'takes' => $package['takes'],
                    'price' => $package['price'],
                    'description' => $package['description'],
                    'is_active' => true,
                    'sort_order' => $package['sort_order'],
                ]
            );
        }
    }
}
