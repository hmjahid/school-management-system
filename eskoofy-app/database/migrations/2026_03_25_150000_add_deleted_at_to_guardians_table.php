<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Guardians model uses SoftDeletes; early create_guardians migration had no deleted_at.
     */
    public function up(): void
    {
        if (Schema::hasTable('guardians') && ! Schema::hasColumn('guardians', 'deleted_at')) {
            Schema::table('guardians', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('guardians') && Schema::hasColumn('guardians', 'deleted_at')) {
            Schema::table('guardians', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
