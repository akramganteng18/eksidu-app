<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\PanduanMediaController;
use App\Models\PanduanMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Pusat Panduan: daftar & pemutar materi multimedia. Setiap akses diperiksa lewat
 * PanduanMediaPolicy (materi untuk "siswa" tidak terbuka bagi role lain, dst.).
 */
class PanduanController extends Controller
{
    public function index(Request $request)
    {
        $daftar = PanduanMedia::untukPengguna($request->user())
            ->orderBy('urutan')->latest('id')->get();

        return view('panduan.index', compact('daftar'));
    }

    public function show(Request $request, PanduanMedia $panduan)
    {
        $this->authorize('view', $panduan);

        $lainnya = PanduanMedia::untukPengguna($request->user())
            ->whereKeyNot($panduan->id)->orderBy('urutan')->latest('id')->limit(4)->get();

        return view('panduan.show', compact('panduan', 'lainnya'));
    }

    /**
     * Streaming berkas media dengan dukungan HTTP Range (seek video/audio).
     */
    public function stream(Request $request, PanduanMedia $panduan)
    {
        $this->authorize('view', $panduan);
        abort_unless($panduan->sumber === 'unggah' && $panduan->file_path, 404);

        $disk = Storage::disk(config('eksidu.disk_berkas'));
        abort_unless($disk->exists($panduan->file_path), 404);

        $ekstensi = strtolower(pathinfo($panduan->file_path, PATHINFO_EXTENSION));
        $mime = PanduanMediaController::MIME[$panduan->jenis][$ekstensi] ?? null;
        abort_unless($mime, 404);

        return response()->file($disk->path($panduan->file_path), [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
