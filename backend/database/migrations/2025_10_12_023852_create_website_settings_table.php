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
        Schema::create('website_settings', function (Blueprint $table) {
            $table->id();
            // School Information
            $table->string('school_name');
            $table->string('tagline')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('favicon_path')->nullable();
            $table->year('established_year');
            
            // Contact Information
            $table->string('address');
            $table->string('city');
            $table->string('state');
            $table->string('country');
            $table->string('postal_code');
            $table->string('phone');
            $table->string('email');
            $table->string('website')->nullable();
            
            // School Hours
            $table->json('opening_hours')->nullable();
            
            // Social Media Links
            $table->string('facebook_url')->nullable();
            $table->string('twitter_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('youtube_url')->nullable();
            
            // SEO
            $table->text('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            
            // Additional Settings
            $table->string('timezone')->default('UTC');
            $table->string('date_format')->default('Y-m-d');
            $table->string('time_format')->default('H:i');
            
            // System Settings
            $table->boolean('maintenance_mode')->default(false);
            $table->text('maintenance_message')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('website_settings');
    }
};
