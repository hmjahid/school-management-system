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
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            
            // Payment relationship
            $table->foreignId('payment_id')->constrained()->onDelete('cascade');
            
            // User who made the original payment
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Admin who processed the refund
            $table->foreignId('processed_by')->constrained('users')->onDelete('set null');
            
            // Refund details
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('BDT');
            $table->string('transaction_id')->nullable()->comment('Gateway transaction ID for the refund');
            
            // Status tracking
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->string('reason')->nullable();
            
            // Timestamps
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Additional metadata
            $table->json('metadata')->nullable();
            
            // Indexes
            $table->index('transaction_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
