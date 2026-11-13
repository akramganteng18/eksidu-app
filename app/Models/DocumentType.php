<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    use HasFactory;

    protected $fillable = ['kode', 'nama', 'keterangan', 'aktif', 'layanan'];

    protected $casts = [
        'aktif' => 'boolean',
        'layanan' => 'array',
    ];

    public function pengajuans()
    {
        return $this->hasMany(Pengajuan::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }
}
