<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Pengumuman extends Model
{
    /** Nama tabel ditetapkan karena pluralisasi otomatis Laravel mengubah Pengumuman menjadi pengumumen. */
    protected $table = 'pengumumans';

    protected $fillable = [
        'judul', 'isi', 'sasaran', 'diterbitkan', 'mulai_pada', 'berakhir_pada', 'dibuat_oleh',
    ];

    protected $casts = [
        'diterbitkan' => 'boolean',
        'mulai_pada' => 'datetime',
        'berakhir_pada' => 'datetime',
    ];

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function scopeTayangUntuk(Builder $query, User $user): Builder
    {
        $sasaran = $user->isAdmin() ? ['semua', 'admin'] : ['semua', 'siswa'];

        return $query->where('diterbitkan', true)
            ->whereIn('sasaran', $sasaran)
            ->where(function (Builder $q) {
                $q->whereNull('mulai_pada')->orWhere('mulai_pada', '<=', now());
            })
            ->where(function (Builder $q) {
                $q->whereNull('berakhir_pada')->orWhere('berakhir_pada', '>=', now());
            });
    }
}
