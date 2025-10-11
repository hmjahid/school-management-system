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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            
            // Polymorphic relationship
            $table->nullableMorphs('paymentable');
            
            // Invoice and reference
            $table->string('invoice_number')->unique();
            $table->string('reference_number')->nullable();
            $table->string('transaction_id')->nullable();
            
            // Amount details
            $table->decimal('amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('due_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('fine_amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            
            // Payment method and status
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
            ]);
            
            $table->enum('payment_status', [
                'pending',
                'processing',
                'completed',
                'failed',
                'refunded',
                'cancelled',
                'expired',
            ])->default('pending');
            
            // Dates
            $table->date('payment_date')->nullable();
            $table->date('due_date')->nullable();
            
            // Additional details
            $table->json('payment_details')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            
            // User references
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['invoice_number', 'payment_status', 'payment_method']);
            $table->index(['paymentable_type', 'paymentable_id']);
        });
        
        // Add fulltext index for search
        DB::statement('ALTER TABLE payments ADD FULLTEXT fulltext_index (invoice_number, reference_number, transaction_id)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
