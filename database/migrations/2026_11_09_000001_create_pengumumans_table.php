<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengumumans', function (Blueprint $table) {
            $table->id();
            $table->string('judul', 180);
            $table->longText('isi');
            $table->string('sasaran', 20)->default('semua'); // semua, siswa, admin
            $table->boolean('diterbitkan')->default(false);
            $table->timestamp('mulai_pada')->nullable();
            $table->timestamp('berakhir_pada')->nullable();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['diterbitkan', 'mulai_pada', 'berakhir_pada'], 'pengumuman_tayang_index');
            $table->index('sasaran');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengumumans');
    }
};
