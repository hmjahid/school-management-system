<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Teacher model defines belongsToMany relationships to SchoolClass, Subject,
 * and Section via three pivot tables that were never migrated:
 *   - class_teacher  (Teacher::classes  <-> SchoolClass::teachers)
 *   - class_subject_teacher  (Subject::teachers)  — aligned here
 *   - section_teacher (Teacher::sections <-> Section::teachers)
 *
 * Originally the codebase had a `subject_teacher` table (referenced by the
 * Teacher::subjects() relationship), but the Subject::teachers() relationship
 * pointed to `class_subject_teacher` instead. We align on the Subject side
 * (the more feature-rich pivot) and update Teacher::subjects() to match.
 *
 * All pivots include `academic_session_id` so assignments can be scoped to
 * a school year. Cascades are configured for safety on teacher/class deletion.
 */
return new class extends Migration
{
    public function up(): void
    {
        // class_teacher: <teacher, class, is_class_teacher, academic_session>
        if (! Schema::hasTable('class_teacher')) {
            Schema::create('class_teacher', function (Blueprint $table) {
                $table->id();
                $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
                $table->foreignId('class_id')->constrained('school_classes')->cascadeOnDelete();
                $table->boolean('is_class_teacher')->default(false);
                $table->foreignId('academic_session_id')->nullable()->constrained('academic_sessions')->nullOnDelete();
                $table->timestamps();
                $table->unique(['teacher_id', 'class_id', 'academic_session_id'], 'class_teacher_unique');
            });
        }

        // class_subject_teacher: <teacher, subject, class, academic_session, is_primary>
        if (! Schema::hasTable('class_subject_teacher')) {
            Schema::create('class_subject_teacher', function (Blueprint $table) {
                $table->id();
                $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
                $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
                $table->foreignId('class_id')->nullable()->constrained('school_classes')->nullOnDelete();
                $table->foreignId('academic_session_id')->nullable()->constrained('academic_sessions')->nullOnDelete();
                $table->boolean('is_primary')->default(false);
                $table->timestamps();
                $table->unique(['teacher_id', 'subject_id', 'class_id', 'academic_session_id'], 'class_subject_teacher_unique');
            });
        }

        // section_teacher: <teacher, section, subject, academic_session, is_class_teacher>
        if (! Schema::hasTable('section_teacher')) {
            Schema::create('section_teacher', function (Blueprint $table) {
                $table->id();
                $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
                $table->foreignId('section_id')->constrained('sections')->cascadeOnDelete();
                $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
                $table->foreignId('academic_session_id')->nullable()->constrained('academic_sessions')->nullOnDelete();
                $table->boolean('is_class_teacher')->default(false);
                $table->timestamps();
                $table->unique(['teacher_id', 'section_id', 'subject_id', 'academic_session_id'], 'section_teacher_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('section_teacher');
        Schema::dropIfExists('class_subject_teacher');
        Schema::dropIfExists('class_teacher');
    }
};
