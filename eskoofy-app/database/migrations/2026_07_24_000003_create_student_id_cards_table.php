<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_id_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('id_card_number', 50)->unique();
            $table->date('issue_date');
            $table->date('expiry_date')->nullable();
            $table->string('blood_group', 10)->nullable();
            $table->text('photo_url')->nullable();
            $table->json('details')->nullable();
            $table->string('status', 20)->default('active');
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_id_cards');
    }
};
