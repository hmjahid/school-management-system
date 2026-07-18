<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The original barebones teachers migration (2025_10_05_033158_create_teachers_table.php)
 * created a `subjects` JSON column. The Teacher model later added a `subjects()`
 * belongsToMany relation that pivots through `class_subject_teacher`. When both
 * exist with the same name, accessing `$teacher->subjects` returns the column value
 * (null) instead of the loaded relation, causing
 * `Call to a member function pluck() on null` in the show view.
 *
 * The legacy column is unused — subject assignments are always read through the
 * pivot. Drop it to resolve the name collision.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('teachers', 'subjects')) {
            Schema::table('teachers', function (Blueprint $table) {
                $table->dropColumn('subjects');
            });
        }
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->json('subjects')->nullable();
        });
    }
};
