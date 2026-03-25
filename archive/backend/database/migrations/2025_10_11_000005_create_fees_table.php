<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('fees')) {
            return;
        }
        
        Schema::create('fees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->foreignId('class_id')->constrained('school_classes');
            $table->foreignId('section_id')->nullable()->constrained();
            $table->foreignId('student_id')->nullable()->constrained();
            $table->decimal('amount', 10, 2);
            
            // Fee type: tuition, admission, exam, transport, library, etc.
            $table->string('fee_type');
            
            // Frequency: one_time, daily, weekly, monthly, quarterly, half_yearly, yearly
            $table->string('frequency')->default('one_time');
            
            // For recurring fees
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            
            // Fine settings
            $table->decimal('fine_amount', 10, 2)->default(0);
            $table->string('fine_type')->default('fixed'); // fixed or percentage
            $table->integer('fine_grace_days')->default(0);
            
            // Discount settings
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->string('discount_type')->default('fixed'); // fixed or percentage
            
            // Status
            $table->enum('status', ['active', 'inactive', 'archived'])->default('active');
            
            // Additional metadata
            $table->json('metadata')->nullable();
            
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('fees');
    }
};
