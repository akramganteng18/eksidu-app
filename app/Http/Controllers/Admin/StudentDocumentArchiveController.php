<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\StudentDocumentArchive;
use App\Models\StudentDocumentArchiveHistory;
use App\Models\StudentArchiveRequirement;
use App\Models\StudentArchiveRequirementExemption;
use App\Models\User;
use App\Notifications\DigitalAccessRevoked;
use App\Models\Pengajuan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class StudentDocumentArchiveController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->where('role', 'siswa')->withCount('documentArchives');
        if ($request->filled('q')) {
            $term = trim($request->q);
            $query->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('nis_nip', 'like', "%{$term}%"));
        }
        if ($request->filled('kelas')) $query->where('kelas', $request->kelas);
        $students = $query->orderBy('name')->paginate(15)->withQueryString();
        $requirements = StudentArchiveRequirement::where('active', true)->where('required', true)->get();
        $students->getCollection()->transform(function ($student) use ($requirements) {
            $rules = $requirements->filter(fn ($rule) => blank($rule->class_name) || $rule->class_name === $student->kelas);
            $exemptIds = StudentArchiveRequirementExemption::where('user_id', $student->id)->whereIn('requirement_id', $rules->pluck('id'))->pluck('requirement_id')->map(fn ($id) => (int) $id)->all();
            $student->archive_requirement_exempt = count($exemptIds);
            $rules = $rules->reject(fn ($rule) => in_array($rule->id, $exemptIds, true));
            $available = 0; $missing = 0;
            foreach ($rules as $rule) {
                $archive = $student->documentArchives()->where('category', $rule->category)
                    ->when($rule->academic_year, fn ($q) => $q->where('academic_year', $rule->academic_year))
                    ->when($rule->semester, fn ($q) => $q->where('semester', $rule->semester))
                    ->when($rule->report_period, fn ($q) => $q->where('report_period', $rule->report_period))->first();
                if ($archive && ($archive->physicalAvailable() || $archive->digitalAvailable())) $available++; else $missing++;
            }
            $student->archive_requirement_total = $rules->count();
            $student->archive_requirement_available = $available;
            $student->archive_requirement_missing = $missing;
            return $student;
        });
        $classes = User::where('role', 'siswa')->whereNotNull('kelas')->distinct()->orderBy('kelas')->pluck('kelas');
        return view('admin.arsip-siswa.index', ['students' => $students, 'classes' => $classes, 'requirements' => StudentArchiveRequirement::where('active', true)->orderBy('class_name')->orderBy('category')->get()]);
    }

    public function show(User $siswa)
    {
        abort_unless($siswa->isSiswa(), 404);
        $archives = $siswa->documentArchives()->with(['documentType', 'document'])->orderBy('category')->orderBy('academic_year')->orderBy('semester')->get();
        $types = DocumentType::where('aktif', true)->orderBy('nama')->get(['id', 'nama', 'kode']);
        $studentRequirements = StudentArchiveRequirement::where('active', true)->where('required', true)->where(fn ($q) => $q->whereNull('class_name')->orWhere('class_name', $siswa->kelas))->get();
        $exemptions = StudentArchiveRequirementExemption::where('user_id', $siswa->id)->get()->keyBy('requirement_id');
        return view('admin.arsip-siswa.show', compact('siswa', 'archives', 'types', 'studentRequirements', 'exemptions'));
    }

    public function exemptRequirement(Request $request, User $siswa, StudentArchiveRequirement $requirement)
    {
        abort_unless($siswa->isSiswa(), 404);
        abort_unless($requirement->active && ($requirement->class_name === null || $requirement->class_name === $siswa->kelas), 404);
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']]);
        $exemption = StudentArchiveRequirementExemption::withTrashed()->firstOrNew(['user_id' => $siswa->id, 'requirement_id' => $requirement->id]);
        $exemption->fill(['created_by' => auth()->id(), 'reason' => $data['reason']]);
        if ($exemption->trashed()) $exemption->restore(); else $exemption->save();
        ActivityLog::catat(auth()->id(), 'arsip_syarat_tidak_berlaku', "Menandai persyaratan #{$requirement->id} tidak berlaku untuk siswa {$siswa->nis_nip}.");
        return back()->with('status', 'Persyaratan ditandai Tidak Berlaku untuk siswa ini. Alasan tersimpan.');
    }

    public function restoreRequirement(User $siswa, StudentArchiveRequirement $requirement)
    {
        abort_unless($siswa->isSiswa(), 404);
        StudentArchiveRequirementExemption::where('user_id', $siswa->id)->where('requirement_id', $requirement->id)->delete();
        ActivityLog::catat(auth()->id(), 'arsip_syarat_berlaku', "Mengaktifkan kembali persyaratan #{$requirement->id} untuk siswa {$siswa->nis_nip}.");
        return back()->with('status', 'Persyaratan kembali dihitung sebagai kewajiban siswa.');
    }

    public function storeRequirement(Request $request)
    {
        $data = $request->validate([
            'class_name' => ['nullable', 'string', 'max:40'],
            'academic_year' => ['nullable', 'string', 'max:20', 'prohibited_unless:category,rapor'],
            'category' => ['required', Rule::in(array_keys(StudentDocumentArchive::CATEGORIES))],
            'semester' => ['nullable', 'integer', 'between:1,2', 'prohibited_unless:category,rapor'],
            'report_period' => ['nullable', Rule::in(['tengah', 'akhir']), 'prohibited_unless:category,rapor'],
            'required' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);
        // Compare normalized dimensions in application code so NULL / empty values
        // have identical semantics across MySQL, MariaDB, and SQLite test databases.
        // The number of school-defined requirement rules is intentionally small.
        $normalize = static fn ($value) => $value === null || $value === '' ? null : (string) $value;
        $duplicate = StudentArchiveRequirement::query()->where('active', true)->get()->contains(
            static function (StudentArchiveRequirement $existing) use ($data, $normalize): bool {
                return $normalize($existing->class_name) === $normalize($data['class_name'] ?? null)
                    && $normalize($existing->academic_year) === $normalize($data['academic_year'] ?? null)
                    && $existing->category === $data['category']
                    && $normalize($existing->semester) === $normalize($data['semester'] ?? null)
                    && $normalize($existing->report_period) === $normalize($data['report_period'] ?? null)
                    && (bool) $existing->required === (bool) $data['required'];
            }
        );
        if ($duplicate) {
            return back()->withErrors(['category' => 'Aturan kelengkapan dengan cakupan yang sama sudah aktif.'])->withInput();
        }
        StudentArchiveRequirement::create($data + ['active' => true]);
        ActivityLog::catat(auth()->id(), 'arsip_syarat_tambah', 'Menambahkan aturan kelengkapan arsip siswa.');
        return back()->with('status', 'Aturan kelengkapan arsip disimpan.');
    }

    public function deleteRequirement(StudentArchiveRequirement $requirement)
    {
        $requirement->update(['active' => false]);
        ActivityLog::catat(auth()->id(), 'arsip_syarat_nonaktif', 'Menonaktifkan aturan kelengkapan arsip #' . $requirement->id . '.');
        return back()->with('status', 'Aturan dinonaktifkan tanpa menghapus histori.');
    }

    public function store(Request $request, User $siswa)
    {
        abort_unless($siswa->isSiswa(), 404);
        $data = $request->validate([
            'category' => ['required', Rule::in(array_keys(StudentDocumentArchive::CATEGORIES))],
            'document_type_id' => ['nullable', 'exists:document_types,id'],
            'academic_year' => ['nullable', 'string', 'max:20', 'required_if:category,rapor', 'prohibited_unless:category,rapor'],
            'semester' => ['nullable', 'integer', 'between:1,2', 'required_if:category,rapor', 'prohibited_unless:category,rapor'],
            'report_period' => ['nullable', Rule::in(['tengah', 'akhir']), 'required_if:category,rapor', 'prohibited_unless:category,rapor'],
            'publication_year' => ['nullable', 'integer', 'between:1990,2100', 'prohibited_if:category,rapor'],
            'digital_status' => ['required', Rule::in(array_keys(StudentDocumentArchive::DIGITAL))],
            'physical_status' => ['required', Rule::in(array_keys(StudentDocumentArchive::PHYSICAL))],
            'physical_location' => ['nullable', 'string', 'max:180'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:20480'],
        ]);

        $identity = [
            'user_id' => $siswa->id, 'category' => $data['category'],
            'academic_year' => $data['academic_year'] ?? null, 'semester' => $data['semester'] ?? null,
            'report_period' => $data['report_period'] ?? null, 'publication_year' => $data['publication_year'] ?? null,
        ];
        $identity['identity_key'] = hash('sha256', json_encode([$siswa->id, $data['category'], $data['academic_year'] ?? '', $data['semester'] ?? '', $data['report_period'] ?? '', $data['publication_year'] ?? ''], JSON_UNESCAPED_SLASHES));
        $archive = StudentDocumentArchive::withTrashed()->firstOrNew(['identity_key' => $identity['identity_key']]);
        $wasTrashed = $archive->exists && $archive->trashed();
        $old = $archive->exists ? $archive->only(['digital_status', 'physical_status', 'document_id', 'physical_location', 'version']) : [];
        $newFilePath = null;
        $disk = config('eksidu.disk_berkas', 'berkas');
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $newFilePath = $file->store('arsip-siswa/' . $siswa->id . '/' . now()->format('Y/m'), $disk);
            if (! $newFilePath) return back()->withErrors(['file' => 'File gagal disimpan pada penyimpanan privat.'])->withInput();
            try {
                DB::transaction(function () use ($archive, $identity, $data, $file, $disk, $newFilePath, $siswa, $old, $wasTrashed) {
                    if ($wasTrashed) $archive->restore();
                    $document = Document::create([
                        'user_id' => $siswa->id, 'document_type_id' => $data['document_type_id'] ?? null,
                        'uploaded_by' => auth()->id(), 'title' => StudentDocumentArchive::CATEGORIES[$data['category']],
                        'academic_year' => $data['academic_year'] ?? null, 'semester' => $data['semester'] ?? null,
                        'disk' => $disk, 'file_path' => $newFilePath, 'original_filename' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType() ?: 'application/octet-stream', 'file_size' => $file->getSize(),
                        'checksum_sha256' => hash_file('sha256', $file->getRealPath()), 'status' => 'available',
                    ]);
                    $archive->fill($identity + [
                        'document_type_id' => $data['document_type_id'] ?? null, 'document_id' => $document->id,
                        'digital_status' => 'tersedia', 'physical_status' => $data['physical_status'],
                        'physical_location' => $data['physical_location'] ?? null, 'notes' => $data['notes'] ?? null,
                        'version' => $archive->exists ? ((int) $archive->version) + 1 : 1,
                    ])->save();
                    if (($old['physical_status'] ?? null) === 'tersedia' && $archive->physical_status !== 'tersedia') $this->invalidatePhysicalRequests($archive, $archive->physical_status);
                    $this->recordChanges($archive, $old, auth()->id(), $data['notes'] ?? 'File digital diperbarui.');
                });
            } catch (\Throwable $e) {
                Storage::disk($disk)->delete($newFilePath);
                throw $e;
            }
        } else {
            if (($data['digital_status'] ?? null) === 'tersedia' && ! ($archive->document && $archive->document->status === 'available' && $archive->document->privateDisk()->exists($archive->document->file_path))) {
                return back()->withErrors(['digital_status' => 'Status Digital tidak dapat Tersedia sebelum file benar-benar diunggah.'])->withInput();
            }
            if ($wasTrashed) $archive->restore();
            $archive->fill($identity + [
                'document_type_id' => $data['document_type_id'] ?? null,
                'digital_status' => $data['digital_status'], 'physical_status' => $data['physical_status'],
                'physical_location' => $data['physical_location'] ?? null, 'notes' => $data['notes'] ?? null,
            ])->save();
            if (($old['digital_status'] ?? null) === 'tersedia' && $archive->digital_status !== 'tersedia') {
                $affected = Pengajuan::where('archive_id', $archive->id)->where('bentuk_dokumen', 'digital')->where('status', 'approved')->whereNull('digital_access_revoked_at')->get();
                foreach ($affected as $pengajuan) {
                    $pengajuan->update(['digital_access_revoked_at' => now()]);
                    $pengajuan->user?->notify(new DigitalAccessRevoked($pengajuan, 'Status arsip digital diubah menjadi ' . StudentDocumentArchive::DIGITAL[$archive->digital_status] . '.'));
                }
            }
            if (($old['physical_status'] ?? null) === 'tersedia' && $archive->physical_status !== 'tersedia') $this->invalidatePhysicalRequests($archive, $archive->physical_status);
            $this->recordChanges($archive, $old, auth()->id(), $data['notes'] ?? null);
        }

        ActivityLog::catat(auth()->id(), 'arsip_siswa_update', "Memperbarui arsip {$archive->id} milik siswa {$siswa->nis_nip}.");
        return back()->with('status', 'Arsip siswa berhasil disimpan.');
    }

    public function download(Request $request, StudentDocumentArchive $archive)
    {
        $document = $archive->document;
        abort_unless($document && $document->status === 'available' && $document->privateDisk()->exists($document->file_path), 404);
        ActivityLog::catat($request->user()->id, 'arsip_siswa_download', "Mengunduh file arsip siswa #{$archive->id}.");
        $response = $document->privateDisk()->download($document->file_path, $document->original_filename);
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        return $response;
    }

    public function destroy(StudentDocumentArchive $archive)
    {
        $hasActive = Pengajuan::where('archive_id', $archive->id)->whereIn('status', ['pending', 'diproses', 'approved'])->exists();
        if ($hasActive) return back()->with('error', 'Arsip tidak dapat dihapus selama masih memiliki pengajuan aktif.');
        $archive->delete();
        ActivityLog::catat(auth()->id(), 'arsip_siswa_hapus', "Mengarsipkan (soft delete) arsip siswa #{$archive->id}; data dan histori fisik tetap disimpan.");
        return back()->with('status', 'Arsip disembunyikan dari daftar aktif. File dan histori tidak dihapus permanen.');
    }

    public function update(Request $request, StudentDocumentArchive $archive)
    {
        $data = $request->validate([
            'academic_year' => ['nullable', 'string', 'max:20', Rule::prohibitedIf($archive->category !== 'rapor')],
            'semester' => ['nullable', 'integer', 'between:1,2', Rule::prohibitedIf($archive->category !== 'rapor')],
            'report_period' => ['nullable', Rule::in(['tengah', 'akhir']), Rule::prohibitedIf($archive->category !== 'rapor')],
            'publication_year' => ['nullable', 'integer', 'between:1990,2100', Rule::prohibitedIf($archive->category === 'rapor')],
            'document_type_id' => ['nullable', 'exists:document_types,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        if ($archive->category === 'rapor' && (blank($data['academic_year'] ?? null) || empty($data['semester']) || empty($data['report_period']))) {
            return back()->withErrors(['academic_year' => 'Arsip Rapor wajib memiliki tahun ajaran, semester, dan periode.'])->withInput();
        }
        $next = [
            $archive->user_id, $archive->category, $data['academic_year'] ?? '', $data['semester'] ?? '',
            $data['report_period'] ?? '', $data['publication_year'] ?? '',
        ];
        $data['identity_key'] = hash('sha256', json_encode($next, JSON_UNESCAPED_SLASHES));
        $oldMetadata = $archive->only(['academic_year', 'semester', 'report_period', 'publication_year', 'document_type_id']);
        $duplicate = StudentDocumentArchive::withTrashed()->where('identity_key', $data['identity_key'])->where('id', '<>', $archive->id)->exists();
        if ($duplicate) return back()->withErrors(['academic_year' => 'Arsip dengan kombinasi metadata tersebut sudah ada.'])->withInput();
        $archive->update($data);
        StudentDocumentArchiveHistory::create(['archive_id' => $archive->id, 'actor_id' => auth()->id(), 'field' => 'metadata', 'old_value' => json_encode($oldMetadata), 'new_value' => json_encode($data), 'notes' => $data['notes'] ?? 'Metadata arsip diperbarui.']);
        ActivityLog::catat(auth()->id(), 'arsip_siswa_metadata', "Memperbarui metadata arsip siswa #{$archive->id}.");
        return back()->with('status', 'Metadata arsip berhasil diperbarui.');
    }

    public function status(Request $request, StudentDocumentArchive $archive)
    {
        $data = $request->validate([
            'digital_status' => ['required', Rule::in(array_keys(StudentDocumentArchive::DIGITAL))],
            'physical_status' => ['required', Rule::in(array_keys(StudentDocumentArchive::PHYSICAL))],
            'physical_location' => ['nullable', 'string', 'max:180'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        if ($data['digital_status'] === 'tersedia' && ! ($archive->document && $archive->document->status === 'available' && $archive->document->privateDisk()->exists($archive->document->file_path))) {
            return back()->withErrors(['digital_status' => 'Status Digital tidak dapat Tersedia sebelum file aktif benar-benar ada.'])->withInput();
        }
        $old = $archive->only(array_keys($data));
        $archive->update($data);
        if (($old['digital_status'] ?? null) === 'tersedia' && $archive->digital_status !== 'tersedia') {
            $affected = Pengajuan::where('archive_id', $archive->id)->where('bentuk_dokumen', 'digital')->where('status', 'approved')->whereNull('digital_access_revoked_at')->get();
            foreach ($affected as $pengajuan) {
                $pengajuan->update(['digital_access_revoked_at' => now()]);
                $pengajuan->user?->notify(new DigitalAccessRevoked($pengajuan, 'Status arsip digital diubah menjadi ' . StudentDocumentArchive::DIGITAL[$archive->digital_status] . '.'));
            }
        }
        if (($old['physical_status'] ?? null) === 'tersedia' && $archive->physical_status !== 'tersedia') $this->invalidatePhysicalRequests($archive, $archive->physical_status);
        $this->recordChanges($archive, $old, auth()->id(), $data['notes'] ?? null);
        ActivityLog::catat(auth()->id(), 'arsip_siswa_status', "Mengubah status arsip siswa #{$archive->id}.");
        return back()->with('status', 'Status ketersediaan arsip diperbarui.');
    }

    private function invalidatePhysicalRequests(StudentDocumentArchive $archive, string $newStatus): void
    {
        $reason = 'Ketersediaan dokumen fisik berubah menjadi ' . StudentDocumentArchive::PHYSICAL[$newStatus] . '; pengajuan yang belum diserahkan dibatalkan.';
        $affected = Pengajuan::where('archive_id', $archive->id)->where('bentuk_dokumen', 'fisik')->where('status', 'approved')->get();
        foreach ($affected as $pengajuan) {
            $pengajuan->update([
                'status' => 'rejected', 'catatan_admin' => $reason,
                'physical_verification_hash' => null, 'physical_verification_code' => null,
                'physical_verification_expires_at' => null,
            ]);
            \App\Models\PengajuanRiwayat::catat($pengajuan, 'approved', 'rejected', auth()->id(), $reason);
            $pengajuan->user?->notify(new \App\Notifications\PengajuanStatusUpdated($pengajuan));
        }
    }

    private function recordChanges(StudentDocumentArchive $archive, array $old, ?int $actor, ?string $notes): void
    {
        foreach (['digital_status', 'physical_status', 'document_id', 'physical_location', 'version'] as $field) {
            $before = $old[$field] ?? null;
            $after = $archive->{$field};
            if ($field === 'document_id') {
                $beforeDoc = $before ? Document::find($before) : null;
                $afterDoc = $after ? Document::find($after) : null;
                $before = $beforeDoc ? $beforeDoc->id . ' | ' . $beforeDoc->original_filename : $before;
                $after = $afterDoc ? $afterDoc->id . ' | ' . $afterDoc->original_filename : $after;
            }
            if ((string) $before !== (string) $after) {
                StudentDocumentArchiveHistory::create(['archive_id' => $archive->id, 'actor_id' => $actor, 'field' => $field, 'old_value' => $before, 'new_value' => $after, 'notes' => $notes]);
            }
        }
    }
}
