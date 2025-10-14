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
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->integer('capacity')->default(30);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('class_teacher_id')->nullable()->constrained('users');
            $table->foreignId('academic_year_id')->constrained('academic_years');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->dropForeign(['class_teacher_id']);
            $table->dropForeign(['academic_year_id']);
        });
        
        Schema::dropIfExists('sections');
    }
};
