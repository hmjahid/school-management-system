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
        Schema::table('batches', function (Blueprint $table) {
            $table->foreignId('academic_session_id')
                  ->after('school_class_id')
                  ->nullable()
                  ->constrained('academic_sessions')
                  ->onDelete('set null');
                  
            // Add other missing columns
            $table->integer('capacity')->after('end_date')->default(30);
            $table->string('status')->after('capacity')->default('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->dropForeign(['academic_session_id']);
            $table->dropColumn(['academic_session_id', 'capacity', 'status']);
        });
    }
};
