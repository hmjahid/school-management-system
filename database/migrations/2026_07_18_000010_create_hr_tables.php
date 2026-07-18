<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name_en', 100);
            $table->string('name_bn', 100)->nullable();
            $table->unsignedSmallInteger('days_per_year')->default(0);
            $table->boolean('is_paid')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
            $table->date('from_date');
            $table->date('to_date');
            $table->text('reason');
            $table->string('status', 20)->default('pending'); // pending, approved, rejected, cancelled
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('approver_note')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('from_date');
        });

        Schema::create('salary_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->decimal('basic', 10, 2)->default(0);
            $table->json('allowances')->nullable(); // [{name, amount}]
            $table->json('deductions')->nullable(); // [{name, amount}]
            $table->date('effective_from');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['teacher_id', 'is_active']);
        });

        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->decimal('basic', 10, 2)->default(0);
            $table->decimal('total_allowances', 10, 2)->default(0);
            $table->decimal('total_deductions', 10, 2)->default(0);
            $table->decimal('net_salary', 10, 2)->default(0);
            $table->json('details')->nullable();
            $table->string('status', 20)->default('draft'); // draft, paid
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['teacher_id', 'month', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslips');
        Schema::dropIfExists('salary_structures');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_types');
    }
};