<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\DocumentLoan;
use Illuminate\Http\Request;

/** Legacy repository access: new requests use Ajukan Layanan; approved historical loans remain downloadable. */
class DocumentRepositoryController extends Controller
{
    public function index(Request $request)
    {
        return redirect()->route('siswa.dokumen-saya.index')->with('status', 'Repository lama telah dipindahkan ke menu Dokumen Saya.');
    }

    public function requestLoan(Request $request, Document $document)
    {
        return redirect()->route('siswa.pengajuan.create')->with('error', 'Pengajuan dokumen baru hanya melalui menu Ajukan Layanan.');
    }

    public function download(Request $request, DocumentLoan $loan)
    {
        abort_unless($loan->user_id === $request->user()->id || $request->user()->isAdmin(), 404);
        abort_unless($loan->status === 'approved' && $loan->tokenAktif(), 403, 'Izin akses dokumen sudah tidak berlaku.');
        $document = $loan->document;
        abort_unless($document && $document->status === 'available' && $document->privateDisk()->exists($document->file_path), 404);
        $loan->forceFill(['accessed_at' => now()])->save();
        ActivityLog::catat($request->user()->id, 'repository_loan_download', "Mengunduh dokumen repository historis melalui peminjaman #{$loan->id}.");
        return $document->privateDisk()->download($document->file_path, $document->original_filename);
    }
}
