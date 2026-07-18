<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website_settings', function (Blueprint $table) {
            $table->boolean('send_absence_sms')->default(false)->after('meta_keywords');
            $table->text('absence_sms_template')->nullable()->after('send_absence_sms');
            $table->string('sms_sender_id', 32)->nullable()->after('absence_sms_template');
        });
    }

    public function down(): void
    {
        Schema::table('website_settings', function (Blueprint $table) {
            $table->dropColumn(['send_absence_sms', 'absence_sms_template', 'sms_sender_id']);
        });
    }
};