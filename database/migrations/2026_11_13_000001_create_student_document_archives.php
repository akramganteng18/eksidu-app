<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('student_document_archives')) {
            Schema::create('student_document_archives', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->char('identity_key', 64)->unique();
                $table->foreignId('document_type_id')->nullable()->constrained('document_types')->nullOnDelete();
                $table->foreignId('document_id')->nullable()->constrained('documents')->nullOnDelete();
                $table->enum('category', ['rapor', 'tka', 'skls', 'ijazah']);
                $table->string('academic_year', 20)->nullable();
                $table->unsignedTinyInteger('semester')->nullable();
                $table->enum('report_period', ['tengah', 'akhir'])->nullable();
                $table->unsignedSmallInteger('publication_year')->nullable();
                $table->enum('digital_status', ['belum_diunggah', 'tersedia', 'tidak_tersedia', 'diarsipkan'])->default('belum_diunggah');
                $table->enum('physical_status', ['belum_tercatat', 'tersedia', 'tidak_tersedia', 'hilang', 'rusak'])->default('belum_tercatat');
                $table->string('physical_location', 180)->nullable();
                $table->text('notes')->nullable();
                $table->unsignedInteger('version')->default(1);
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['user_id', 'category', 'academic_year', 'semester', 'report_period', 'publication_year'], 'student_archive_identity_unique');
                $table->index(['user_id', 'category', 'digital_status', 'physical_status'], 'student_archive_availability_idx');
            });
        }

        // Safe legacy mapping: only IJZ is unambiguous. SKL and SKHUN are deliberately not mapped to SKLS.
        if (Schema::hasTable('documents') && Schema::hasTable('document_types')
            && Schema::hasColumn('documents', 'user_id') && Schema::hasColumn('documents', 'document_type_id')
            && Schema::hasColumn('documents', 'file_path') && Schema::hasColumn('documents', 'disk')) {
            $legacyIjazah = \Illuminate\Support\Facades\DB::table('documents')
                ->join('document_types', 'document_types.id', '=', 'documents.document_type_id')
                ->join('users', 'users.id', '=', 'documents.user_id')
                ->where('users.role', 'siswa')->where('document_types.kode', 'IJZ')
                ->orderByDesc('documents.id')->select('documents.*')->get();
            foreach ($legacyIjazah as $document) {
                $identity = hash('sha256', json_encode([$document->user_id, 'ijazah', '', '', '', $document->graduation_year ?? ''], JSON_UNESCAPED_SLASHES));
                $disk = in_array($document->disk ?? '', ['private', 'local'], true) ? config('eksidu.disk_berkas', 'berkas') : ($document->disk ?: 'berkas');
                $fileExists = false;
                try { $fileExists = ($document->status ?? 'available') === 'available' && \Illuminate\Support\Facades\Storage::disk($disk)->exists($document->file_path); } catch (\Throwable $e) { $fileExists = false; }
                \Illuminate\Support\Facades\DB::table('student_document_archives')->insertOrIgnore([
                    'user_id' => $document->user_id, 'identity_key' => $identity, 'document_type_id' => $document->document_type_id,
                    'document_id' => $document->id, 'category' => 'ijazah', 'publication_year' => $document->graduation_year ?? null,
                    'digital_status' => $fileExists ? 'tersedia' : 'tidak_tersedia', 'physical_status' => 'belum_tercatat',
                    'version' => 1, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        if (! Schema::hasTable('student_document_archive_histories')) {
            Schema::create('student_document_archive_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('archive_id')->constrained('student_document_archives')->cascadeOnDelete();
                $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('field', 60);
                $table->text('old_value')->nullable();
                $table->text('new_value')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['archive_id', 'created_at']);
            });
        }

        Schema::table('pengajuans', function (Blueprint $table) {
            if (! Schema::hasColumn('pengajuans', 'archive_id')) $table->foreignId('archive_id')->nullable()->after('document_type_id')->constrained('student_document_archives')->nullOnDelete();
            if (! Schema::hasColumn('pengajuans', 'document_version_id')) $table->foreignId('document_version_id')->nullable()->constrained('documents')->nullOnDelete();
            if (! Schema::hasColumn('pengajuans', 'result_document_id')) $table->foreignId('result_document_id')->nullable()->constrained('documents')->nullOnDelete();
            if (! Schema::hasColumn('pengajuans', 'kategori_layanan')) $table->string('kategori_layanan', 30)->nullable()->after('jenis_layanan');
            if (! Schema::hasColumn('pengajuans', 'bentuk_dokumen')) $table->string('bentuk_dokumen', 12)->nullable()->after('kategori_layanan');
            if (! Schema::hasColumn('pengajuans', 'academic_year')) $table->string('academic_year', 20)->nullable();
            if (! Schema::hasColumn('pengajuans', 'semester_akademik')) $table->unsignedTinyInteger('semester_akademik')->nullable();
            if (! Schema::hasColumn('pengajuans', 'periode_rapor')) $table->string('periode_rapor', 12)->nullable();
            if (! Schema::hasColumn('pengajuans', 'physical_verification_hash')) $table->char('physical_verification_hash', 64)->nullable();
            if (! Schema::hasColumn('pengajuans', 'physical_verification_code')) $table->text('physical_verification_code')->nullable();
            if (! Schema::hasColumn('pengajuans', 'physical_verification_expires_at')) $table->timestamp('physical_verification_expires_at')->nullable();
            if (! Schema::hasColumn('pengajuans', 'digital_access_hash')) $table->char('digital_access_hash', 64)->nullable();
            if (! Schema::hasColumn('pengajuans', 'digital_access_token')) $table->text('digital_access_token')->nullable();
            if (! Schema::hasColumn('pengajuans', 'digital_access_revoked_at')) $table->timestamp('digital_access_revoked_at')->nullable();
        });
    }

    public function down(): void
    {
        // Additive migration: intentionally keep archive/history and added request data on rollback.
    }
};
