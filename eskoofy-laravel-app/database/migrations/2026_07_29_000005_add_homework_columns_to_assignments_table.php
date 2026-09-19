<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->foreignId('class_id')->nullable()->after('batch_id')->constrained('school_classes')->nullOnDelete();
            $table->foreignId('section_id')->nullable()->after('class_id')->constrained('sections')->nullOnDelete();
            $table->boolean('allow_guardian_notes')->default(false)->after('total_marks');
        });
    }

    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropForeign(['class_id']);
            $table->dropForeign(['section_id']);
            $table->dropColumn(['class_id', 'section_id', 'allow_guardian_notes']);
        });
    }
};
