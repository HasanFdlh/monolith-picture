@extends('layouts.app')

@section('title', 'Branding Settings')

@section('content')
  <div class="branding-panel">
    <div class="branding-header">
      <div class="branding-icon">
        <i class="fa-solid fa-palette"></i>
      </div>
      <div>
        <h1>Branding Settings</h1>
        <p>Customize how your download pages look to visitors</p>
      </div>
    </div>

    @if (session('success'))
      <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif

    <form action="{{ $branding->exists ? route('branding.update') : route('branding.store') }}" method="POST"
      enctype="multipart/form-data">
      @csrf
      @if ($branding->exists)
        @method('PUT')
      @endif

      <div class="branding-grid">
        <div class="section-block">
          <div class="section-title">
            <i class="fa-solid fa-circle-info"></i>
            <span>Basic Information</span>
          </div>

          <div class="form-stack">
            <div>
              <label for="business_name" class="field-label">Business Name <span class="required">*</span></label>
              <input id="business_name" name="business_name" type="text" class="form-control"
                value="{{ old('business_name', $branding->business_name ?? 'Your Photobooth Business') }}" required>
            </div>

            <div>
              <label for="tagline" class="field-label">Tagline</label>
              <input id="tagline" name="tagline" type="text" class="form-control"
                value="{{ old('tagline', $branding->tagline ?? 'Capturing moments, creating memories') }}">
            </div>

            <div>
              <label for="logo" class="field-label">Upload Logo</label>
              <input id="logo" name="logo" type="file" class="form-control" accept="image/*">
              @if ($branding->logo_path)
                <div class="mt-2">
                  <img src="{{ asset('storage/' . $branding->logo_path) }}" alt="Logo"
                    style="max-height: 80px; border-radius: 10px;">
                </div>
              @endif
            </div>

            <div>
              <label for="logo_url" class="field-label">Or Logo URL</label>
              <input id="logo_url" name="logo_url" type="url" class="form-control"
                value="{{ old('logo_url', $branding->logo_url ?? '') }}">
            </div>

            <div>
              <label for="custom_message" class="field-label">Custom Message</label>
              <textarea id="custom_message" name="custom_message">{{ old('custom_message', $branding->custom_message ?? 'We hope you love your photos! Share them with friends and family, and do not forget to tag us on social media.') }}</textarea>
            </div>
          </div>
        </div>

        <div class="section-block">
          <div class="section-title">
            <i class="fa-solid fa-paintbrush"></i>
            <span>Colors &amp; Social Links</span>
          </div>

          <div class="social-stack">
            <div>
              <label for="primary_color" class="field-label">Primary Color</label>
              <div class="color-input">
                <input id="primary_color" name="primary_color" type="color" class="form-control-color"
                  value="{{ old('primary_color', $branding->primary_color ?? '#4f46e5') }}">
                <div class="color-preview"
                  style="background: {{ old('primary_color', $branding->primary_color ?? '#4f46e5') }};"></div>
              </div>
            </div>

            <div>
              <label for="secondary_color" class="field-label">Secondary Color</label>
              <div class="color-input">
                <input id="secondary_color" name="secondary_color" type="color" class="form-control-color"
                  value="{{ old('secondary_color', $branding->secondary_color ?? '#8b5cf6') }}">
                <div class="color-preview"
                  style="background: linear-gradient(90deg, {{ old('secondary_color', $branding->secondary_color ?? '#8b5cf6') }} 0%, #a855f7 100%);">
                </div>
              </div>
            </div>

            <div>
              <label for="background_style" class="field-label">Background Style</label>
              <select id="background_style" name="background_style" class="form-select">
                <option value="gradient"
                  {{ old('background_style', $branding->background_style ?? 'gradient') == 'gradient' ? 'selected' : '' }}>
                  Gradient</option>
                <option value="solid"
                  {{ old('background_style', $branding->background_style ?? '') == 'solid' ? 'selected' : '' }}>Solid
                </option>
                <option value="image"
                  {{ old('background_style', $branding->background_style ?? '') == 'image' ? 'selected' : '' }}>Image
                </option>
              </select>
            </div>

            <div>
              <label for="background_gradient" class="field-label">Background Gradient</label>
              <select id="background_gradient" name="background_gradient" class="form-select">
                <option value="purple"
                  {{ old('background_gradient', $branding->background_gradient ?? 'purple') == 'purple' ? 'selected' : '' }}>
                  Purple Gradient</option>
                <option value="blue"
                  {{ old('background_gradient', $branding->background_gradient ?? '') == 'blue' ? 'selected' : '' }}>Blue
                  Gradient</option>
                <option value="sunset"
                  {{ old('background_gradient', $branding->background_gradient ?? '') == 'sunset' ? 'selected' : '' }}>
                  Sunset Gradient</option>
              </select>
            </div>

            <div>
              <div class="gradient-preview"
                style="background: linear-gradient(90deg, {{ old('primary_color', $branding->primary_color ?? '#4f46e5') }} 0%, {{ old('secondary_color', $branding->secondary_color ?? '#8b5cf6') }} 100%);">
              </div>
            </div>

            <div>
              <label for="instagram_url" class="field-label">Instagram URL</label>
              <input id="instagram_url" name="instagram_url" type="url" class="form-control"
                value="{{ old('instagram_url', $branding->instagram_url ?? '') }}">
            </div>

            <div>
              <label for="facebook_url" class="field-label">Facebook URL</label>
              <input id="facebook_url" name="facebook_url" type="url" class="form-control"
                value="{{ old('facebook_url', $branding->facebook_url ?? '') }}">
            </div>

            <div>
              <label for="twitter_url" class="field-label">Twitter URL</label>
              <input id="twitter_url" name="twitter_url" type="url" class="form-control"
                value="{{ old('twitter_url', $branding->twitter_url ?? '') }}">
            </div>
            <div>
              <label for="website_url" class="field-label">Website URL</label>
              <input id="website_url" name="website_url" type="url" class="form-control"
                value="{{ old('website_url', $branding->website_url ?? '') }}">
            </div>
            <div>
              <label for="contact_email" class="field-label">Contact Email</label>
              <input id="contact_email" name="contact_email" type="email" class="form-control"
                value="{{ old('contact_email', $branding->contact_email ?? '') }}">
            </div>

            <div>
              <label for="contact_phone" class="field-label">Contact Phone</label>
              <input id="contact_phone" name="contact_phone" type="text" class="form-control"
                value="{{ old('contact_phone', $branding->contact_phone ?? '') }}">
            </div>

          </div>
        </div>
      </div>

      <div class="d-flex justify-content-end mt-4">
        <button type="submit" class="btn btn-primary px-4">Save Branding</button>
      </div>
    </form>
  </div>
@endsection
