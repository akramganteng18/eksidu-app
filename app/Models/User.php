<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'nis_nip', 'kelas', 'no_telp', 'foto', 'aktif',
    ];

    protected $hidden = [
        'password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes',
    ];

    // PENTING (Laravel 10): cast WAJIB berupa properti $casts. Method casts() hanya
    // dikenali mulai Laravel 11 dan akan diabaikan begitu saja di versi ini.
    // Password sengaja TIDAK memakai cast "hashed"; seluruh kode memanggil Hash::make().
    protected $casts = [
        'email_verified_at' => 'datetime',
        'aktif' => 'boolean',
        'two_factor_enabled_at' => 'datetime',
    ];

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isSiswa(): bool
    {
        return $this->role === 'siswa';
    }

    public function twoFactorEnabled(): bool
    {
        return $this->isAdmin() && ! is_null($this->two_factor_enabled_at) && filled($this->two_factor_secret);
    }

    public function pengajuans()
    {
        return $this->hasMany(Pengajuan::class);
    }

    public function documentArchives()
    {
        return $this->hasMany(StudentDocumentArchive::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function uploadedDocuments()
    {
        return $this->hasMany(Document::class, 'uploaded_by');
    }

    public function documentLoans()
    {
        return $this->hasMany(DocumentLoan::class);
    }

    public function pengajuanDiverifikasi()
    {
        return $this->hasMany(Pengajuan::class, 'verified_by');
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];
        $first = mb_substr($parts[0] ?? '', 0, 1);
        $second = isset($parts[1]) ? mb_substr($parts[1], 0, 1) : '';

        return mb_strtoupper($first . $second) ?: 'U';
    }

    /** URL foto profil (berotorisasi: hanya pemilik & admin). ?v= membuat cache peramban ikut berganti saat foto diganti. */
    public function fotoUrl(): ?string
    {
        return $this->foto
            ? route('avatar.show', ['user' => $this->id, 'v' => substr(md5($this->foto), 0, 8)])
            : null;
    }

    /** Menghapus berkas foto dari disk privat maupun disk publik lama. */
    public function hapusBerkasFoto(): void
    {
        if (! $this->foto) {
            return;
        }

        Storage::disk(config('eksidu.disk_berkas'))->delete($this->foto);
        Storage::disk('public')->delete($this->foto);
    }

    public function labelRole(): string
    {
        return $this->isAdmin() ? 'Admin / Petugas TU' : 'Siswa / Alumni';
    }
}
