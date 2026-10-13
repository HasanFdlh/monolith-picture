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
        'printed_at',
        'print_count',
        'session_code',
        'customer_name',
        'layout',
        'grid',
        'filter',
        'composition',
        'email',
        'total_files',
        'total_size',
        'taken_at',
    ];

    protected $casts = [
        'taken_at' => 'datetime',
        'paid_at' => 'datetime',
        'printed_at' => 'datetime',
        'print_count' => 'integer',
        'allowed_takes' => 'integer',
        'composition' => 'array',
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

    /**
     * Catat bahwa sesi ini dicetak (dipakai halaman "Hasil Print" di dashboard).
     */
    public function markPrinted(): void
    {
        $this->forceFill([
            'printed_at' => now(),
            'print_count' => (int) $this->print_count + 1,
        ])->save();
    }

    /**
     * Keluarkan sesi dari daftar "Hasil Print" tanpa menghapus sesi/fotonya.
     */
    public function unmarkPrinted(): void
    {
        $this->forceFill(['printed_at' => null, 'print_count' => 0])->save();
    }

    /**
     * Hapus sesi PERMANEN beserta file foto di storage (media, share ikut
     * terhapus; pembayaran tetap ada dengan session_id dikosongkan).
     */
    public function deleteWithMedia(): void
    {
        foreach ($this->media as $media) {
            $path = ltrim(preg_replace('#^/?storage/#', '', (string) $media->path), '/');
            if ($path !== '' && \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
            }
        }

        $this->delete();
    }

    public function scopePrinted($query)
    {
        return $query->whereNotNull('printed_at');
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
