<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('website_settings', 'default_locale')) {
            Schema::table('website_settings', function (Blueprint $table): void {
                $table->string('default_locale', 8)->default('en')->after('timezone');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('website_settings', 'default_locale')) {
            Schema::table('website_settings', function (Blueprint $table): void {
                $table->dropColumn('default_locale');
            });
        }
    }
};
