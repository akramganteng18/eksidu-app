<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuans', function (Blueprint $table) {
            $table->id();
            $table->string('kode_tiket')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('jenis_layanan', ['peminjaman_rapor', 'legalisir', 'pengambilan_ijazah']);
            $table->foreignId('document_type_id')->nullable()->constrained('document_types')->nullOnDelete();
            $table->text('keperluan');
            $table->date('tanggal_dibutuhkan')->nullable();
            $table->unsignedInteger('jumlah_lembar')->nullable();
            $table->string('file_pendukung')->nullable();
            $table->enum('status', ['pending', 'diproses', 'approved', 'rejected', 'selesai'])->default('pending');
            $table->text('catatan_admin')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->date('tanggal_pengembalian')->nullable();
            $table->string('kondisi_pengembalian')->nullable();
            $table->timestamps();

            // Index untuk mempercepat filter & laporan admin
            $table->index('status');
            $table->index('jenis_layanan');
            $table->index(['status', 'created_at']); // laporan per periode & status
            $table->index(['user_id', 'status']);     // riwayat siswa per status
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuans');
    }
};