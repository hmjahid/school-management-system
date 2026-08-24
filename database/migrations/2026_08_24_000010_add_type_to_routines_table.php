<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('routines', 'type')) {
            Schema::table('routines', function (Blueprint $table) {
                $table->string('type')->nullable()->default('class')->after('is_active');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('routines', 'type')) {
            Schema::table('routines', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }
    }
};
