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
        if (Schema::hasTable('payment_gateways')) {
            return;
        }
        
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            
            // Basic information
            $table->string('name');
            $table->string('code')->unique();
            $table->enum('type', [
                'bank',
                'mobile_financial_service',
                'online_payment',
                'other',
            ]);
            
            // Status and capabilities
            $table->boolean('is_active')->default(true);
            $table->boolean('is_online')->default(false);
            $table->boolean('has_api')->default(false);
            
            // URLs
            $table->string('sandbox_url')->nullable();
            $table->string('live_url')->nullable();
            $table->boolean('test_mode')->default(true);
            
            // API credentials
            $table->text('api_key')->nullable();
            $table->text('api_secret')->nullable();
            $table->string('api_username')->nullable();
            $table->text('api_password')->nullable();
            
            // Callback and webhook URLs
            $table->string('callback_url')->nullable();
            $table->string('webhook_url')->nullable();
            $table->string('success_url')->nullable();
            $table->string('cancel_url')->nullable();
            $table->string('ipn_url')->nullable();
            
            // Display and information
            $table->string('logo')->nullable();
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            
            // Currency and fees
            $table->char('currency', 3)->default('BDT');
            $table->decimal('fee_percentage', 5, 2)->default(0);
            $table->decimal('fee_fixed', 12, 2)->default(0);
            
            // Amount limits
            $table->decimal('min_amount', 12, 2)->nullable();
            $table->decimal('max_amount', 12, 2)->nullable();
            
            // Supported currencies (JSON array)
            $table->json('supported_currencies')->nullable();
            
            // Additional configuration (JSON)
            $table->json('extra_attributes')->nullable();
            
            // Sorting and ordering
            $table->unsignedInteger('sort_order')->default(0);
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['is_active', 'is_online', 'type']);
        });
        
        // Add fulltext index for search (only for MySQL/PostgreSQL)
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE payment_gateways ADD FULLTEXT fulltext_index (name, code, description)');
        } else {
            // Add regular indexes for SQLite
            Schema::table('payment_gateways', function (Blueprint $table) {
                $table->index('name');
                $table->index('code');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('payment_gateways')) {
            // Drop the fulltext index if it exists (MySQL/PostgreSQL)
            if (DB::connection()->getDriverName() !== 'sqlite') {
                DB::statement('ALTER TABLE payment_gateways DROP INDEX fulltext_index');
            } else {
                // Drop the SQLite indexes
                Schema::table('payment_gateways', function (Blueprint $table) {
                    $table->dropIndex('payment_gateways_name_index');
                    $table->dropIndex('payment_gateways_code_index');
                });
            }
            
            // Drop the table
            Schema::dropIfExists('payment_gateways');
        }
    }
};
