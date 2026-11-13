<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('physical_inventory_items')) {
            Schema::create('physical_inventory_items', function (Blueprint $table) {
                $table->id();
                $table->string('item_code', 40)->unique();
                $table->string('name');
                $table->string('description', 1000)->nullable();
                $table->unsignedInteger('stock_total');
                $table->unsignedInteger('stock_available');
                $table->string('location', 120)->nullable();
                $table->boolean('active')->default(true)->index();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['active', 'name']);
            });
        }

        if (! Schema::hasTable('physical_loans')) {
            Schema::create('physical_loans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('inventory_item_id')->constrained('physical_inventory_items')->restrictOnDelete();
                $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('handed_over_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('returned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status', 20)->default('pending')->index();
                $table->text('purpose');
                $table->text('admin_note')->nullable();
                $table->dateTime('approved_at')->nullable();
                $table->dateTime('borrowed_at')->nullable();
                $table->date('due_at')->nullable()->index();
                $table->dateTime('returned_at')->nullable();
                $table->string('return_condition', 80)->nullable();
                $table->timestamps();
                $table->index(['user_id', 'status']);
                $table->index(['inventory_item_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        // Sengaja no-op: data transaksi/inventaris sekolah tidak boleh terhapus oleh rollback otomatis.
    }
};
