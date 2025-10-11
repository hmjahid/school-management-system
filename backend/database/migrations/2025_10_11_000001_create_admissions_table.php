<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdmissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('admissions', function (Blueprint $table) {
            $table->id();
            
            // Application Information
            $table->string('application_number')->unique();
            $table->foreignId('academic_session_id')->constrained()->onDelete('cascade');
            $table->foreignId('batch_id')->constrained()->onDelete('cascade');
            
            // Student Information
            $table->string('first_name');
            $table->string('last_name');
            $table->enum('gender', ['male', 'female', 'other']);
            $table->date('date_of_birth');
            $table->string('blood_group', 10)->nullable();
            $table->string('religion')->nullable();
            $table->string('nationality')->default('Bangladeshi');
            $table->string('photo')->nullable();
            
            // Contact Information
            $table->string('email')->unique();
            $table->string('phone');
            $table->text('address');
            $table->string('city');
            $table->string('state')->nullable();
            $table->string('country')->default('Bangladesh');
            $table->string('postal_code');
            
            // Parent/Guardian Information
            $table->string('father_name');
            $table->string('father_phone');
            $table->string('father_occupation')->nullable();
            $table->string('mother_name');
            $table->string('mother_phone');
            $table->string('mother_occupation')->nullable();
            $table->string('guardian_name')->nullable();
            $table->string('guardian_relation')->nullable();
            $table->string('guardian_phone')->nullable();
            
            // Previous Education
            $table->string('previous_school')->nullable();
            $table->string('previous_class')->nullable();
            $table->string('previous_grade')->nullable();
            
            // Documents
            $table->string('transfer_certificate')->nullable();
            $table->string('birth_certificate')->nullable();
            $table->json('other_documents')->nullable();
            
            // Status and Timestamps
            $table->enum('status', [
                'draft', 'submitted', 'under_review', 'approved', 'rejected', 'waitlisted', 'enrolled', 'cancelled'
            ])->default('draft');
            
            $table->text('rejection_reason')->nullable();
            $table->date('admission_date')->nullable();
            $table->text('admission_notes')->nullable();
            
            // Timestamps
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            
            // User references
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('rejected_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Additional metadata
            $table->json('metadata')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['application_number', 'status', 'email', 'phone']);
        });
        
        // Add fulltext index for search
        DB::statement('ALTER TABLE admissions ADD FULLTEXT fulltext_index (first_name, last_name, email, phone, father_name, mother_name)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('admissions');
    }
}
