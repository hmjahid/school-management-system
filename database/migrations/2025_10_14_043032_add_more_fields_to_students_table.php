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
            // Add first_name column if it doesn't exist
            if (!Schema::hasColumn('students', 'first_name')) {
                $table->string('first_name')->after('admission_id');
            }
            
            // Add last_name column if it doesn't exist
            if (!Schema::hasColumn('students', 'last_name')) {
                $table->string('last_name')->after('first_name');
            }
            
            // Add gender column if it doesn't exist
            if (!Schema::hasColumn('students', 'gender')) {
                $table->enum('gender', ['male', 'female', 'other'])->after('last_name');
            }
            
            // Add date_of_birth column if it doesn't exist
            if (!Schema::hasColumn('students', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('gender');
            }
            
            // Add blood_group column if it doesn't exist
            if (!Schema::hasColumn('students', 'blood_group')) {
                $table->string('blood_group', 5)->nullable()->after('date_of_birth');
            }
            
            // Add religion column if it doesn't exist
            if (!Schema::hasColumn('students', 'religion')) {
                $table->string('religion')->nullable()->after('blood_group');
            }
            
            // Add nationality column if it doesn't exist
            if (!Schema::hasColumn('students', 'nationality')) {
                $table->string('nationality')->default('Bangladeshi')->after('religion');
            }
            
            // Add email column if it doesn't exist
            if (!Schema::hasColumn('students', 'email')) {
                $table->string('email')->unique()->nullable()->after('nationality');
            }
            
            // Add phone column if it doesn't exist
            if (!Schema::hasColumn('students', 'phone')) {
                $table->string('phone')->nullable()->after('email');
            }
            
            // Add address columns if they don't exist
            if (!Schema::hasColumn('students', 'address')) {
                $table->text('address')->nullable()->after('phone');
            }
            
            if (!Schema::hasColumn('students', 'city')) {
                $table->string('city')->nullable()->after('address');
            }
            
            if (!Schema::hasColumn('students', 'state')) {
                $table->string('state')->nullable()->after('city');
            }
            
            if (!Schema::hasColumn('students', 'country')) {
                $table->string('country')->default('Bangladesh')->after('state');
            }
            
            if (!Schema::hasColumn('students', 'postal_code')) {
                $table->string('postal_code', 20)->nullable()->after('country');
            }
            
            // Add father's information columns if they don't exist
            if (!Schema::hasColumn('students', 'father_name')) {
                $table->string('father_name')->nullable()->after('postal_code');
            }
            
            if (!Schema::hasColumn('students', 'father_phone')) {
                $table->string('father_phone')->nullable()->after('father_name');
            }
            
            if (!Schema::hasColumn('students', 'father_occupation')) {
                $table->string('father_occupation')->nullable()->after('father_phone');
            }
            
            // Add mother's information columns if they don't exist
            if (!Schema::hasColumn('students', 'mother_name')) {
                $table->string('mother_name')->nullable()->after('father_occupation');
            }
            
            if (!Schema::hasColumn('students', 'mother_phone')) {
                $table->string('mother_phone')->nullable()->after('mother_name');
            }
            
            if (!Schema::hasColumn('students', 'mother_occupation')) {
                $table->string('mother_occupation')->nullable()->after('mother_phone');
            }
            
            // Add status column if it doesn't exist
            if (!Schema::hasColumn('students', 'status')) {
                $table->enum('status', ['active', 'inactive', 'graduated', 'transferred'])->default('active')->after('mother_occupation');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // We're not dropping columns in the down method to avoid data loss
            // If you need to rollback, create a new migration to drop these columns
        });
    }
};
