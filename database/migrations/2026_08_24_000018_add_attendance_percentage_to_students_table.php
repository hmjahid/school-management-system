<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('students') && !Schema::hasColumn('students', 'attendance_percentage')) {
            Schema::table('students', function (Blueprint $table) {
                $table->decimal('attendance_percentage', 5, 2)->nullable()->after('roll_number');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('students') && Schema::hasColumn('students', 'attendance_percentage')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn('attendance_percentage');
            });
        }
    }
};
