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
        // This is a no-op migration to mark the notifications migration as completed
        // The notifications table is already created by Laravel's default migration
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
