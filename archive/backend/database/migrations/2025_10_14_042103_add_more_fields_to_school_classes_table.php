<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('school_classes', function (Blueprint $table) {
            // Add code column if it doesn't exist
            if (!Schema::hasColumn('school_classes', 'code')) {
                $table->string('code', 20)->nullable()->after('name');
            }
            
            // Add description column if it doesn't exist
            if (!Schema::hasColumn('school_classes', 'description')) {
                $table->text('description')->nullable()->after('code');
            }
            
            // Add grade_level column if it doesn't exist
            if (!Schema::hasColumn('school_classes', 'grade_level')) {
                $table->integer('grade_level')->nullable()->after('description');
            }
            
            // Add academic_session_id column if it doesn't exist
            if (!Schema::hasColumn('school_classes', 'academic_session_id')) {
                $table->foreignId('academic_session_id')
                    ->nullable()
                    ->after('grade_level')
                    ->constrained('academic_sessions')
                    ->onDelete('set null');
            }
            
            // Add class_teacher_id column if it doesn't exist
            if (!Schema::hasColumn('school_classes', 'class_teacher_id')) {
                $table->foreignId('class_teacher_id')
                    ->nullable()
                    ->after('academic_session_id')
                    ->constrained('teachers')
                    ->onDelete('set null');
            }
            
            // Add max_students column if it doesn't exist
            if (!Schema::hasColumn('school_classes', 'max_students')) {
                $table->integer('max_students')->default(30)->after('class_teacher_id');
            }
            
            // Add is_active column if it doesn't exist
            if (!Schema::hasColumn('school_classes', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('max_students');
            }
            
            // Add fee-related columns if they don't exist
            if (!Schema::hasColumn('school_classes', 'monthly_fee')) {
                $table->decimal('monthly_fee', 10, 2)->default(0)->after('is_active');
            }
            
            if (!Schema::hasColumn('school_classes', 'admission_fee')) {
                $table->decimal('admission_fee', 10, 2)->default(0)->after('monthly_fee');
            }
            
            if (!Schema::hasColumn('school_classes', 'exam_fee')) {
                $table->decimal('exam_fee', 10, 2)->default(0)->after('admission_fee');
            }
            
            if (!Schema::hasColumn('school_classes', 'other_fees')) {
                $table->decimal('other_fees', 10, 2)->default(0)->after('exam_fee');
            }
            
            if (!Schema::hasColumn('school_classes', 'notes')) {
                $table->text('notes')->nullable()->after('other_fees');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('school_classes', function (Blueprint $table) {
            // We're not dropping columns in the down method to avoid data loss
            // If you need to rollback, create a new migration to drop these columns
        });
    }
};
