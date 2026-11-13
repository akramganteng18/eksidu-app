<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Jejak perubahan status sebuah pengajuan (audit trail per dokumen).
 */
class PengajuanRiwayat extends Model
{
    public $timestamps = false;

    protected $fillable = ['pengajuan_id', 'user_id', 'status_dari', 'status_ke', 'catatan', 'created_at'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (PengajuanRiwayat $r) {
            $r->created_at = $r->created_at ?? now();
        });
    }

    public function pengajuan()
    {
        return $this->belongsTo(Pengajuan::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function catat(Pengajuan $pengajuan, ?string $dari, string $ke, ?int $userId = null, ?string $catatan = null): self
    {
        return static::create([
            'pengajuan_id' => $pengajuan->id,
            'user_id'      => $userId,
            'status_dari'  => $dari,
            'status_ke'    => $ke,
            'catatan'      => $catatan,
        ]);
    }

    public function labelKe(): string
    {
        return Pengajuan::STATUS_LABEL[$this->status_ke] ?? $this->status_ke;
    }

    public function toneKe(): string
    {
        return match ($this->status_ke) {
            'pending' => 'amber', 'diproses' => 'sky', 'approved' => 'green',
            'selesai' => 'teal', 'rejected' => 'rose', default => 'slate',
        };
    }
}
