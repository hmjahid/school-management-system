<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cloud_backup_runs')) {
            return;
        }

        Schema::create('cloud_backup_runs', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('file_name');
            $table->string('remote_id')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('status')->default('success'); // success | failed | skipped
            $table->text('message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cloud_backup_runs');
    }
};
