<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhotoFrame extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'scope',
        'status',
        'print_size',
        'printer_setting',
        'file_path',
        'thumbnail_path',
        'is_active',
        'layout_json',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'layout_json' => 'array',
    ];
}
