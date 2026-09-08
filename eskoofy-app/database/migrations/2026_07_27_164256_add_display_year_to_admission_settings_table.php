<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admission_settings', function (Blueprint $table) {
            $table->string('display_year', 9)->nullable()->after('is_open');
        });
    }

    public function down(): void
    {
        Schema::table('admission_settings', function (Blueprint $table) {
            $table->dropColumn('display_year');
        });
    }
};
