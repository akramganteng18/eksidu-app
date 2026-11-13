<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Pengajuan;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class DigitalDocumentController extends Controller
{
    public function index(Request $request)
    {
        // Filter sebelum pagination agar total dan nomor halaman mencerminkan dokumen
        // yang benar-benar dapat dibuka, bukan hanya baris pengajuan yang berstatus approved.
        $eligible = $request->user()->pengajuans()->with(['archive', 'documentVersion'])
            ->where('bentuk_dokumen', 'digital')->where('status', 'approved')
            ->whereNull('digital_access_revoked_at')->whereNotNull('digital_access_hash')
            ->latest()->get()
            ->filter(fn ($p) => $p->archive && $p->documentVersion && $p->documentVersion->status === 'available'
                && $p->documentVersion->privateDisk()->exists($p->documentVersion->file_path)
                && ($p->jenis_layanan === 'legalisir' || $p->archive->digitalAvailable()))
            ->values();

        $perPage = 12;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $pengajuans = new LengthAwarePaginator(
            $eligible->forPage($page, $perPage)->values(),
            $eligible->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('siswa.dokumen-digital.index', compact('pengajuans'));
    }

    public function show(Request $request, Pengajuan $pengajuan, string $token)
    {
        abort_unless($pengajuan->bentuk_dokumen === 'digital' && $pengajuan->status === 'approved' && ! $pengajuan->digital_access_revoked_at && $pengajuan->digital_access_hash && hash_equals($pengajuan->digital_access_hash, hash('sha256', $token)), 404);
        abort_unless($request->user()->isAdmin() || $pengajuan->user_id === $request->user()->id, 403);
        $pengajuan->load('archive.documentType', 'documentType', 'documentVersion');
        $archive = $pengajuan->archive;
        $document = $pengajuan->documentVersion;
        abort_unless($archive && $document && $document->status === 'available' && $document->privateDisk()->exists($document->file_path), 404, 'Versi dokumen digital pada pengajuan ini tidak tersedia.');
        if ($pengajuan->jenis_layanan !== 'legalisir') abort_unless($archive->digitalAvailable(), 404, 'Status arsip digital sudah dinonaktifkan.');
        ActivityLog::catat($request->user()->id, 'dokumen_digital_preview', "Membuka dokumen digital untuk tiket {$pengajuan->kode_tiket}.");
        return response()->view('siswa.dokumen-digital.show', compact('pengajuan', 'archive'))
            ->header('Cache-Control', 'private, no-store, max-age=0')
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('Referrer-Policy', 'no-referrer')
            ->header('X-Frame-Options', 'SAMEORIGIN');
    }

    public function file(Request $request, Pengajuan $pengajuan, string $token)
    {
        abort_unless($pengajuan->bentuk_dokumen === 'digital' && $pengajuan->status === 'approved' && ! $pengajuan->digital_access_revoked_at && $pengajuan->digital_access_hash && hash_equals($pengajuan->digital_access_hash, hash('sha256', $token)), 404);
        abort_unless($request->user()->isAdmin() || $pengajuan->user_id === $request->user()->id, 403);
        $pengajuan->loadMissing('archive.document');
        $document = $pengajuan->documentVersion;
        abort_unless($pengajuan->archive && $document && $document->status === 'available' && $document->privateDisk()->exists($document->file_path), 404);
        if ($pengajuan->jenis_layanan !== 'legalisir') abort_unless($pengajuan->archive->digitalAvailable(), 404);
        ActivityLog::catat($request->user()->id, 'dokumen_digital_download', "Mengunduh dokumen digital untuk tiket {$pengajuan->kode_tiket}.");
        $filename = Str::slug($document->title) . '.' . pathinfo($document->original_filename, PATHINFO_EXTENSION);
        if ($request->boolean('inline')) {
            $stream = $document->privateDisk()->readStream($document->file_path);
            abort_unless(is_resource($stream), 404);
            return response()->stream(function () use ($stream) { fpassthru($stream); if (is_resource($stream)) fclose($stream); }, 200, [
                'Content-Type' => $document->mime_type, 'Content-Disposition' => 'inline; filename="' . addslashes($filename) . '"',
                'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store, max-age=0',
                'Referrer-Policy' => 'no-referrer', 'X-Frame-Options' => 'SAMEORIGIN',
            ]);
        }
        $response = $document->privateDisk()->download($document->file_path, $filename);
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        return $response;
    }
}
