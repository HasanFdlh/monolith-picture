<?php

namespace App\Http\Controllers;

use App\Models\Branding;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BrandingController extends Controller
{
    public function index()
    {
        $data = [
            "title" => "Branding Settings",
            "subtitle" => "Customize how your download pages look to visitors",
            "branding" => Branding::firstOrNew()
        ];

        return view('branding.index', $data);
    }

    public function store(Request $request)
    {
        $validated = $this->validateBranding($request);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('branding', 'public');
            $validated['logo_path'] = $path;
        }

        Branding::updateOrCreate([], $validated);

        return redirect()->route('branding')->with('success', 'Branding settings saved successfully.');
    }

    public function update(Request $request)
    {
        $validated = $this->validateBranding($request);

        $branding = Branding::first();

        if ($request->hasFile('logo')) {
            if ($branding && $branding->logo_path && Storage::disk('public')->exists($branding->logo_path)) {
                Storage::disk('public')->delete($branding->logo_path);
            }

            $path = $request->file('logo')->store('branding', 'public');
            $validated['logo_path'] = $path;
        }

        if ($branding) {
            $branding->update($validated);
        } else {
            Branding::create($validated);
        }

        return redirect()->route('branding')->with('success', 'Branding settings updated successfully.');
    }

    protected function validateBranding(Request $request): array
    {
        return $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'logo_url' => ['nullable', 'url', 'max:255'],
            'custom_message' => ['nullable', 'string'],
            'primary_color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'secondary_color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'background_style' => ['nullable', 'in:gradient,solid,image'],
            'background_gradient' => ['nullable', 'string', 'max:255'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'facebook_url' => ['nullable', 'url', 'max:255'],
            'twitter_url' => ['nullable', 'url', 'max:255'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,svg,webp'],
        ]);
    }
}
