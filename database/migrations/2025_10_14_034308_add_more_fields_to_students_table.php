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
            // Only add columns that don't already exist
            if (!Schema::hasColumn('students', 'roll_number')) {
                $table->string('roll_number')->after('user_id')->nullable();
            }
            if (!Schema::hasColumn('students', 'admission_number')) {
                $table->string('admission_number')->after('roll_number')->unique();
            }
            if (!Schema::hasColumn('students', 'admission_date')) {
                $table->date('admission_date')->after('admission_number');
            }
            if (!Schema::hasColumn('students', 'date_of_birth')) {
                $table->date('date_of_birth')->after('admission_date')->nullable();
            }
            if (!Schema::hasColumn('students', 'gender')) {
                $table->enum('gender', ['male', 'female', 'other'])->after('date_of_birth')->nullable();
            }
            if (!Schema::hasColumn('students', 'blood_group')) {
                $table->string('blood_group', 10)->after('gender')->nullable();
            }
            if (!Schema::hasColumn('students', 'religion')) {
                $table->string('religion', 50)->after('blood_group')->nullable();
            }
            if (!Schema::hasColumn('students', 'phone')) {
                $table->string('phone', 20)->after('religion')->nullable();
            }
            if (!Schema::hasColumn('students', 'city')) {
                $table->string('city', 100)->after('phone')->nullable();
            }
            if (!Schema::hasColumn('students', 'state')) {
                $table->string('state', 100)->after('city')->nullable();
            }
            if (!Schema::hasColumn('students', 'country')) {
                $table->string('country', 100)->after('state')->default('Bangladesh');
            }
            if (!Schema::hasColumn('students', 'status')) {
                $table->enum('status', ['active', 'inactive', 'suspended', 'graduated', 'transferred'])->default('active');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Only drop columns that exist
            $columnsToDrop = [];
            if (Schema::hasColumn('students', 'roll_number')) {
                $columnsToDrop[] = 'roll_number';
            }
            if (Schema::hasColumn('students', 'admission_number')) {
                $columnsToDrop[] = 'admission_number';
            }
            if (Schema::hasColumn('students', 'admission_date')) {
                $columnsToDrop[] = 'admission_date';
            }
            if (Schema::hasColumn('students', 'date_of_birth')) {
                $columnsToDrop[] = 'date_of_birth';
            }
            if (Schema::hasColumn('students', 'gender')) {
                $columnsToDrop[] = 'gender';
            }
            if (Schema::hasColumn('students', 'blood_group')) {
                $columnsToDrop[] = 'blood_group';
            }
            if (Schema::hasColumn('students', 'religion')) {
                $columnsToDrop[] = 'religion';
            }
            if (Schema::hasColumn('students', 'phone')) {
                $columnsToDrop[] = 'phone';
            }
            if (Schema::hasColumn('students', 'city')) {
                $columnsToDrop[] = 'city';
            }
            if (Schema::hasColumn('students', 'state')) {
                $columnsToDrop[] = 'state';
            }
            if (Schema::hasColumn('students', 'country')) {
                $columnsToDrop[] = 'country';
            }
            if (Schema::hasColumn('students', 'status')) {
                $columnsToDrop[] = 'status';
            }
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
