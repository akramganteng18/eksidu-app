<?php

namespace App\Support;

use Carbon\Carbon;

class Akademik
{
    /**
     * Tahun ajaran berjalan, mis. "2026/2027". Tahun ajaran baru dimulai bulan Juli.
     */
    public static function tahunAjaran(?Carbon $tanggal = null): string
    {
        $tanggal ??= now();
        $awal = $tanggal->month >= 7 ? $tanggal->year : $tanggal->year - 1;

        return $awal . '/' . ($awal + 1);
    }

    /**
     * Menghindari karakter wildcard LIKE agar pencarian "%" atau "_" tidak menjadi pola.
     */
    public static function likeAman(string $kata): string
    {
        return '%' . addcslashes($kata, '%_\\') . '%';
    }
}
