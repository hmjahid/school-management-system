<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('late_fee_per_day', 8, 2)->default(5.00);
            $table->unsignedInteger('max_books_per_student')->default(3);
            $table->unsignedInteger('max_books_per_teacher')->default(10);
            $table->unsignedInteger('issue_duration_days')->default(14);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_settings');
    }
};
