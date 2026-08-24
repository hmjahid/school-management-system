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
        Schema::table('website_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('website_settings', 'theme_style')) {
                $table->string('theme_style', 20)->default('default')->after('theme_section_spacing');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('website_settings', function (Blueprint $table) {
            if (Schema::hasColumn('website_settings', 'theme_style')) {
                $table->dropColumn('theme_style');
            }
        });
    }
};
