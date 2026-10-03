<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Session extends Model
{
    use HasFactory;

    protected $fillable = [
        'booth_id',
        'photo_frame_id',
        'photo_package_id',
        'allowed_takes',
        'payment_status',
        'paid_at',
        'session_code',
        'customer_name',
        'layout',
        'grid',
        'filter',
        'email',
        'total_files',
        'total_size',
        'taken_at',
    ];

    protected $casts = [
        'taken_at' => 'datetime',
        'paid_at' => 'datetime',
        'allowed_takes' => 'integer',
    ];

    public function booth()
    {
        return $this->belongsTo(Booth::class);
    }

    public function media()
    {
        return $this->hasMany(Media::class);
    }

    public function photoFrame()
    {
        return $this->belongsTo(PhotoFrame::class);
    }

    public function shares()
    {
        return $this->hasMany(Share::class);
    }

    public function photoPackage()
    {
        return $this->belongsTo(PhotoPackage::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function latestPayment()
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Sisa foto ("takes") yang masih boleh diambil pada sesi ini.
     */
    public function remainingTakes(): int
    {
        return max(0, (int) $this->allowed_takes - (int) $this->total_files);
    }
}
