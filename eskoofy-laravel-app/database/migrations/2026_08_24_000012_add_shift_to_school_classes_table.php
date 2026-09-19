<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('school_classes', 'shift')) {
            Schema::table('school_classes', function (Blueprint $table) {
                $table->string('shift')->nullable()->after('notes');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('school_classes', 'shift')) {
            Schema::table('school_classes', function (Blueprint $table) {
                $table->dropColumn('shift');
            });
        }
    }
};
