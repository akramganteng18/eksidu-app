<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('student_archive_requirement_exemptions')) return;
        Schema::create('student_archive_requirement_exemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('requirement_id')->constrained('student_archive_requirements')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['user_id', 'requirement_id'], 'archive_requirement_exemption_unique');
        });
    }
    public function down(): void { /* Keep school audit records on rollback. */ }
};
