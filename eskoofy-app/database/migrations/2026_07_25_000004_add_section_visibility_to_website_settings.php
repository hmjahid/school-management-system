<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website_settings', function (Blueprint $table) {
            $table->json('section_visibility')->nullable()->after('default_locale');
        });

        DB::table('website_settings')->whereNull('section_visibility')->update([
            'section_visibility' => json_encode([
                'hero' => true,
                'features' => true,
                'stats' => true,
                'principal' => true,
                'testimonials' => true,
                'events' => true,
                'news' => true,
                'highlights' => true,
                'cta' => true,
                'partners' => true,
                'admissions_bar' => true,
                'urgent_notices' => true,
            ]),
        ]);
    }

    public function down(): void
    {
        Schema::table('website_settings', function (Blueprint $table) {
            $table->dropColumn('section_visibility');
        });
    }
};
