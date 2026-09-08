<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routines', function (Blueprint $table) {
            if (! Schema::hasColumn('routines', 'section_id')) {
                $table->foreignId('section_id')->nullable()->after('school_class_id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('routines', 'batch_id')) {
                $table->foreignId('batch_id')->nullable()->after('section_id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('routines', 'academic_session_id')) {
                $table->foreignId('academic_session_id')->nullable()->after('batch_id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('routines', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('room_number');
            }
        });
    }

    public function down(): void {}
};
