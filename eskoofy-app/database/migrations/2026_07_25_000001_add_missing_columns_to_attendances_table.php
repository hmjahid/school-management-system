<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('batch_id')->nullable()->after('school_class_id')->constrained()->nullOnDelete();
            $table->foreignId('section_id')->nullable()->after('batch_id')->constrained()->nullOnDelete();
            $table->foreignId('teacher_id')->nullable()->after('section_id')->constrained()->nullOnDelete();
            $table->foreignId('academic_session_id')->nullable()->after('teacher_id')->constrained()->nullOnDelete();
            $table->string('type', 30)->nullable()->default('daily')->after('academic_session_id');
            $table->string('period', 50)->nullable()->after('type');
            $table->text('remarks')->nullable()->after('period');
            $table->foreignId('recorded_by')->nullable()->after('remarks')->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->after('recorded_by')->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable()->after('updated_by');
            $table->unique(['student_id', 'date', 'type'], 'attendances_unique_per_student_date_type');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['batch_id']);
            $table->dropForeign(['section_id']);
            $table->dropForeign(['teacher_id']);
            $table->dropForeign(['academic_session_id']);
            $table->dropForeign(['recorded_by']);
            $table->dropForeign(['updated_by']);
            $table->dropIndex('attendances_unique_per_student_date_type');
            $table->dropColumn([
                'batch_id', 'section_id', 'teacher_id', 'academic_session_id',
                'type', 'period', 'remarks', 'recorded_by', 'updated_by', 'metadata',
            ]);
        });
    }
};
