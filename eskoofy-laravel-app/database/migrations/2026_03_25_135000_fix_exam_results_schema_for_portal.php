<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('exam_results')) {
            return;
        }

        Schema::table('exam_results', function (Blueprint $table) {
            // Soft deletes + publishability are required by App\Models\ExamResult and the portal.
            if (! Schema::hasColumn('exam_results', 'deleted_at')) {
                $table->softDeletes();
            }

            if (! Schema::hasColumn('exam_results', 'is_published')) {
                $table->boolean('is_published')->default(true)->after('gpa');
            }
            if (! Schema::hasColumn('exam_results', 'published_at')) {
                $table->timestamp('published_at')->nullable()->after('is_published');
            }
            if (! Schema::hasColumn('exam_results', 'published_by')) {
                $table->unsignedBigInteger('published_by')->nullable()->after('published_at');
            }

            // Additional fields used by the richer model (kept nullable for legacy rows).
            if (! Schema::hasColumn('exam_results', 'obtained_marks')) {
                $table->float('obtained_marks')->nullable()->after('exam_id');
            }
            if (! Schema::hasColumn('exam_results', 'grade_point')) {
                $table->float('grade_point')->nullable()->after('grade');
            }
            if (! Schema::hasColumn('exam_results', 'remarks')) {
                $table->text('remarks')->nullable()->after('grade_point');
            }
            if (! Schema::hasColumn('exam_results', 'status')) {
                $table->string('status')->nullable()->after('remarks');
            }
        });
    }

    public function down(): void
    {
        // Non-destructive: keep columns to avoid data loss.
    }
};
