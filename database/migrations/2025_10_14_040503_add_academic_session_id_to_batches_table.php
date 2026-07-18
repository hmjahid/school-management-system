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
        if (! Schema::hasTable('batches')) {
            return;
        }

        Schema::table('batches', function (Blueprint $table) {
            if (! Schema::hasColumn('batches', 'academic_session_id')) {
                $table->foreignId('academic_session_id')
                    ->nullable()
                    ->constrained('academic_sessions')
                    ->onDelete('set null');
            }

            // Add other missing columns
            if (! Schema::hasColumn('batches', 'capacity')) {
                $table->integer('capacity')->default(30);
            }
            if (! Schema::hasColumn('batches', 'status')) {
                $table->string('status')->default('active');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('batches')) {
            return;
        }

        Schema::table('batches', function (Blueprint $table) {
            if (Schema::hasColumn('batches', 'academic_session_id')) {
                $table->dropForeign(['academic_session_id']);
            }
            $drops = collect(['academic_session_id', 'capacity', 'status'])->filter(fn ($c) => Schema::hasColumn('batches', $c))->values()->all();
            if ($drops !== []) {
                $table->dropColumn($drops);
            }
        });
    }
};
