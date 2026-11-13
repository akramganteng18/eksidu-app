<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Riwayat perubahan status per pengajuan (timeline di halaman detail).
 * Bersifat TAMBAHAN: tidak mengubah/menghapus tabel atau data yang sudah ada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_riwayats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_id')->constrained('pengajuans')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status_dari', 20)->nullable();
            $table->string('status_ke', 20);
            $table->text('catatan')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['pengajuan_id', 'created_at']);
        });

        // Backfill untuk pengajuan yang sudah ada sebelum fitur ini, supaya
        // timeline tidak kosong. Ditandai jelas sebagai data historis.
        DB::table('pengajuans')->orderBy('id')->chunkById(200, function ($rows) {
            $insert = [];
            foreach ($rows as $row) {
                $insert[] = [
                    'pengajuan_id' => $row->id,
                    'user_id'      => $row->user_id,
                    'status_dari'  => null,
                    'status_ke'    => 'pending',
                    'catatan'      => 'Pengajuan dibuat.',
                    'created_at'   => $row->created_at,
                ];

                if ($row->status !== 'pending') {
                    $insert[] = [
                        'pengajuan_id' => $row->id,
                        'user_id'      => $row->verified_by,
                        'status_dari'  => null,
                        'status_ke'    => $row->status,
                        'catatan'      => 'Status terakhir sebelum riwayat rinci dicatat (data historis).',
                        'created_at'   => $row->verified_at ?? $row->updated_at ?? $row->created_at,
                    ];
                }
            }
            if ($insert) {
                DB::table('pengajuan_riwayats')->insert($insert);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_riwayats');
    }
};
