<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            if (! Schema::hasColumn('certificates', 'student_id')) {
                $table->foreignId('student_id')->nullable()->after('id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('certificates', 'certificate_type')) {
                $table->string('certificate_type', 50)->nullable()->after('student_id');
            }
            if (! Schema::hasColumn('certificates', 'issue_date')) {
                $table->date('issue_date')->nullable()->after('certificate_type');
            }
            if (! Schema::hasColumn('certificates', 'certificate_number')) {
                $table->string('certificate_number', 50)->unique()->nullable()->after('issue_date');
            }
            if (! Schema::hasColumn('certificates', 'body')) {
                $table->json('body')->nullable()->after('template');
            }
            if (! Schema::hasColumn('certificates', 'status')) {
                $table->string('status', 20)->default('draft')->after('body');
            }
            if (! Schema::hasColumn('certificates', 'generated_by')) {
                $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete()->after('status');
            }
        });
    }

    public function down(): void
    {
    }
};