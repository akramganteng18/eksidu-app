<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Pengajuan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Satu-satunya pintu akses ke berkas pendukung pengajuan.
 * Berkas tersimpan di disk privat; izin diperiksa lewat PengajuanPolicy
 * (admin, atau siswa pemilik pengajuan). Mengganti ID di URL tidak membuka berkas orang lain.
 */
class BerkasController extends Controller
{
    private const MIME = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
    ];

    public function pengajuan(Request $request, Pengajuan $pengajuan)
    {
        $this->authorize('viewBerkas', $pengajuan);
        abort_unless($pengajuan->punyaBerkas(), 404);

        $path = $pengajuan->file_pendukung;
        $disk = Storage::disk(config('eksidu.disk_berkas'));

        if (! $disk->exists($path)) {
            // Berkas dari versi lama masih berada di disk publik. Tetap dilayani lewat
            // pemeriksaan izin; pindahkan ke disk privat dengan: php artisan eksidu:amankan-berkas
            $disk = Storage::disk('public');
            abort_unless($disk->exists($path), 404);
        }

        $ekstensi = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = self::MIME[$ekstensi] ?? null;
        abort_unless($mime, 404);

        $unduh = $request->boolean('unduh');
        $nama = Str::slug($pengajuan->kode_tiket) . '-berkas.' . $ekstensi;

        ActivityLog::catat($request->user()->id, $unduh ? 'unduh_berkas' : 'lihat_berkas',
            ($unduh ? 'Mengunduh' : 'Membuka') . " berkas pendukung tiket {$pengajuan->kode_tiket}.");

        return response()->file($disk->path($path), [
            'Content-Type' => $mime,
            'Content-Disposition' => ($unduh ? 'attachment' : 'inline') . '; filename="' . $nama . '"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
