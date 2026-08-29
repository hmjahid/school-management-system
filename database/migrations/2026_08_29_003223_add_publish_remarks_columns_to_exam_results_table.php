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
            if (! Schema::hasColumn('exam_results', 'publish_remarks')) {
                $table->text('publish_remarks')->nullable()->after('published_by');
            }

            if (! Schema::hasColumn('exam_results', 'unpublished_at')) {
                $table->timestamp('unpublished_at')->nullable()->after('publish_remarks');
            }

            if (! Schema::hasColumn('exam_results', 'unpublished_by')) {
                $table->unsignedBigInteger('unpublished_by')->nullable()->after('unpublished_at');
            }

            if (! Schema::hasColumn('exam_results', 'unpublish_remarks')) {
                $table->text('unpublish_remarks')->nullable()->after('unpublished_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('exam_results', function (Blueprint $table) {
            $table->dropColumn(['publish_remarks', 'unpublished_at', 'unpublished_by', 'unpublish_remarks']);
        });
    }
};
