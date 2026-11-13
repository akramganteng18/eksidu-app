<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\DocumentType;
use App\Models\Pengajuan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DocumentTypeController extends Controller
{
    public function index()
    {
        $dokumen = DocumentType::withCount(['pengajuans', 'documents'])->orderBy('nama')->paginate(10);
        $layananOptions = Pengajuan::JENIS_SINGKAT;

        return view('admin.dokumen.index', compact('dokumen', 'layananOptions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['kode' => strtoupper(trim((string) $request->input('kode')))]);

        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:20', 'unique:document_types,kode'],
            'nama' => ['required', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'layanan' => ['sometimes', 'array'],
            'layanan.*' => ['required', 'string', Rule::in(array_keys(Pengajuan::JENIS_LAYANAN))],
        ]);

        $validated['layanan'] = $request->input('layanan', []);
        DocumentType::create($validated + ['aktif' => true]);
        ActivityLog::catat(auth()->id(), 'tambah_dokumen', "Menambahkan jenis dokumen {$validated['nama']}.");

        return back()->with('status', 'Jenis dokumen berhasil ditambahkan.');
    }

    public function update(Request $request, DocumentType $dokumen): RedirectResponse
    {
        $request->merge(['kode' => strtoupper(trim((string) $request->input('kode')))]);

        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:20', Rule::unique('document_types', 'kode')->ignore($dokumen->id)],
            'nama' => ['required', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'aktif' => ['sometimes', 'boolean'],
            'layanan' => ['sometimes', 'array'],
            'layanan.*' => ['required', 'string', Rule::in(array_keys(Pengajuan::JENIS_LAYANAN))],
        ]);
        $validated['aktif'] = $request->boolean('aktif');
        $validated['layanan'] = $request->input('layanan', []);

        $dokumen->update($validated);
        ActivityLog::catat(auth()->id(), 'ubah_dokumen', "Memperbarui jenis dokumen {$dokumen->nama}.");

        return back()->with('status', 'Jenis dokumen berhasil diperbarui.');
    }

    public function destroy(DocumentType $dokumen): RedirectResponse
    {
        // FK pengajuans.document_type_id bersifat NULL ON DELETE: menghapus jenis yang sudah dipakai
        // akan mengosongkan jenis dokumen pada riwayat pengajuan. Dicegah demi integritas data.
        $dipakai = $dokumen->pengajuans()->count();
        $dipakaiRepository = $dokumen->documents()->count();
        if (($dipakai + $dipakaiRepository) > 0) {
            return back()->with('error',
                "Jenis dokumen \"{$dokumen->nama}\" sudah dipakai {$dipakai} pengajuan dan {$dipakaiRepository} arsip repository sehingga tidak dapat dihapus. "
                . 'Nonaktifkan saja agar tidak muncul di formulir pengajuan baru.');
        }

        $nama = $dokumen->nama;
        $dokumen->delete();
        ActivityLog::catat(auth()->id(), 'hapus_dokumen', "Menghapus jenis dokumen {$nama}.");

        return back()->with('status', 'Jenis dokumen berhasil dihapus.');
    }
}
