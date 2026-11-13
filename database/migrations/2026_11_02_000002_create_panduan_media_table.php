<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('panduan_media', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->enum('jenis', ['video', 'audio'])->default('video');
            $table->enum('sumber', ['unggah', 'tautan'])->default('unggah');
            $table->string('file_path')->nullable();   // disk privat "berkas"
            $table->string('mime', 100)->nullable();
            $table->string('url')->nullable();         // tautan YouTube (sumber = tautan)
            $table->enum('untuk', ['semua', 'siswa', 'admin'])->default('semua');
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->boolean('aktif')->default(true);
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['aktif', 'untuk', 'urutan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('panduan_media');
    }
};
