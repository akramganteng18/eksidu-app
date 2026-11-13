<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('document_types')) {
            return;
        }

        if (! Schema::hasColumn('document_types', 'layanan')) {
            Schema::table('document_types', function (Blueprint $table) {
                $table->json('layanan')->nullable()->after('aktif');
            });
        }

        // Pemetaan awal berdasarkan kode bawaan EKSiDU. Data master khusus milik
        // sekolah tidak dihapus; Admin dapat mengatur layanan untuk kode lainnya.
        $pemetaan = [
            'RPR' => ['peminjaman_rapor', 'legalisir'],
            'IJZ' => ['legalisir', 'pengambilan_ijazah'],
            'SKHUN' => ['legalisir'],
            'SKL' => [],
        ];

        foreach ($pemetaan as $kode => $layanan) {
            DB::table('document_types')
                ->where('kode', $kode)
                ->update(['layanan' => json_encode($layanan)]);
        }
    }

    public function down(): void
    {
        // Sengaja no-op agar metadata layanan tidak terhapus saat rollback
        // yang tidak disengaja pada database sekolah.
    }
};
