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
        Schema::table('students', function (Blueprint $table) {
            // Add school_class_id column if it doesn't exist
            if (!Schema::hasColumn('students', 'school_class_id')) {
                $table->foreignId('school_class_id')->after('class_id')->constrained('school_classes')->onDelete('cascade');
            }
            
            // Add roll_no column if it doesn't exist
            if (!Schema::hasColumn('students', 'roll_no')) {
                $table->string('roll_no')->after('admission_no');
            }
            
            // Add admission_no column if it doesn't exist
            if (!Schema::hasColumn('students', 'admission_no')) {
                $table->string('admission_no')->after('user_id')->unique();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Remove the foreign key constraint first
            if (Schema::hasColumn('students', 'school_class_id')) {
                $table->dropForeign(['school_class_id']);
                $table->dropColumn('school_class_id');
            }
            
            // Only drop roll_no if it was added by this migration
            if (Schema::hasColumn('students', 'roll_no')) {
                $table->dropColumn('roll_no');
            }
            
            // Only drop admission_no if it was added by this migration
            if (Schema::hasColumn('students', 'admission_no')) {
                $table->dropColumn('admission_no');
            }
        });
    }
};
