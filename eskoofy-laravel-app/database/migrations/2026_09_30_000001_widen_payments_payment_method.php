<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * `payments.payment_method` was a closed ENUM that did not even list the
     * already-shipped `uddoktapay` / `paddle` codes. Since the method is really
     * a `payment_gateways.code`, it must accept arbitrary codes — including the
     * international gateways and any gateway an admin adds manually.
     */
    public function up(): void
    {
        if (! Schema::hasTable('payments') || ! Schema::hasColumn('payments', 'payment_method')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->string('payment_method', 50)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('payments') || ! Schema::hasColumn('payments', 'payment_method')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->enum('payment_method', [
                'cash',
                'bank_transfer',
                'cheque',
                'bkash',
                'nagad',
                'rocket',
                'stripe',
                'paypal',
                'other',
            ])->change();
        });
    }
};
