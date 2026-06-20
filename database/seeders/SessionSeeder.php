<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Booth;
use App\Models\Session;
use App\Models\Media;

class SessionSeeder extends Seeder
{
    public function run(): void
    {
        // BOOTHS
        $booth1 = Booth::create([
            'id' => 1,
            'name' => '2000s Booth',
            'slug' => '2000s-booth',
        ]);

        $booth2 = Booth::create([
            'id' => 2,
            'name' => 'Retro Neon Booth',
            'slug' => 'retro-neon-booth',
        ]);

        // SESSIONS
        $session1 = Session::create([
            'id' => 1,
            'booth_id' => $booth1->id,
            'session_code' => '20260620120001',
            'taken_at' => now(),
        ]);

        $session2 = Session::create([
            'id' => 2,
            'booth_id' => $booth2->id,
            'session_code' => '20260620130001',
            'taken_at' => now(),
        ]);

        $session10 = Session::create([
            'id' => 10,
            'booth_id' => $booth1->id,
            'session_code' => '20260620199999',
            'taken_at' => now(),
        ]);

        // MEDIA (session 1)
        Media::create([
            'session_id' => $session1->id,
            'type' => 'photo',
            'file_name' => 'img1.jpg',
            'path' => 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d',
            'size' => 204800,
        ]);

        Media::create([
            'session_id' => $session2->id,
            'type' => 'photo',
            'file_name' => 'img2.jpg',
            'path' => 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee',
            'size' => 309600,
        ]);

        // MEDIA (session 10 - 5 images)
        $mediaData = [
            ['img1.jpg', 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee', 120000],
            ['img2.jpg', 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d', 130000],
            ['img3.jpg', 'https://images.unsplash.com/photo-1517816743773-6e0fd518b4a6', 140000],
            ['img4.jpg', 'https://images.unsplash.com/photo-1498050108023-c5249f4df085', 150000],
            ['img5.jpg', 'https://images.unsplash.com/photo-1501785888041-af3ef285b470', 160000],
        ];

        foreach ($mediaData as [$file, $path, $size]) {
            Media::create([
                'session_id' => $session10->id,
                'type' => 'photo',
                'file_name' => $file,
                'path' => $path,
                'size' => $size,
            ]);
        }
    }
}
