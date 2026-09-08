<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the existing table if it exists
        if (Schema::hasTable('about_contents')) {
            Schema::dropIfExists('about_contents');
        }

        // Recreate the table with the correct schema
        Schema::create('about_contents', function (Blueprint $table) {
            $table->id();
            $table->string('school_name');
            $table->string('tagline')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('favicon_path')->nullable();
            $table->year('established_year');
            $table->text('about_summary');
            $table->text('mission')->nullable();
            $table->text('vision')->nullable();
            $table->text('history')->nullable();
            $table->json('core_values')->nullable();
            $table->json('contact_info')->nullable();
            $table->json('social_links')->nullable();
            $table->text('address');
            $table->string('phone');
            $table->string('email');
            $table->string('website')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('about_contents');
    }
};
