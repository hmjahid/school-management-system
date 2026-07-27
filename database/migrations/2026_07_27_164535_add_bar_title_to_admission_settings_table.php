<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admission_settings', function (Blueprint $table) {
            $table->string('bar_title_en', 255)->nullable()->after('display_year');
            $table->string('bar_title_bn', 255)->nullable()->after('bar_title_en');
        });
    }

    public function down(): void
    {
        Schema::table('admission_settings', function (Blueprint $table) {
            $table->dropColumn(['bar_title_en', 'bar_title_bn']);
        });
    }
};
