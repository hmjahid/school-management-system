<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('courses')) {
            Schema::create('courses', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->unique();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('batches') && ! Schema::hasColumn('batches', 'course_id')) {
            Schema::table('batches', function (Blueprint $table) {
                $table->foreignId('course_id')->nullable()->after('academic_session_id')->constrained()->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('batches') && Schema::hasColumn('batches', 'course_id')) {
            Schema::table('batches', function (Blueprint $table) {
                $table->dropConstrainedForeignId('course_id');
            });
        }

        Schema::dropIfExists('courses');
    }
};
