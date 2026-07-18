<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('admission_tests')) {
            return;
        }

        Schema::create('admission_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_id')->constrained('admissions')->onDelete('cascade');
            $table->timestamp('scheduled_at')->nullable();
            $table->string('venue')->nullable();
            $table->string('status')->default('scheduled'); // scheduled|completed|missed|cancelled
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['admission_id', 'scheduled_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_tests');
    }
};
