<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Session extends Model
{
    use HasFactory;

    protected $fillable = [
        'booth_id',
        'session_code',
        'customer_name',
        'total_files',
        'total_size',
        'taken_at',
    ];

    protected $casts = [
        'taken_at' => 'datetime',
    ];

    public function booth()
    {
        return $this->belongsTo(Booth::class);
    }

    public function media()
    {
        return $this->hasMany(Media::class);
    }

    public function shares()
    {
        return $this->hasMany(Share::class);
    }
}
