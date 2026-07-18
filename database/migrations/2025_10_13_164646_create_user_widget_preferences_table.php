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
        Schema::create('user_widget_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('widget_id');
            $table->boolean('enabled')->default(true);
            $table->integer('position')->default(0);
            $table->json('settings')->nullable();
            $table->timestamps();
            
            // Ensure each user can only have one preference per widget
            $table->unique(['user_id', 'widget_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_widget_preferences');
    }
};
