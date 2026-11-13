<?php

namespace App\Support;

use DateTimeInterface;
use Illuminate\Support\Facades\URL;

/**
 * Membuat URL QR bertanda tangan memakai alamat aplikasi yang dapat dijangkau
 * perangkat lain. Signature dibuat relatif sehingga host publik dapat dikonfigurasi
 * tanpa mengubah payload/path yang ditandatangani.
 */
class QrUrl
{
    public static function temporarySignedRoute(
        string $name,
        DateTimeInterface $expiration,
        array $parameters = []
    ): string {
        $relative = URL::temporarySignedRoute($name, $expiration, $parameters, false);
        $baseUrl = rtrim((string) config('eksidu.public_url', config('app.url')), '/');

        return $baseUrl . '/' . ltrim($relative, '/');
    }
}
