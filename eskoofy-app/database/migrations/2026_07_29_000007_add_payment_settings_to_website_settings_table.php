<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website_settings', function (Blueprint $table) {
            $table->string('bkash_merchant_number')->nullable()->after('timezone');
            $table->string('bkash_api_key')->nullable()->after('bkash_merchant_number');
            $table->string('bkash_api_secret')->nullable()->after('bkash_api_key');
            $table->string('bkash_username')->nullable()->after('bkash_api_secret');
            $table->string('bkash_password')->nullable()->after('bkash_username');
            $table->string('bkash_app_key')->nullable()->after('bkash_password');
            $table->string('bkash_app_secret')->nullable()->after('bkash_app_key');
            $table->boolean('bkash_sandbox')->default(true)->after('bkash_app_secret');
            $table->string('nagad_merchant_number')->nullable()->after('bkash_sandbox');
            $table->string('currency', 10)->default('BDT')->after('nagad_merchant_number');
            $table->string('default_payment_method', 50)->default('bkash')->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('website_settings', function (Blueprint $table) {
            $table->dropColumn([
                'bkash_merchant_number', 'bkash_api_key', 'bkash_api_secret',
                'bkash_username', 'bkash_password', 'bkash_app_key', 'bkash_app_secret',
                'bkash_sandbox', 'nagad_merchant_number', 'currency', 'default_payment_method',
            ]);
        });
    }
};
