<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admission_settings', function (Blueprint $table) {
            $table->decimal('admission_fee', 10, 2)->default(0)->after('closed_message_bn');
            $table->string('payment_number', 64)->nullable()->after('admission_fee');
            $table->text('payment_instructions_en')->nullable()->after('payment_number');
            $table->text('payment_instructions_bn')->nullable()->after('payment_instructions_en');
        });
    }

    public function down(): void
    {
        Schema::table('admission_settings', function (Blueprint $table) {
            $table->dropColumn([
                'admission_fee',
                'payment_number',
                'payment_instructions_en',
                'payment_instructions_bn',
            ]);
        });
    }
};