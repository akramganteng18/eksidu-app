<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\DocumentLoan;
use App\Support\QrUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class DocumentLoanController extends Controller
{
    public function index(Request $request)
    {
        return redirect()->route('admin.pengajuan.index')->with('status', 'Pengajuan digital baru kini diproses melalui Pengajuan Masuk.');

        $loans = DocumentLoan::with(['document:id,title', 'borrower:id,name,nis_nip', 'approver:id,name'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()->paginate(20)->withQueryString();
        return view('admin.repository.loans', compact('loans'));
    }

    public function update(Request $request, DocumentLoan $loan)
    {
        $data = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        [$updated, $isRenewal, $loan] = DB::transaction(function () use ($request, $data, $loan) {
            $lockedLoan = DocumentLoan::whereKey($loan->id)->lockForUpdate()->firstOrFail();
            // Admin boleh menerbitkan ulang QR yang masih aktif juga, misalnya
            // setelah APP_URL/EKSIDU_PUBLIC_URL berubah saat aplikasi dipindah host.
            // Token lama otomatis tidak berlaku karena hash diganti.
            $renewal = $lockedLoan->status === 'approved'
                && $data['status'] === 'approved';
            if ($lockedLoan->status !== 'pending' && ! $renewal) return [false, false, $lockedLoan];

            $updates = [
                'status' => $data['status'],
                'approved_by' => $request->user()->id,
                'admin_note' => $data['admin_note'] ?? null,
            ];

            if ($data['status'] === 'approved') {
                $plainToken = Str::random(64);
                $expiresAt = now()->addDays(7);
                $updates['token_hash'] = hash('sha256', $plainToken);
                $updates['expires_at'] = $expiresAt;
                $updates['revoked_at'] = null;
                $updates['accessed_at'] = null;
                $verifyUrl = QrUrl::temporarySignedRoute('repository.qr.access', $expiresAt, [
                    'loan' => $lockedLoan->id,
                    'token' => $plainToken,
                ]);
                $updates['qr_svg'] = (string) QrCode::format('svg')->size(220)->margin(1)->generate($verifyUrl);
            } else {
                $updates['token_hash'] = null;
                $updates['qr_svg'] = null;
                $updates['expires_at'] = null;
                $updates['revoked_at'] = now();
            }

            $lockedLoan->update($updates);
            return [true, $renewal, $lockedLoan];
        });

        if (! $updated) return back()->withErrors(['status' => 'Permohonan ini sudah diproses oleh petugas lain. Muat ulang halaman.']);

        ActivityLog::catat($request->user()->id, $isRenewal ? 'repository_loan_qr_renewed' : 'repository_loan_' . $data['status'], "Memproses permohonan peminjaman #{$loan->id}.");
        return back()->with('success', $isRenewal ? 'QR peminjaman berhasil diterbitkan ulang.' : 'Permohonan peminjaman berhasil diperbarui.');
    }

    public function access(Request $request, DocumentLoan $loan, string $token)
    {
        abort_unless(filled($loan->token_hash) && hash_equals($loan->token_hash, hash('sha256', $token)), 404);
        abort_unless($loan->tokenAktif() && $loan->document?->status === 'available', 410, 'QR sudah kedaluwarsa atau dicabut.');

        // QR tetap memerlukan sesi login. Peminjam hanya dapat membuka QR miliknya,
        // sedangkan Admin/TU dapat memverifikasi transaksi sesuai kewenangannya.
        if (! $request->user()->isAdmin()) {
            abort_unless((int) $loan->user_id === (int) $request->user()->id, 404);
        }

        $loan->load(['document.documentType', 'borrower']);
        $loan->forceFill(['accessed_at' => now()])->save();
        ActivityLog::catat($request->user()->id, 'repository_loan_qr_access', "Mengakses halaman QR peminjaman #{$loan->id}.");

        if ($request->user()->isAdmin()) {
            $previewUrl = route('repository.preview', $loan);
            return view('admin.repository.verify', compact('loan', 'previewUrl'));
        }

        $previewUrl = route('repository.preview', $loan);
        return view('siswa.repository.access', compact('loan', 'previewUrl'));
    }

    /** Pratinjau berkas privat melalui sesi peminjam/Admin dan pinjaman aktif. */
    public function preview(Request $request, DocumentLoan $loan)
    {
        abort_unless($request->user()->isAdmin() || (int) $loan->user_id === (int) $request->user()->id, 404);
        abort_unless($loan->status === 'approved' && $loan->tokenAktif(), 403, 'Izin akses dokumen sudah tidak berlaku.');

        $document = $loan->document;
        abort_unless($document && $document->status === 'available', 404);

        $disk = $document->privateDisk();
        abort_unless($disk->exists($document->file_path), 404);

        $mime = strtolower((string) ($document->mime_type ?: $disk->mimeType($document->file_path)));
        abort_unless(in_array($mime, ['application/pdf', 'image/jpeg', 'image/png'], true), 415, 'Format dokumen tidak dapat dipratinjau.');

        $stream = $disk->readStream($document->file_path);
        abort_unless(is_resource($stream), 404);

        $extension = strtolower(pathinfo((string) $document->original_filename, PATHINFO_EXTENSION));
        $filename = Str::slug(pathinfo((string) $document->original_filename, PATHINFO_FILENAME)) ?: 'dokumen';
        if ($extension !== '') {
            $filename .= '.' . $extension;
        }
        $headers = [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . ($filename ?: 'dokumen') . '"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
        ];
        if ($document->file_size) {
            $headers['Content-Length'] = (string) $document->file_size;
        }

        $loan->forceFill(['accessed_at' => now()])->save();
        ActivityLog::catat($request->user()->id, 'repository_loan_preview', "Melihat isi dokumen melalui peminjaman #{$loan->id}.");

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, $headers);
    }

    public function verify(Request $request, DocumentLoan $loan, string $token)
    {
        abort_unless(hash_equals((string) $loan->token_hash, hash('sha256', $token)), 404);
        abort_unless($loan->tokenAktif() && $loan->document?->status === 'available', 410, 'QR sudah kedaluwarsa atau dicabut.');
        $loan->load(['document.documentType', 'borrower']);
        $previewUrl = route('repository.preview', $loan);
        $loan->forceFill(['accessed_at' => now()])->save();
        ActivityLog::catat($request->user()->id, 'repository_loan_qr_verify', "Memverifikasi QR peminjaman #{$loan->id}.");
        return view('admin.repository.verify', compact('loan', 'previewUrl'));
    }

    public function complete(Request $request, DocumentLoan $loan)
    {
        $data = $request->validate(['token' => ['required', 'string', 'min:32', 'max:128']]);
        $completed = DB::transaction(function () use ($request, $data, $loan) {
            $lockedLoan = DocumentLoan::whereKey($loan->id)->lockForUpdate()->firstOrFail();
            abort_unless(hash_equals((string) $lockedLoan->token_hash, hash('sha256', $data['token'])), 404);
            abort_unless($lockedLoan->tokenAktif() && $lockedLoan->document?->status === 'available', 410, 'QR sudah kedaluwarsa atau dicabut.');
            $lockedLoan->update(['status' => 'completed', 'revoked_at' => now(), 'accessed_at' => now()]);
            return $lockedLoan;
        });

        ActivityLog::catat($request->user()->id, 'repository_loan_completed', "Mengonfirmasi peminjaman dokumen #{$completed->id}.");
        return redirect()->route('admin.repository.loans')->with('success', 'QR valid. Transaksi peminjaman telah diselesaikan.');
    }

    public function revoke(Request $request, DocumentLoan $loan)
    {
        $revoked = DB::transaction(function () use ($loan) {
            $lockedLoan = DocumentLoan::whereKey($loan->id)->lockForUpdate()->firstOrFail();
            if ($lockedLoan->status !== 'approved') return false;
            $lockedLoan->update(['revoked_at' => now(), 'token_hash' => null, 'qr_svg' => null]);
            return true;
        });
        abort_unless($revoked, 422, 'Hanya token yang telah disetujui yang dapat dicabut.');

        ActivityLog::catat($request->user()->id, 'repository_loan_revoked', "Mencabut token peminjaman dokumen #{$loan->id}.");
        return back()->with('success', 'Token QR berhasil dicabut.');
    }

}
