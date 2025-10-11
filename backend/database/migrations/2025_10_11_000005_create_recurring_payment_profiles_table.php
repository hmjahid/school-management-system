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
        Schema::create('recurring_payment_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('profile_id')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->morphs('paymentable');
            $table->string('gateway');
            $table->string('gateway_profile_id')->nullable();
            
            // Payment details
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('BDT');
            
            // Billing cycle
            $table->enum('billing_period', ['day', 'week', 'month', 'year']);
            $table->unsignedSmallInteger('billing_frequency')->default(1);
            
            // Billing dates
            $table->dateTime('start_date');
            $table->dateTime('next_billing_date');
            $table->dateTime('end_date')->nullable();
            
            // Status
            $table->enum('status', ['active', 'suspended', 'cancelled', 'expired'])->default('active');
            
            // Payment method details
            $table->string('payment_method_token')->nullable();
            $table->string('card_last4', 4)->nullable();
            $table->string('card_brand')->nullable();
            $table->string('card_expiry')->nullable();
            
            // Retry settings
            $table->unsignedTinyInteger('max_failures')->default(3);
            $table->unsignedTinyInteger('failure_count')->default(0);
            
            // Additional data
            $table->json('metadata')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['user_id', 'status']);
            $table->index('next_billing_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_payment_profiles');
    }
};
