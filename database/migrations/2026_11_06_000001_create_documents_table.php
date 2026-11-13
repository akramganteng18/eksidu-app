<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Database lokal sebagian sudah memiliki tabel ini. Jangan sentuh atau buat duplikat.
        if (Schema::hasTable('documents')) return;

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('document_type_id')->nullable()->constrained('document_types')->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('academic_year', 20)->nullable();
            $table->unsignedTinyInteger('semester')->nullable();
            $table->unsignedSmallInteger('graduation_year')->nullable()->index();
            $table->string('disk', 40)->default('private');
            $table->string('file_path');
            $table->string('original_filename');
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('file_size');
            $table->char('checksum_sha256', 64)->nullable();
            $table->enum('status', ['available', 'archived'])->default('available');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'status']);
            $table->index(['document_type_id', 'academic_year']);
        });
    }

    public function down(): void
    {
        // Tabel yang sudah ada sebelum migration tidak boleh dihapus otomatis.
        // Rollback sengaja no-op demi melindungi data lokal pengguna.
    }
};
