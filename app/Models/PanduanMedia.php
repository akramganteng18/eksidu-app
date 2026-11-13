<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Materi panduan multimedia (video / audio) yang dikelola Admin.
 * Berkas unggahan disimpan di disk privat dan hanya dilayani lewat controller.
 */
class PanduanMedia extends Model
{
    protected $table = 'panduan_media';

    public const JENIS = ['video' => 'Video', 'audio' => 'Audio'];
    public const SUMBER = ['unggah' => 'Unggah berkas', 'tautan' => 'Tautan YouTube'];
    public const UNTUK = ['semua' => 'Semua pengguna', 'siswa' => 'Siswa saja', 'admin' => 'Admin saja'];

    protected $fillable = [
        'judul', 'deskripsi', 'jenis', 'sumber', 'file_path', 'mime', 'url',
        'untuk', 'urutan', 'aktif', 'dibuat_oleh',
    ];

    protected $casts = [
        'aktif'  => 'boolean',
        'urutan' => 'integer',
    ];

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }

    /** Materi yang boleh dilihat oleh pengguna tertentu. */
    public function scopeUntukPengguna($query, User $user)
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where('aktif', true)->whereIn('untuk', ['semua', $user->role]);
    }

    public function bisaDilihatOleh(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $this->aktif && in_array($this->untuk, ['semua', $user->role], true);
    }

    public function isVideo(): bool
    {
        return $this->jenis === 'video';
    }

    public function isAudio(): bool
    {
        return $this->jenis === 'audio';
    }

    public function isTautan(): bool
    {
        return $this->sumber === 'tautan';
    }

    /** ID video YouTube dari berbagai bentuk URL, atau null bila tidak valid. */
    public static function ekstrakIdYoutube(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $pola = '~^(?:https?://)?(?:www\.|m\.)?(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})(?:[?&#].*)?$~';

        return preg_match($pola, trim($url), $cocok) ? $cocok[1] : null;
    }

    public function urlEmbed(): ?string
    {
        $id = self::ekstrakIdYoutube($this->url);

        return $id ? 'https://www.youtube-nocookie.com/embed/' . $id . '?rel=0' : null;
    }
}
