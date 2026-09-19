<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            $table->decimal('admission_fee', 10, 2)->default(0)->after('admission_notes');
            $table->string('payment_number', 64)->nullable()->after('admission_fee');
            $table->string('transaction_id', 128)->nullable()->after('payment_number');
            $table->string('payment_method', 32)->nullable()->after('transaction_id');
            $table->string('payment_status', 32)->default('unpaid')->after('payment_method');
            $table->timestamp('paid_at')->nullable()->after('payment_status');
            $table->timestamp('verified_at')->nullable()->after('paid_at');
            $table->foreignId('verified_by')->nullable()->after('verified_at')->constrained('users')->nullOnDelete();
            $table->text('payment_note')->nullable()->after('verified_by');

            $table->index('payment_status');
            $table->index('transaction_id');
        });
    }

    public function down(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropIndex(['payment_status']);
            $table->dropIndex(['transaction_id']);
            $table->dropColumn([
                'admission_fee',
                'payment_number',
                'transaction_id',
                'payment_method',
                'payment_status',
                'paid_at',
                'verified_at',
                'verified_by',
                'payment_note',
            ]);
        });
    }
};
