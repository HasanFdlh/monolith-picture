<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhotoPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'takes',
        'price',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'takes' => 'integer',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function sessions()
    {
        return $this->hasMany(Session::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
