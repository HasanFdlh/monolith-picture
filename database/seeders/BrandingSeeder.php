<?php

namespace Database\Seeders;

use App\Models\Branding;
use Illuminate\Database\Seeder;

class BrandingSeeder extends Seeder
{
    public function run(): void
    {
        Branding::updateOrCreate(
            ['id' => 1],
            [
                'business_name' => 'Your Photobooth Business',
                'tagline' => 'Capturing moments, creating memories',
                'logo_url' => '',
                'custom_message' => 'We hope you love your photos! Share them with friends and family, and do not forget to tag us on social media.',
                'primary_color' => '#4f46e5',
                'secondary_color' => '#8b5cf6',
                'background_style' => 'gradient',
                'background_gradient' => 'purple',
                'instagram_url' => '',
                'facebook_url' => '',
                'twitter_url' => '',
                'website_url' => '',
                'contact_email' => '',
                'contact_person' => '',
            ]
        );
    }
}
