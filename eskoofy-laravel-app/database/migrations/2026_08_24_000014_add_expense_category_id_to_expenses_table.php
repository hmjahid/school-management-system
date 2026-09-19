<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('expenses', 'expense_category_id')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->unsignedBigInteger('expense_category_id')->nullable()->after('id');
                $table->foreign('expense_category_id')
                    ->references('id')
                    ->on('expense_categories')
                    ->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('expenses', 'expense_category_id')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->dropForeign(['expense_category_id']);
                $table->dropColumn('expense_category_id');
            });
        }
    }
};
