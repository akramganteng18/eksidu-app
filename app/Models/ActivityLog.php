<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'action', 'description', 'created_at'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (ActivityLog $log) {
            $log->created_at = $log->created_at ?? now();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function catat(?int $userId, string $action, ?string $description = null): void
    {
        static::create([
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
        ]);
    }

    /** Nama "tone" warna (lihat kelas .tone-* di resources/css/app.css). */
    public function tone(): string
    {
        return match (true) {
            str_starts_with($this->action, 'login'), str_starts_with($this->action, 'logout') => 'slate',
            str_starts_with($this->action, 'tambah'), str_starts_with($this->action, 'ajukan'), str_starts_with($this->action, 'register') => 'sky',
            str_starts_with($this->action, 'ubah'), str_starts_with($this->action, 'update'), str_starts_with($this->action, 'verifikasi') => 'amber',
            str_starts_with($this->action, 'hapus') => 'rose',
            str_starts_with($this->action, 'konfirmasi'), str_starts_with($this->action, 'catat_pengembalian') => 'green',
            str_starts_with($this->action, 'export'), str_starts_with($this->action, 'cetak') => 'violet',
            default => 'slate',
        };
    }

    /** Dipertahankan untuk kompatibilitas. */
    public function actionColor(): string
    {
        return $this->tone();
    }

    public function labelAksi(): string
    {
        return ucfirst(str_replace('_', ' ', $this->action));
    }
}
