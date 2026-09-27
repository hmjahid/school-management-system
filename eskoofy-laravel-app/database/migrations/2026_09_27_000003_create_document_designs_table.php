<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('document_designs')) {
            return;
        }

        Schema::create('document_designs', function (Blueprint $table) {
            $table->id();
            $table->string('document_type');
            $table->string('name');
            $table->string('template')->default('classic');
            $table->boolean('is_default')->default(false);
            $table->json('settings')->nullable();
            $table->json('watermark')->nullable();
            $table->text('custom_css')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['document_type', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_designs');
    }
};
