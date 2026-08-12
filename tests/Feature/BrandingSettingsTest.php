<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandingSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_branding_settings_can_be_created_and_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $payload = [
            'business_name' => 'Luminash Studio',
            'tagline' => 'Capturing moments, creating memories',
            'logo_url' => 'https://example.com/logo.png',
            'custom_message' => 'Thanks for visiting!',
            'primary_color' => '#4f46e5',
            'secondary_color' => '#8b5cf6',
            'background_style' => 'gradient',
            'background_gradient' => 'purple',
            'instagram_url' => 'https://instagram.com/luminash',
            'facebook_url' => 'https://facebook.com/luminash',
            'twitter_url' => 'https://x.com/luminash',
        ];

        $this->post(route('branding.store'), $payload)
            ->assertRedirect(route('branding'));

        $this->assertDatabaseHas('brandings', [
            'business_name' => 'Luminash Studio',
        ]);

        $this->put(route('branding.update'), array_merge($payload, [
            'business_name' => 'Luminash Studio Updated',
        ]))
            ->assertRedirect(route('branding'));

        $this->assertDatabaseHas('brandings', [
            'business_name' => 'Luminash Studio Updated',
        ]);
    }
}
