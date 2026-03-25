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
        Schema::table('classes', function (Blueprint $table) {
            $table->boolean('is_active')->after('schedule')->default(true);
            $table->text('description')->after('is_active')->nullable();
            $table->string('room_number')->after('description')->nullable();
            $table->integer('max_students')->after('room_number')->default(30);
            $table->json('schedule_days')->after('max_students')->nullable();
            $table->time('start_time')->after('schedule_days')->nullable();
            $table->time('end_time')->after('start_time')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropColumn([
                'is_active',
                'description',
                'room_number',
                'max_students',
                'schedule_days',
                'start_time',
                'end_time'
            ]);
        });
    }
};
