<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    /**
     * Status dari Midtrans yang dianggap "uang sudah diterima".
     */
    public const PAID_STATUSES = ['settlement', 'capture'];

    protected $fillable = [
        'order_id',
        'session_id',
        'photo_package_id',
        'takes',
        'amount',
        'status',
        'payment_type',
        'transaction_id',
        'snap_token',
        'snap_redirect_url',
        'paid_at',
        'expires_at',
        'raw_response',
    ];

    protected $casts = [
        'takes' => 'integer',
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'expires_at' => 'datetime',
        'raw_response' => 'array',
    ];

    public function session()
    {
        return $this->belongsTo(Session::class);
    }

    public function photoPackage()
    {
        return $this->belongsTo(PhotoPackage::class);
    }

    public function isPaid(): bool
    {
        return in_array($this->status, self::PAID_STATUSES, true);
    }

    public function isExpired(): bool
    {
        return in_array($this->status, ['expire', 'cancel', 'deny', 'failure'], true);
    }
}
