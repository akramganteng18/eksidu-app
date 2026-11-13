<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Pengumuman;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PengumumanController extends Controller
{
    public function index()
    {
        $pengumumans = Pengumuman::with('pembuat:id,name')->latest()->paginate(15);

        return view('admin.pengumuman.index', compact('pengumumans'));
    }

    public function create()
    {
        return view('admin.pengumuman.form', ['pengumuman' => new Pengumuman()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['dibuat_oleh'] = $request->user()->id;
        $pengumuman = Pengumuman::create($data);

        ActivityLog::catat($request->user()->id, 'pengumuman_dibuat', "Membuat pengumuman: {$pengumuman->judul}.");

        return redirect()->route('admin.pengumuman.index')->with('status', 'Pengumuman berhasil dibuat.');
    }

    public function edit(Pengumuman $pengumuman)
    {
        return view('admin.pengumuman.form', compact('pengumuman'));
    }

    public function update(Request $request, Pengumuman $pengumuman): RedirectResponse
    {
        $data = $this->validated($request);
        $pengumuman->update($data);

        ActivityLog::catat($request->user()->id, 'pengumuman_diubah', "Memperbarui pengumuman: {$pengumuman->judul}.");

        return redirect()->route('admin.pengumuman.index')->with('status', 'Pengumuman berhasil diperbarui.');
    }

    public function destroy(Request $request, Pengumuman $pengumuman): RedirectResponse
    {
        $judul = $pengumuman->judul;
        $pengumuman->delete();
        ActivityLog::catat($request->user()->id, 'pengumuman_dihapus', "Menghapus pengumuman: {$judul}.");

        return back()->with('status', 'Pengumuman berhasil dihapus.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'judul' => ['required', 'string', 'max:180'],
            'isi' => ['required', 'string', 'min:5', 'max:20000'],
            'sasaran' => ['required', 'in:semua,siswa,admin'],
            'diterbitkan' => ['sometimes', 'boolean'],
            'mulai_pada' => ['nullable', 'date'],
            'berakhir_pada' => ['nullable', 'date', 'after_or_equal:mulai_pada'],
        ]);

        $data['diterbitkan'] = $request->boolean('diterbitkan');
        $data['mulai_pada'] = $data['mulai_pada'] ?? null;
        $data['berakhir_pada'] = $data['berakhir_pada'] ?? null;

        return $data;
    }
}
