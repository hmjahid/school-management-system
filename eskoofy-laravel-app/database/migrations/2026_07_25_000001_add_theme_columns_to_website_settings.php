<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website_settings', function (Blueprint $table) {
            $table->string('theme_primary_color', 20)->nullable()->after('sms_sender_id');
            $table->string('theme_secondary_color', 20)->nullable()->after('theme_primary_color');
            $table->string('theme_font_family', 100)->nullable()->after('theme_secondary_color');
            $table->string('theme_border_radius', 20)->nullable()->after('theme_font_family');
        });
    }

    public function down(): void
    {
        Schema::table('website_settings', function (Blueprint $table) {
            $table->dropColumn([
                'theme_primary_color',
                'theme_secondary_color',
                'theme_font_family',
                'theme_border_radius',
            ]);
        });
    }
};
