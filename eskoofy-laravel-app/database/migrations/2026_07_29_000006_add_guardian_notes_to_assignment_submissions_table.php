<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assignment_submissions', function (Blueprint $table) {
            $table->text('guardian_notes')->nullable()->after('notes');
            $table->timestamp('guardian_notified_at')->nullable()->after('guardian_notes');
            $table->foreignId('guardian_id')->nullable()->after('student_id')->constrained('guardians')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('assignment_submissions', function (Blueprint $table) {
            $table->dropForeign(['guardian_id']);
            $table->dropColumn(['guardian_notes', 'guardian_notified_at', 'guardian_id']);
        });
    }
};
