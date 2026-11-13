<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Pengajuan;
use App\Models\PengajuanRiwayat;
use App\Models\StudentDocumentArchive;
use App\Notifications\PengajuanStatusUpdated;
use App\Support\QrUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class PengajuanController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->user()->pengajuans()->with('documentType');

        $status = $request->get('status');
        if ($status && isset(Pengajuan::STATUS_LABEL[$status])) {
            $query->where('status', $status);
        }

        $pengajuans = $query->latest()->paginate(8)->withQueryString();

        return view('siswa.pengajuan.index', compact('pengajuans'));
    }

    public function create()
    {
        $archives = auth()->user()->documentArchives()->with(['documentType', 'document'])->get()
            ->filter(fn (StudentDocumentArchive $archive) => $archive->digitalAvailable() || $archive->physicalAvailable())
            ->map(fn (StudentDocumentArchive $archive) => [
                'id' => $archive->id, 'category' => $archive->category,
                'category_label' => StudentDocumentArchive::CATEGORIES[$archive->category],
                'detail' => $archive->labelDetail(), 'digital' => $archive->digitalAvailable(),
                'physical' => $archive->physicalAvailable(), 'academic_year' => $archive->academic_year,
                'semester' => $archive->semester, 'period' => $archive->report_period,
            ])->values();

        return view('siswa.pengajuan.create', compact('archives'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kategori_layanan' => ['required', 'in:rapor,tka,skls,ijazah,legalisir'],
            'kategori_dokumen' => ['required_if:kategori_layanan,legalisir', 'nullable', 'in:rapor,tka,skls,ijazah'],
            'bentuk_dokumen' => ['required', 'in:fisik,digital'],
            'archive_id' => ['required', 'integer', 'exists:student_document_archives,id'],
            'semester_akademik' => ['nullable', 'integer', 'between:1,2'],
            'periode_rapor' => ['nullable', 'in:tengah,akhir'],
            'keperluan' => ['required', 'string', 'max:1000'],
            'jumlah_lembar' => ['required_if:kategori_layanan,legalisir', 'nullable', 'integer', 'min:1', 'max:20'],
            'file_pendukung' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:' . config('eksidu.batas_berkas_kb', 2048)],
        ], [
            'archive_id.required' => 'Pilih arsip dokumen yang tersedia.',
            'kategori_dokumen.required_if' => 'Pilih kategori dokumen yang akan dilegalisir.',
            'jumlah_lembar.required_if' => 'Jumlah lembar wajib diisi untuk legalisir.',
        ]);

        $user = $request->user();
        $path = null;
        $disk = config('eksidu.disk_berkas');
        if ($request->hasFile('file_pendukung')) $path = $request->file('file_pendukung')->store('berkas-pengajuan', $disk);

        try {
            $pengajuan = DB::transaction(function () use ($data, $user, $path) {
                // Lock arsip selama validasi dan insert untuk menutup race-condition pengajuan ganda.
                $archive = $user->documentArchives()->with('document')->lockForUpdate()->findOrFail($data['archive_id']);
                $archiveCategory = $data['kategori_layanan'] === 'legalisir' ? ($data['kategori_dokumen'] ?? null) : $data['kategori_layanan'];
                if ($archive->category !== $archiveCategory) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['archive_id' => 'Kategori arsip tidak sesuai dengan pilihan layanan.']);
                }
                $available = $data['bentuk_dokumen'] === 'digital' ? $archive->digitalAvailable() : $archive->physicalAvailable();
                if (! $available) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['bentuk_dokumen' => 'Bentuk dokumen tersebut sedang tidak tersedia pada arsip Anda.']);
                }
                if ($archive->category === 'rapor') {
                    if (! in_array((int) ($data['semester_akademik'] ?? 0), [1, 2], true) || ! in_array($data['periode_rapor'] ?? null, ['tengah', 'akhir'], true)) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['semester_akademik' => 'Pilih semester dan periode rapor.']);
                    }
                    if ((int) $data['semester_akademik'] !== (int) $archive->semester || $data['periode_rapor'] !== $archive->report_period) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['archive_id' => 'Kombinasi semester/periode tidak sesuai dengan arsip yang tersedia.']);
                    }
                }
                $jenisLayanan = $data['kategori_layanan'] === 'legalisir' ? 'legalisir' : ($archive->category === 'rapor' ? 'peminjaman_rapor' : 'pengambilan_ijazah');
                $dupe = $user->pengajuans()->where('archive_id', $archive->id)->where('kategori_layanan', $archive->category)
                    ->where('jenis_layanan', $jenisLayanan)->where('bentuk_dokumen', $data['bentuk_dokumen'])
                    ->whereIn('status', ['pending', 'diproses', 'approved'])->exists();
                if ($dupe) throw \Illuminate\Validation\ValidationException::withMessages(['archive_id' => 'Masih ada pengajuan aktif untuk dokumen dan bentuk layanan yang sama.']);

                $p = Pengajuan::create([
                    'user_id' => $user->id, 'archive_id' => $archive->id, 'document_version_id' => $archive->document_id, 'document_type_id' => $archive->document_type_id,
                    'jenis_layanan' => $jenisLayanan, 'kategori_layanan' => $archive->category,
                    'bentuk_dokumen' => $data['bentuk_dokumen'], 'academic_year' => $archive->academic_year,
                    'semester_akademik' => $archive->semester, 'periode_rapor' => $archive->report_period,
                    'keperluan' => $data['keperluan'], 'jumlah_lembar' => $jenisLayanan === 'legalisir' ? ($data['jumlah_lembar'] ?? null) : null,
                    'file_pendukung' => $path, 'status' => 'pending',
                ]);
                PengajuanRiwayat::catat($p, null, 'pending', $user->id, 'Pengajuan layanan dokumen dibuat.');
                return $p;
            });
        } catch (\Throwable $e) {
            if ($path) Storage::disk($disk)->delete($path);
            throw $e;
        }

        $user->notify(new PengajuanStatusUpdated($pengajuan));
        ActivityLog::catat($user->id, 'ajukan_layanan', "Mengajukan {$pengajuan->labelJenisLayanan()} (Tiket: {$pengajuan->kode_tiket}).");
        return redirect()->route('siswa.pengajuan.show', $pengajuan)->with('status', 'Pengajuan berhasil dikirim. Pantau statusnya di halaman ini.');
    }

    public function show(Request $request, Pengajuan $pengajuan)
    {
        $this->authorize('view', $pengajuan);
        $pengajuan->load('documentType', 'archive.document', 'documentVersion', 'resultDocument', 'verifier', 'riwayats.user');
        $qrUrl = null;
        $kodeVerifikasi = null;
        if ($pengajuan->status === 'approved' && $pengajuan->bentuk_dokumen === 'digital' && $pengajuan->archive && $pengajuan->digital_access_revoked_at === null) {
            $token = $pengajuan->digital_access_token ? Crypt::decryptString($pengajuan->digital_access_token) : null;
            $snapshot = $pengajuan->documentVersion;
            $fileAvailable = $snapshot && $snapshot->status === 'available' && $snapshot->privateDisk()->exists($snapshot->file_path);
            if ($pengajuan->jenis_layanan !== 'legalisir') $fileAvailable = $fileAvailable && $pengajuan->archive->digitalAvailable();
            if ($token && $pengajuan->digital_access_hash && $fileAvailable) $qrUrl = QrUrl::temporarySignedRoute('siswa.dokumen.digital', now()->addDays(30), ['pengajuan' => $pengajuan->id, 'token' => $token]);
        } elseif ($pengajuan->status === 'approved' && $pengajuan->bentuk_dokumen === 'fisik' && $pengajuan->physical_verification_hash && $pengajuan->physical_verification_expires_at && $pengajuan->physical_verification_expires_at->isFuture()) {
            $qrUrl = QrUrl::temporarySignedRoute('pengajuan.qr.show', now()->addDays(7), ['pengajuan' => $pengajuan->id, 'v' => substr($pengajuan->physical_verification_hash, 0, 16)]);
            $kodeVerifikasi = $pengajuan->physical_verification_code ? Crypt::decryptString($pengajuan->physical_verification_code) : null;
        }
        return view('siswa.pengajuan.show', compact('pengajuan', 'qrUrl', 'kodeVerifikasi'));
    }

    public function lacakForm()
    {
        return view('siswa.pengajuan.lacak');
    }

    public function lacakCari(Request $request): RedirectResponse
    {
        $request->validate([
            'kode_tiket' => ['required', 'string', 'max:40'],
        ]);

        // Kode tiket disimpan huruf kapital; tiket lama berawalan SIMADA tetap bisa dilacak.
        $kode = Str::upper(trim($request->kode_tiket));

        $pengajuan = $request->user()->pengajuans()
            ->where('kode_tiket', $kode)
            ->first();

        if (! $pengajuan) {
            return back()->withErrors([
                'kode_tiket' => 'Kode tiket tidak ditemukan pada akun Anda. Periksa kembali penulisannya.',
            ])->withInput();
        }

        return redirect()->route('siswa.pengajuan.show', $pengajuan);
    }
}
