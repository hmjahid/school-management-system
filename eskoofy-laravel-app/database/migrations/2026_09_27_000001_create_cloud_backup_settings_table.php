<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cloud_backup_settings')) {
            return;
        }

        Schema::create('cloud_backup_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->default('local');
            $table->text('credentials')->nullable();
            $table->string('folder')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->boolean('auto_enabled')->default(false);
            $table->unsignedInteger('interval_minutes')->default(60);
            $table->unsignedInteger('keep')->default(7);
            $table->timestamp('last_run_at')->nullable();
            $table->string('last_status')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cloud_backup_settings');
    }
};
