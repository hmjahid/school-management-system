<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Older installs created `fees` before the schema included a unique `code`.
     */
    public function up(): void
    {
        if (! Schema::hasTable('fees') || Schema::hasColumn('fees', 'code')) {
            return;
        }

        Schema::table('fees', function (Blueprint $table) {
            $table->string('code', 50)->nullable()->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('fees') || ! Schema::hasColumn('fees', 'code')) {
            return;
        }

        Schema::table('fees', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });
    }
};
