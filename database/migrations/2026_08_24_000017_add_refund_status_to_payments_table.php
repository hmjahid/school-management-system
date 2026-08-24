<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payments') && !Schema::hasColumn('payments', 'refund_status')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->string('refund_status')->default('not_refunded')->after('payment_status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'refund_status')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropColumn('refund_status');
            });
        }
    }
};
