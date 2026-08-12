<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branding extends Model
{
    use HasFactory;

    protected $table = 'brandings';

    protected $fillable = [
        'business_name',
        'tagline',
        'logo_path',
        'logo_url',
        'custom_message',
        'primary_color',
        'secondary_color',
        'background_style',
        'background_gradient',
        'instagram_url',
        'facebook_url',
        'twitter_url',
    ];
}
