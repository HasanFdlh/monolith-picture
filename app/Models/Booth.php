<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Booth extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'location',
        'is_active',
    ];

    public function sessions()
    {
        return $this->hasMany(Session::class);
    }
}
