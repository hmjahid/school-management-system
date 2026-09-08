<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website_settings', function (Blueprint $table) {
            $table->boolean('mail_enabled')->default(false)->after('twilio_from_number');
            $table->string('mail_driver', 32)->nullable()->after('mail_enabled');
            $table->string('mail_host', 255)->nullable()->after('mail_driver');
            $table->string('mail_port', 10)->nullable()->after('mail_host');
            $table->text('mail_username')->nullable()->after('mail_port');
            $table->text('mail_password')->nullable()->after('mail_username');
            $table->string('mail_encryption', 10)->nullable()->after('mail_password');
            $table->string('mail_from_address', 255)->nullable()->after('mail_encryption');
            $table->string('mail_from_name', 255)->nullable()->after('mail_from_address');
            $table->string('mail_test_recipient', 255)->nullable()->after('mail_from_name');
        });
    }

    public function down(): void
    {
        Schema::table('website_settings', function (Blueprint $table) {
            $table->dropColumn([
                'mail_enabled',
                'mail_driver',
                'mail_host',
                'mail_port',
                'mail_username',
                'mail_password',
                'mail_encryption',
                'mail_from_address',
                'mail_from_name',
                'mail_test_recipient',
            ]);
        });
    }
};
