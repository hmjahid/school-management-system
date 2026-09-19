<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            if (! Schema::hasColumn('testimonials', 'name')) {
                $table->string('name')->nullable()->after('id');
            }
            if (! Schema::hasColumn('testimonials', 'testimonial_type')) {
                $table->string('testimonial_type', 50)->nullable()->after('name');
            }
            if (! Schema::hasColumn('testimonials', 'testimonial_number')) {
                $table->string('testimonial_number', 50)->unique()->nullable()->after('testimonial_type');
            }
            if (! Schema::hasColumn('testimonials', 'issue_date')) {
                $table->date('issue_date')->nullable()->after('testimonial_number');
            }
            if (! Schema::hasColumn('testimonials', 'status')) {
                $table->string('status', 20)->default('draft')->after('issue_date');
            }
            if (! Schema::hasColumn('testimonials', 'generated_by')) {
                $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete()->after('status');
            }
            if (! Schema::hasColumn('testimonials', 'body')) {
                $table->json('body')->nullable()->after('content');
            }
            if (! Schema::hasColumn('testimonials', 'details')) {
                $table->json('details')->nullable()->after('sort_order');
            }
        });
    }

    public function down(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->dropColumn([
                'name', 'testimonial_type', 'testimonial_number', 'issue_date',
                'status', 'generated_by', 'body', 'details',
            ]);
        });
    }
};
