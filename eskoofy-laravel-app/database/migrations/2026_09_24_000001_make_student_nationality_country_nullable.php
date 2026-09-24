<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Let the receiving variant store a NULL nationality/country instead of the
     * Bangladeshi default. The app already validates these fields as nullable;
     * this only relaxes the DB column so a cross-variant CSV import into the int
     * variant does not bake BD defaults into int student rows.
     */
    public function up(): void
    {
        if (! Schema::hasTable('students')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'nationality')) {
                $table->string('nationality', 100)->nullable()->change();
            }
            if (Schema::hasColumn('students', 'country')) {
                $table->string('country', 100)->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('students')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'nationality')) {
                $table->string('nationality', 100)->default('Bangladeshi')->change();
            }
            if (Schema::hasColumn('students', 'country')) {
                $table->string('country', 100)->default('Bangladesh')->change();
            }
        });
    }
};
