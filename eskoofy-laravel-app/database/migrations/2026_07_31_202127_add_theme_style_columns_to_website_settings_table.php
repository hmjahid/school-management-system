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
            if (! Schema::hasColumn('website_settings', 'theme_header_style')) {
                $table->string('theme_header_style', 20)->nullable()->after('theme_border_radius');
            }
            if (! Schema::hasColumn('website_settings', 'theme_footer_style')) {
                $table->string('theme_footer_style', 20)->nullable()->after('theme_header_style');
            }
            if (! Schema::hasColumn('website_settings', 'theme_button_style')) {
                $table->string('theme_button_style', 20)->nullable()->after('theme_footer_style');
            }
            if (! Schema::hasColumn('website_settings', 'theme_section_spacing')) {
                $table->string('theme_section_spacing', 20)->nullable()->after('theme_button_style');
            }
            if (! Schema::hasColumn('website_settings', 'academic_start_month')) {
                $table->unsignedTinyInteger('academic_start_month')->nullable()->after('theme_section_spacing');
            }
            if (! Schema::hasColumn('website_settings', 'student_id_prefix')) {
                $table->string('student_id_prefix', 20)->nullable()->after('academic_start_month');
            }
        });
    }

    public function down(): void
    {
        Schema::table('website_settings', function (Blueprint $table) {
            $table->dropColumn([
                'theme_header_style',
                'theme_footer_style',
                'theme_button_style',
                'theme_section_spacing',
                'academic_start_month',
                'student_id_prefix',
            ]);
        });
    }
};
