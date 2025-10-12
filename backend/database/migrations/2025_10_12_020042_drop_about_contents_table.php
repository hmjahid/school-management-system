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
        if (Schema::hasTable('about_contents')) {
            Schema::dropIfExists('about_contents');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This is a destructive migration, so we won't implement the down method
        // as we don't want to automatically recreate the table with data
    }
};
