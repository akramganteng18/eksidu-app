<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('document_loans')) return;

        Schema::create('document_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('purpose', 1000);
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed'])->default('pending');
            $table->text('admin_note')->nullable();
            $table->string('token_hash', 64)->nullable()->unique();
            $table->longText('qr_svg')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('accessed_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->index(['document_id', 'status']);
        });
    }

    public function down(): void
    {
        // Tidak menghapus tabel transaksi, terutama pada database lama.
    }
};
