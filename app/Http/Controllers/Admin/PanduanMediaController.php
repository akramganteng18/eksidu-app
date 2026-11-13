<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\PanduanMedia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PanduanMediaController extends Controller
{
    /** Ekstensi yang diizinkan => MIME yang dikirim saat streaming. */
    public const MIME = [
        'video' => ['mp4' => 'video/mp4', 'webm' => 'video/webm'],
        'audio' => ['mp3' => 'audio/mpeg', 'wav' => 'audio/wav', 'ogg' => 'audio/ogg', 'm4a' => 'audio/mp4'],
    ];

    public function index()
    {
        $daftar = PanduanMedia::with('pembuat')->orderBy('urutan')->latest('id')->paginate(10);

        return view('admin.panduan.index', compact('daftar'));
    }

    public function create()
    {
        return view('admin.panduan.form', ['panduan' => new PanduanMedia(['jenis' => 'video', 'sumber' => 'unggah', 'untuk' => 'semua', 'aktif' => true, 'urutan' => 0])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validasi($request, null);

        if ($data['sumber'] === 'unggah') {
            [$data['file_path'], $data['mime']] = $this->simpanBerkas($request->file('berkas'), $data['jenis']);
            $data['url'] = null;
        } else {
            $data['jenis'] = 'video';
            $data['file_path'] = null;
            $data['mime'] = null;
        }

        $panduan = PanduanMedia::create($data + ['dibuat_oleh' => auth()->id()]);

        ActivityLog::catat(auth()->id(), 'tambah_panduan', "Menambahkan materi panduan \"{$panduan->judul}\".");

        return redirect()->route('admin.panduan.index')->with('status', 'Materi panduan berhasil ditambahkan.');
    }

    public function edit(PanduanMedia $panduan)
    {
        return view('admin.panduan.form', compact('panduan'));
    }

    public function update(Request $request, PanduanMedia $panduan): RedirectResponse
    {
        $data = $this->validasi($request, $panduan);
        $disk = Storage::disk(config('eksidu.disk_berkas'));

        if ($data['sumber'] === 'unggah') {
            if ($request->hasFile('berkas')) {
                [$data['file_path'], $data['mime']] = $this->simpanBerkas($request->file('berkas'), $data['jenis']);
                if ($panduan->file_path) {
                    $disk->delete($panduan->file_path);
                }
            }
            $data['url'] = null;
        } else {
            $data['jenis'] = 'video';
            if ($panduan->file_path) {
                $disk->delete($panduan->file_path);
            }
            $data['file_path'] = null;
            $data['mime'] = null;
        }

        $panduan->update($data);

        ActivityLog::catat(auth()->id(), 'ubah_panduan', "Memperbarui materi panduan \"{$panduan->judul}\".");

        return redirect()->route('admin.panduan.index')->with('status', 'Materi panduan berhasil diperbarui.');
    }

    public function destroy(PanduanMedia $panduan): RedirectResponse
    {
        if ($panduan->file_path) {
            Storage::disk(config('eksidu.disk_berkas'))->delete($panduan->file_path);
        }

        $judul = $panduan->judul;
        $panduan->delete();

        ActivityLog::catat(auth()->id(), 'hapus_panduan', "Menghapus materi panduan \"{$judul}\".");

        return back()->with('status', 'Materi panduan berhasil dihapus.');
    }

    private function validasi(Request $request, ?PanduanMedia $existing): array
    {
        $sumber = $request->input('sumber');
        $jenis = $request->input('jenis');
        $perluBerkas = $sumber === 'unggah' && ! ($existing && $existing->file_path);

        $aturan = [
            'judul' => ['required', 'string', 'max:150'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'jenis' => ['required', 'in:video,audio'],
            'sumber' => ['required', 'in:unggah,tautan'],
            'untuk' => ['required', 'in:semua,siswa,admin'],
            'urutan' => ['nullable', 'integer', 'min:0', 'max:999'],
            'aktif' => ['sometimes', 'boolean'],
        ];

        if ($sumber === 'tautan') {
            $aturan['url'] = ['required', 'string', 'max:255', function ($atribut, $nilai, $gagal) {
                if (! PanduanMedia::ekstrakIdYoutube($nilai)) {
                    $gagal('Tautan harus berupa URL video YouTube yang valid (youtube.com atau youtu.be).');
                }
            }];
        } else {
            $ekstensi = implode(',', array_keys(self::MIME[$jenis] ?? self::MIME['video']));
            $aturan['berkas'] = [
                $perluBerkas ? 'required' : 'nullable', 'file',
                'mimes:' . $ekstensi,
                'max:' . config('eksidu.batas_media_kb'),
            ];
        }

        $data = $request->validate($aturan, [
            'berkas.required' => 'Berkas media wajib diunggah.',
            'berkas.mimes' => 'Format berkas tidak sesuai. Video: MP4/WebM. Audio: MP3/WAV/OGG/M4A.',
            'berkas.max' => 'Ukuran berkas melebihi batas yang diizinkan.',
            'berkas.uploaded' => 'Unggahan gagal. Periksa batas upload_max_filesize dan post_max_size di php.ini.',
        ]);

        $data['aktif'] = $request->boolean('aktif');
        $data['urutan'] = (int) ($data['urutan'] ?? 0);
        unset($data['berkas']);

        return $data;
    }

    /** @return array{0:string,1:string} [path, mime] */
    private function simpanBerkas(UploadedFile $berkas, string $jenis): array
    {
        $ekstensi = strtolower($berkas->getClientOriginalExtension());
        $mime = self::MIME[$jenis][$ekstensi] ?? 'application/octet-stream';
        $path = $berkas->store('panduan', config('eksidu.disk_berkas'));

        return [$path, $mime];
    }
}
