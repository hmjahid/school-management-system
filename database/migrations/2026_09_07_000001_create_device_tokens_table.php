<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('device_tokens')) {
            return;
        }

        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token', 500);
            $table->string('platform', 30)->nullable();
            $table->string('device_name', 190)->nullable();
            $table->string('app_version', 30)->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'token']);
            $table->index('token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};
