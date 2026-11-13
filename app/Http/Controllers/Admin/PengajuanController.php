<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Pengajuan;
use App\Models\PengajuanRiwayat;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use App\Notifications\PengajuanStatusUpdated;
use App\Support\Akademik;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;

class PengajuanController extends Controller
{
    public function index(Request $request)
    {
        $query = Pengajuan::with(['user', 'documentType']);

        if ($search = trim((string) $request->get('q'))) {
            $like = Akademik::likeAman($search);
            $query->where(function ($q) use ($like) {
                $q->where('kode_tiket', 'like', $like)
                  ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $like)->orWhere('nis_nip', 'like', $like));
            });
        }

        if (($status = $request->get('status')) && isset(Pengajuan::STATUS_LABEL[$status])) {
            $query->where('status', $status);
        }

        if (($jenis = $request->get('jenis_layanan')) && isset(Pengajuan::JENIS_LAYANAN[$jenis])) {
            $query->where('jenis_layanan', $jenis);
        }

        if ($dari = $request->get('dari')) {
            $query->whereDate('created_at', '>=', $dari);
        }
        if ($sampai = $request->get('sampai')) {
            $query->whereDate('created_at', '<=', $sampai);
        }
        if (($kategori = $request->get('kategori_layanan')) && in_array($kategori, ['rapor', 'tka', 'skls', 'ijazah'], true)) $query->where('kategori_layanan', $kategori);
        if (($bentuk = $request->get('bentuk_dokumen')) && in_array($bentuk, ['fisik', 'digital'], true)) $query->where('bentuk_dokumen', $bentuk);
        if ($tahun = $request->get('academic_year')) $query->where('academic_year', $tahun);
        if (($semester = $request->get('semester_akademik')) && in_array((int) $semester, [1, 2], true)) $query->where('semester_akademik', (int) $semester);
        if (($periode = $request->get('periode_rapor')) && in_array($periode, ['tengah', 'akhir'], true)) $query->where('periode_rapor', $periode);

        $pengajuans = $query->latest()->paginate(10)->withQueryString();

        return view('admin.pengajuan.index', compact('pengajuans'));
    }

    public function show(Pengajuan $pengajuan)
    {
        $pengajuan->load(['user', 'documentType', 'archive.document', 'resultDocument', 'documentVersion', 'verifier', 'riwayats.user']);

        return view('admin.pengajuan.show', compact('pengajuan'));
    }

    public function updateStatus(Request $request, Pengajuan $pengajuan): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,diproses,approved,rejected,selesai'],
            'catatan_admin' => ['nullable', 'string', 'max:1000', 'required_if:status,rejected'],
        ], [
            'catatan_admin.required_if' => 'Catatan/alasan penolakan WAJIB diisi ketika status diubah menjadi Ditolak.',
        ]);

        $hasil = DB::transaction(function () use ($pengajuan, $validated) {
            // Kunci baris agar dua admin tidak mengubah status yang sama bersamaan.
            $p = Pengajuan::whereKey($pengajuan->id)->lockForUpdate()->firstOrFail();

            if (! $p->bisaBerpindahKe($validated['status'])) {
                return [false, $p];
            }

            // Pengajuan dokumen fisik hanya boleh menjadi Selesai setelah
            // konfirmasi serah-terima (QR/kode atau form pencatatan khusus).
            if ($validated['status'] === 'selesai' && $p->bentuk_dokumen === 'fisik' && $p->archive_id) {
                return [false, $p];
            }

            if ($validated['status'] === 'approved' && $p->archive) {
                $p->loadMissing('archive.document');
                if ($p->bentuk_dokumen === 'digital') {
                    if ($p->jenis_layanan === 'legalisir') {
                        $p->loadMissing('resultDocument');
                        $snapshot = $p->resultDocument;
                        $tersedia = $snapshot && $snapshot->status === 'available' && $snapshot->privateDisk()->exists($snapshot->file_path);
                    } else {
                        $p->loadMissing('documentVersion');
                        $snapshot = $p->documentVersion;
                        $tersedia = $p->archive->digitalAvailable() && $snapshot && $snapshot->status === 'available' && $snapshot->privateDisk()->exists($snapshot->file_path);
                    }
                } else {
                    $tersedia = $p->archive->physicalAvailable();
                }
                if (! $tersedia) return [false, $p];
            }

            $dari = $p->status;
            $catatan = $validated['catatan_admin'] ?? null;
            $extra = [];
            if ($validated['status'] === 'approved' && $p->bentuk_dokumen === 'digital' && ! $p->digital_access_hash) {
                $digitalToken = Str::random(48);
                $extra = ['digital_access_hash' => hash('sha256', $digitalToken), 'digital_access_token' => Crypt::encryptString($digitalToken), 'digital_access_revoked_at' => null];
            }
            if ($validated['status'] === 'approved' && $p->bentuk_dokumen === 'fisik' && ! $p->physical_verification_hash) {
                $code = 'EKSIDU-' . Str::upper(Str::random(16));
                $extra = ['physical_verification_hash' => hash('sha256', $code), 'physical_verification_code' => Crypt::encryptString($code), 'physical_verification_expires_at' => now()->addDays(14)];
            }
            if ($validated['status'] === 'rejected') {
                $extra = array_merge($extra, [
                    'digital_access_hash' => null, 'digital_access_token' => null,
                    'digital_access_revoked_at' => now(),
                    'physical_verification_hash' => null, 'physical_verification_code' => null,
                    'physical_verification_expires_at' => null,
                ]);
            }

            $p->update(array_merge([
                'status' => $validated['status'],
                'catatan_admin' => $catatan ?? $p->catatan_admin,
                'verified_by' => auth()->id(),
                'verified_at' => now(),
            ], $extra));

            PengajuanRiwayat::catat($p, $dari, $p->status, auth()->id(), $catatan);

            return [true, $p];
        });

        [$berhasil, $p] = $hasil;

        if (! $berhasil) {
            $message = ($validated['status'] === 'selesai' && $p->bentuk_dokumen === 'fisik' && $p->archive_id)
                ? 'Pengajuan fisik hanya dapat diselesaikan melalui konfirmasi serah-terima QR/kode atau pencatatan serah-terima Admin.'
                : "Status tidak bisa diubah dari \"{$p->statusLabel()}\" langsung ke status tersebut. Urutan yang valid: Menunggu → Diproses → Siap Diambil → Selesai (atau Diproses → Ditolak).";
            return back()->withErrors(['status' => $message])->withInput();
        }

        $p->user->notify(new PengajuanStatusUpdated($p));

        ActivityLog::catat(auth()->id(), 'verifikasi_pengajuan',
            "Mengubah status pengajuan {$p->kode_tiket} menjadi {$p->statusLabel()}.");

        return back()->with('status', 'Status pengajuan berhasil diperbarui dan notifikasi dikirim ke siswa.');
    }

    public function catatPengembalian(Request $request, Pengajuan $pengajuan): RedirectResponse
    {
        $validated = $request->validate([
            'tanggal_pengembalian' => ['required', 'date'],
            'kondisi_pengembalian' => ['required', 'string', 'max:255'],
        ]);

        [$berhasil, $p] = DB::transaction(function () use ($pengajuan, $validated) {
            $p = Pengajuan::whereKey($pengajuan->id)->lockForUpdate()->firstOrFail();

            // Validasi transisi di server: hanya "Siap Diambil" yang boleh menjadi "Selesai".
            if (! $p->bisaBerpindahKe('selesai')) {
                return [false, $p];
            }

            $dari = $p->status;
            $p->update(array_merge($validated, [
                'status' => 'selesai',
                'verified_by' => auth()->id(),
                'verified_at' => now(),
                'physical_verification_hash' => null,
                'physical_verification_code' => null,
                'physical_verification_expires_at' => null,
            ]));
            PengajuanRiwayat::catat($p, $dari, 'selesai', auth()->id(), 'Dokumen diserahkan/dikembalikan. Kondisi: ' . $validated['kondisi_pengembalian']);

            return [true, $p];
        });

        if (! $berhasil) {
            return back()->withErrors([
                'status' => 'Pengembalian/penyerahan hanya bisa dicatat ketika status pengajuan adalah "Siap Diambil". '
                    . 'Status saat ini: "' . $p->statusLabel() . '".',
            ]);
        }

        $p->user->notify(new PengajuanStatusUpdated($p));

        ActivityLog::catat(auth()->id(), 'catat_pengembalian',
            "Mencatat pengembalian/penyerahan dokumen untuk {$p->kode_tiket}.");

        return back()->with('status', 'Pengembalian/penyerahan berhasil dicatat.');
    }

    public function uploadLegalizationResult(Request $request, Pengajuan $pengajuan): RedirectResponse
    {
        abort_unless($pengajuan->jenis_layanan === 'legalisir' && $pengajuan->bentuk_dokumen === 'digital', 422, 'Hasil legalisir digital hanya untuk pengajuan legalisir berbentuk Digital.');
        abort_unless(in_array($pengajuan->status, ['pending', 'diproses'], true), 422, 'Hasil legalisir hanya dapat diperbarui sebelum pengajuan disetujui.');
        $data = $request->validate(['file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:20480']]);
        $file = $request->file('file');
        $disk = config('eksidu.disk_berkas', 'berkas');
        $path = $file->store('hasil-legalisir/' . $pengajuan->user_id . '/' . now()->format('Y/m'), $disk);
        if (! $path) return back()->withErrors(['file' => 'Hasil legalisir gagal disimpan pada penyimpanan privat.'])->withInput();
        try {
            DB::transaction(function () use ($pengajuan, $file, $disk, $path) {
                $p = Pengajuan::whereKey($pengajuan->id)->lockForUpdate()->firstOrFail();
                $previousVersionId = $p->document_version_id;
                $document = Document::create([
                    'user_id' => $p->user_id, 'document_type_id' => $p->document_type_id, 'uploaded_by' => auth()->id(),
                    'title' => 'Hasil Legalisir ' . ($p->archive?->labelDetail() ?? $p->kode_tiket),
                    'academic_year' => $p->academic_year, 'semester' => $p->semester_akademik,
                    'disk' => $disk, 'file_path' => $path, 'original_filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType() ?: 'application/octet-stream', 'file_size' => $file->getSize(),
                    'checksum_sha256' => hash_file('sha256', $file->getRealPath()), 'status' => 'available',
                ]);
                $p->update(['result_document_id' => $document->id, 'document_version_id' => $document->id, 'digital_access_revoked_at' => null]);
                PengajuanRiwayat::catat($p, $p->status, $p->status, auth()->id(), 'Hasil legalisir digital diunggah sebagai versi #' . $document->id . '; versi sebelumnya #' . ($previousVersionId ?: '—') . ' tetap disimpan dan dokumen asli tidak ditimpa.');
            });
        } catch (\Throwable $e) {
            Storage::disk($disk)->delete($path);
            throw $e;
        }
        ActivityLog::catat(auth()->id(), 'hasil_legalisir_upload', "Mengunggah hasil legalisir untuk tiket {$pengajuan->kode_tiket}.");
        return back()->with('status', 'Hasil legalisir digital berhasil diunggah. Periksa file sebelum menyetujui pengajuan.');
    }

    public function reissueDigitalAccess(Request $request, Pengajuan $pengajuan): RedirectResponse
    {
        abort_unless($pengajuan->bentuk_dokumen === 'digital' && $pengajuan->status === 'approved', 422, 'Akses hanya dapat diterbitkan untuk pengajuan digital yang disetujui.');
        $pengajuan->load('documentVersion');
        abort_unless($pengajuan->documentVersion && $pengajuan->documentVersion->status === 'available' && $pengajuan->documentVersion->privateDisk()->exists($pengajuan->documentVersion->file_path), 422, 'Versi dokumen tidak tersedia.');
        if ($pengajuan->jenis_layanan !== 'legalisir') {
            abort_unless($pengajuan->archive && $pengajuan->archive->digitalAvailable(), 422, 'Arsip digital tidak lagi tersedia.');
        }
        $token = Str::random(48);
        $pengajuan->update(['digital_access_hash' => hash('sha256', $token), 'digital_access_token' => Crypt::encryptString($token), 'digital_access_revoked_at' => null]);
        $pengajuan->user?->notify(new \App\Notifications\DigitalAccessReissued($pengajuan));
        ActivityLog::catat($request->user()->id, 'terbitkan_ulang_qr_digital', "Menerbitkan ulang akses digital tiket {$pengajuan->kode_tiket}.");
        return back()->with('status', 'QR digital baru diterbitkan. QR lama otomatis tidak berlaku.');
    }

    public function rotatePhysicalCode(Request $request, Pengajuan $pengajuan): RedirectResponse
    {
        abort_unless($pengajuan->bentuk_dokumen === 'fisik' && $pengajuan->status === 'approved', 422, 'Kode hanya dapat dirotasi untuk pengajuan fisik yang disetujui.');
        $code = 'EKSIDU-' . Str::upper(Str::random(16));
        $pengajuan->update(['physical_verification_hash' => hash('sha256', $code), 'physical_verification_code' => Crypt::encryptString($code), 'physical_verification_expires_at' => now()->addDays(14)]);
        $pengajuan->user?->notify(new \App\Notifications\PhysicalCodeReissued($pengajuan));
        ActivityLog::catat($request->user()->id, 'rotasi_kode_fisik', "Merotasi kode verifikasi fisik tiket {$pengajuan->kode_tiket}.");
        return back()->with('status', 'Kode verifikasi baru diterbitkan; kode lama tidak berlaku.');
    }

    public function revokeDigitalAccess(Request $request, Pengajuan $pengajuan): RedirectResponse
    {
        abort_unless($pengajuan->bentuk_dokumen === 'digital' && $pengajuan->status === 'approved', 422, 'Hanya akses pengajuan digital yang disetujui yang dapat dicabut.');
        $pengajuan->update(['digital_access_revoked_at' => now(), 'digital_access_hash' => null, 'digital_access_token' => null]);
        $pengajuan->user?->notify(new \App\Notifications\DigitalAccessRevoked($pengajuan));
        \App\Models\ActivityLog::catat($request->user()->id, 'cabut_akses_digital', "Mencabut akses digital tiket {$pengajuan->kode_tiket}.");
        return back()->with('status', 'Akses digital berhasil dicabut. QR lama tidak lagi dapat digunakan.');
    }

    public function scanForm()
    {
        return view('admin.pengajuan.scan');
    }

    /** Cadangan bila kamera tidak tersedia: cari pengajuan lewat kode tiket. */
    public function scanCari(Request $request): RedirectResponse
    {
        $request->validate(['kode_tiket' => ['required', 'string', 'max:40']]);

        $input = Str::upper(trim($request->kode_tiket));
        $byCode = Pengajuan::where('physical_verification_hash', hash('sha256', $input))->first();
        if ($byCode && $byCode->physical_verification_expires_at && $byCode->physical_verification_expires_at->isFuture() && $byCode->status === 'approved' && $byCode->bentuk_dokumen === 'fisik') {
            return redirect(\App\Support\QrUrl::temporarySignedRoute('pengajuan.qr.show', now()->addMinutes(10), ['pengajuan' => $byCode->id, 'v' => substr($byCode->physical_verification_hash, 0, 16)]));
        }
        $pengajuan = Pengajuan::where('kode_tiket', $input)->first();

        if (! $pengajuan) {
            return back()->withErrors(['kode_tiket' => 'Kode tiket tidak ditemukan.'])->withInput();
        }

        return redirect()->route('admin.pengajuan.show', $pengajuan);
    }

    /**
     * Tujuan tautan QR (route bertanda tangan). Hanya MENAMPILKAN data dan tombol
     * konfirmasi; perubahan data dilakukan lewat POST (qrKonfirmasi), bukan GET.
     */
    public function qrVerify(Request $request, Pengajuan $pengajuan)
    {
        $pengajuan->load(['user', 'documentType', 'archive']);
        abort_unless($pengajuan->bentuk_dokumen === 'fisik' && $pengajuan->status === 'approved' && $pengajuan->physical_verification_hash && $pengajuan->physical_verification_expires_at && $pengajuan->physical_verification_expires_at->isFuture(), 404);
        abort_unless(hash_equals(substr($pengajuan->physical_verification_hash, 0, 16), (string) $request->query('v')), 404);

        // QR fisik hanya dapat diverifikasi petugas yang sudah login sebagai Admin/TU.
        if (! $request->user()?->isAdmin() || ! $request->user()?->aktif) {
            return redirect()->guest(route('login'));
        }

        return view('admin.pengajuan.qr-result', ['pengajuan' => $pengajuan]);
    }

    public function qrKonfirmasi(Request $request, Pengajuan $pengajuan): RedirectResponse
    {
        [$berhasil, $p] = DB::transaction(function () use ($pengajuan) {
            $p = Pengajuan::whereKey($pengajuan->id)->lockForUpdate()->firstOrFail();

            if ($p->status !== 'approved' || $p->bentuk_dokumen !== 'fisik' || ! $p->physical_verification_hash || ! $p->physical_verification_expires_at || $p->physical_verification_expires_at->isPast()) {
                return [false, $p];
            }

            $p->update([
                'status' => 'selesai',
                'tanggal_pengembalian' => now()->toDateString(),
                'kondisi_pengembalian' => 'Diambil langsung oleh siswa (konfirmasi scan QR)',
                'verified_by' => auth()->id(),
                'verified_at' => now(),
                'physical_verification_hash' => null,
                'physical_verification_code' => null,
                'physical_verification_expires_at' => null,
            ]);
            PengajuanRiwayat::catat($p, 'approved', 'selesai', auth()->id(), 'Pengambilan dikonfirmasi lewat scan QR.');

            return [true, $p];
        });

        if (! $berhasil) {
            return redirect()->route('admin.scan-qr')->with('error',
                $p->status === 'selesai'
                    ? "Dokumen {$p->kode_tiket} sudah pernah dikonfirmasi diambil sebelumnya."
                    : "Pengajuan {$p->kode_tiket} belum berstatus \"Siap Diambil\", tidak bisa dikonfirmasi."
            );
        }

        $p->user->notify(new PengajuanStatusUpdated($p));

        ActivityLog::catat(auth()->id(), 'konfirmasi_qr',
            "Mengonfirmasi pengambilan dokumen {$p->kode_tiket} via scan QR.");

        return redirect()->route('admin.scan-qr')
            ->with('status', "Pengambilan dokumen {$p->kode_tiket} ({$p->user->name}) berhasil dikonfirmasi.");
    }
}
