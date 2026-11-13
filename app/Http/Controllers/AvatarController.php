<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Foto profil dilayani lewat controller (disk privat), bukan URL publik.
 * Hanya pemilik foto dan admin yang boleh melihatnya; tidak butuh "php artisan storage:link".
 */
class AvatarController extends Controller
{
    private const MIME = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];

    public function show(Request $request, User $user)
    {
        $pengakses = $request->user();
        abort_unless($pengakses->isAdmin() || $pengakses->id === $user->id, 404);
        abort_unless($user->foto, 404);

        $disk = Storage::disk(config('eksidu.disk_berkas'));
        if (! $disk->exists($user->foto)) {
            // Foto dari versi lama masih di disk publik; pindahkan dengan: php artisan eksidu:amankan-berkas
            $disk = Storage::disk('public');
            abort_unless($disk->exists($user->foto), 404);
        }

        $mime = self::MIME[strtolower(pathinfo($user->foto, PATHINFO_EXTENSION))] ?? null;
        abort_unless($mime, 404);

        $response = response()->file($disk->path($user->foto), [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
        ]);

        // BinaryFileResponse dapat menambahkan directive "public" secara bawaan.
        // Paksa menjadi private agar foto profil tidak masuk shared cache/proxy.
        $response->setPrivate();
        $response->setMaxAge(3600);

        return $response;
    }
}
