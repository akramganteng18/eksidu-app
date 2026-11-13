<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Legacy repository endpoints: retained for historical file access, not new uploads/edits. */
class DocumentRepositoryController extends Controller
{
    public function index(Request $request)
    {
        return redirect()->route('admin.arsip-siswa.index')->with('status', 'Pengelolaan dokumen dipindahkan ke Arsip Dokumen Siswa.');
    }

    public function store(Request $request)
    {
        return redirect()->route('admin.arsip-siswa.index')->with('error', 'Upload dokumen baru harus dilakukan melalui Arsip Dokumen Siswa.');
    }

    public function edit(Document $document)
    {
        return redirect()->route('admin.arsip-siswa.index')->with('status', 'Metadata arsip dikelola melalui Arsip Dokumen Siswa.');
    }

    public function update(Request $request, Document $document)
    {
        return redirect()->route('admin.arsip-siswa.index')->with('error', 'Perubahan dokumen baru harus dilakukan melalui Arsip Dokumen Siswa.');
    }

    public function download(Request $request, Document $document)
    {
        abort_unless($document->privateDisk()->exists($document->file_path), 404);
        ActivityLog::catat($request->user()->id, 'dokumen_repository_download', "Mengunduh dokumen repository historis #{$document->id}.");
        return $document->privateDisk()->download($document->file_path, Str::slug($document->title) . '.' . pathinfo($document->original_filename, PATHINFO_EXTENSION));
    }

    public function toggleArchive(Request $request, Document $document)
    {
        return redirect()->route('admin.arsip-siswa.index')->with('error', 'Status ketersediaan dikelola melalui Arsip Dokumen Siswa.');
    }

    public function destroy(Request $request, Document $document)
    {
        return redirect()->route('admin.arsip-siswa.index')->with('error', 'Penghapusan melalui Repository lama dinonaktifkan untuk melindungi histori.');
    }
}
