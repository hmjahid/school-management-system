<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('website_settings', function (Blueprint $table) {
            $table->boolean('show_facebook')->default(true)->after('facebook_url');
            $table->boolean('show_instagram')->default(true)->after('instagram_url');
            $table->boolean('show_twitter')->default(true)->after('twitter_url');
            $table->boolean('show_youtube')->default(true)->after('youtube_url');
            $table->boolean('show_linkedin')->default(true)->after('linkedin_url');
        });
    }

    public function down(): void
    {
        Schema::table('website_settings', function (Blueprint $table) {
            $table->dropColumn([
                'show_facebook',
                'show_instagram',
                'show_twitter',
                'show_youtube',
                'show_linkedin',
            ]);
        });
    }
};
