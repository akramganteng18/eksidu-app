<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('student_archive_requirements')) return;
        Schema::create('student_archive_requirements', function (Blueprint $table) {
            $table->id();
            $table->string('class_name', 40)->nullable()->index();
            $table->string('academic_year', 20)->nullable();
            $table->enum('category', ['rapor', 'tka', 'skls', 'ijazah']);
            $table->unsignedTinyInteger('semester')->nullable();
            $table->enum('report_period', ['tengah', 'akhir'])->nullable();
            $table->boolean('required')->default(true);
            $table->boolean('active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['active', 'class_name', 'category'], 'archive_requirement_scope_idx');
        });
    }

    public function down(): void
    {
        // Retain school-defined requirements on rollback.
    }
};
