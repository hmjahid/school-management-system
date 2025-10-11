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
        if (Schema::hasTable('teachers')) {
            return;
        }
        
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('employee_id')->unique();
            $table->string('qualification')->nullable();
            
            // Personal Information
            $table->string('gender', 10)->nullable();
            $table->string('blood_group', 5)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('religion', 50)->nullable();
            $table->string('nationality', 100)->default('Bangladeshi');
            
            // Contact Information
            $table->string('phone', 20)->nullable();
            $table->string('emergency_contact', 20)->nullable();
            $table->text('present_address')->nullable();
            $table->text('permanent_address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('zip_code', 20)->nullable();
            $table->string('country', 100)->default('Bangladesh');
            
            // Professional Information
            $table->date('joining_date');
            $table->date('leaving_date')->nullable();
            $table->enum('status', ['active', 'inactive', 'on_leave', 'retired'])->default('active');
            
            // Bank Details
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_account_number', 50)->nullable();
            $table->string('bank_branch', 100)->nullable();
            
            // Salary Information
            $table->decimal('salary', 10, 2)->default(0);
            $table->string('salary_type', 20)->default('monthly');
            
            // Documents
            $table->string('nid_number', 50)->nullable();
            $table->string('passport_number', 50)->nullable();
            $table->string('driving_license', 50)->nullable();
            
            // Additional Information
            $table->text('notes')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
