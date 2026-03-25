<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('fee_payments', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('student_id')->constrained();
            $table->foreignId('fee_id')->constrained();
            $table->decimal('amount', 10, 2);
            
            // Payment details
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('fine_amount', 10, 2)->default(0);
            $table->decimal('paid_amount', 10, 2);
            $table->decimal('balance', 10, 2)->default(0);
            
            // Payment period
            $table->date('payment_date');
            $table->string('month')->nullable();
            $table->integer('year')->nullable();
            
            // Payment method
            $table->string('payment_method'); // cash, bank_transfer, check, online_payment, etc.
            $table->string('transaction_id')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('check_number')->nullable();
            
            // Status
            $table->enum('status', ['pending', 'paid', 'partial', 'cancelled', 'refunded'])->default('pending');
            
            // Notes and metadata
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            
            // Created by and timestamps
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('fee_payments');
    }
};
