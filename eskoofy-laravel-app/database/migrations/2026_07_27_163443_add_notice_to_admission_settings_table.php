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
        Schema::table('admission_settings', function (Blueprint $table) {
            $table->text('notice_en')->nullable()->after('payment_instructions_bn');
            $table->text('notice_bn')->nullable()->after('notice_en');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admission_settings', function (Blueprint $table) {
            $table->dropColumn(['notice_en', 'notice_bn']);
        });
    }
};
