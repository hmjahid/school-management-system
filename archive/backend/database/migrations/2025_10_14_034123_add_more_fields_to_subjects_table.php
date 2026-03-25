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
        Schema::table('subjects', function (Blueprint $table) {
            $table->string('type')->after('code')->default('core');
            $table->string('short_name')->after('type')->nullable();
            $table->decimal('credit_hours', 5, 2)->after('short_name')->default(3.00);
            $table->text('description')->after('credit_hours')->nullable();
            $table->boolean('is_active')->after('description')->default(true);
            $table->integer('display_order')->after('is_active')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn([
                'type',
                'short_name',
                'credit_hours',
                'description',
                'is_active',
                'display_order'
            ]);
        });
    }
};
