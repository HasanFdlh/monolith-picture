<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Media extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'type',
        'file_name',
        'path',
        'size',
    ];

    public function session()
    {
        return $this->belongsTo(Session::class);
    }
}
