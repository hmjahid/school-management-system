<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The original `2025_10_05_033158_create_teachers_table.php` migration created
 * a barebones teachers table (id, user_id, qualification, subjects, timestamps),
 * and the follow-up `2025_10_11_000002_create_teachers_table.php` was guarded
 * with `if (Schema::hasTable('teachers')) return;`, so its rich column set
 * was never applied. The TeacherController writes all those fields, which is
 * why the create form throws "table teachers has no column named employee_id".
 *
 * The guardians table has the same problem — only id/user_id/timestamps were
 * created, but the form collects ~20 parent fields.
 *
 * This migration is idempotent: it adds any missing column to either table
 * using Schema::hasColumn guards, so it works on databases that already
 * have the rich schema (fresh installs after the 2025_10_11_*.php
 * migrations were corrected) and on databases still in the half-built
 * state (most local/dev installs).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            if (!Schema::hasColumn('teachers', 'employee_id')) {
                $table->string('employee_id')->nullable()->unique()->after('user_id');
            }
            if (!Schema::hasColumn('teachers', 'gender')) {
                $table->string('gender', 10)->nullable();
            }
            if (!Schema::hasColumn('teachers', 'blood_group')) {
                $table->string('blood_group', 5)->nullable();
            }
            if (!Schema::hasColumn('teachers', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable();
            }
            if (!Schema::hasColumn('teachers', 'religion')) {
                $table->string('religion', 50)->nullable();
            }
            if (!Schema::hasColumn('teachers', 'nationality')) {
                $table->string('nationality', 100)->default('Bangladeshi');
            }
            if (!Schema::hasColumn('teachers', 'phone')) {
                $table->string('phone', 20)->nullable();
            }
            if (!Schema::hasColumn('teachers', 'emergency_contact')) {
                $table->string('emergency_contact', 20)->nullable();
            }
            if (!Schema::hasColumn('teachers', 'present_address')) {
                $table->text('present_address')->nullable();
            }
            if (!Schema::hasColumn('teachers', 'permanent_address')) {
                $table->text('permanent_address')->nullable();
            }
            if (!Schema::hasColumn('teachers', 'city')) {
                $table->string('city', 100)->nullable();
            }
            if (!Schema::hasColumn('teachers', 'state')) {
                $table->string('state', 100)->nullable();
            }
            if (!Schema::hasColumn('teachers', 'zip_code')) {
                $table->string('zip_code', 20)->nullable();
            }
            if (!Schema::hasColumn('teachers', 'country')) {
                $table->string('country', 100)->default('Bangladesh');
            }
            if (!Schema::hasColumn('teachers', 'joining_date')) {
                $table->date('joining_date')->nullable();
            }
            if (!Schema::hasColumn('teachers', 'leaving_date')) {
                $table->date('leaving_date')->nullable();
            }
            if (!Schema::hasColumn('teachers', 'status')) {
                $table->string('status', 20)->default('active');
            }
            if (!Schema::hasColumn('teachers', 'bank_name')) {
                $table->string('bank_name', 100)->nullable();
            }
            if (!Schema::hasColumn('teachers', 'bank_account_number')) {
                $table->string('bank_account_number', 50)->nullable();
            }
            if (!Schema::hasColumn('teachers', 'bank_branch')) {
                $table->string('bank_branch', 100)->nullable();
            }
            if (!Schema::hasColumn('teachers', 'salary')) {
                $table->decimal('salary', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('teachers', 'salary_type')) {
                $table->string('salary_type', 20)->default('monthly');
            }
            if (!Schema::hasColumn('teachers', 'nid_number')) {
                $table->string('nid_number', 50)->nullable();
            }
            if (!Schema::hasColumn('teachers', 'passport_number')) {
                $table->string('passport_number', 50)->nullable();
            }
            if (!Schema::hasColumn('teachers', 'driving_license')) {
                $table->string('driving_license', 50)->nullable();
            }
            if (!Schema::hasColumn('teachers', 'notes')) {
                $table->text('notes')->nullable();
            }
            if (!Schema::hasColumn('teachers', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('guardians', function (Blueprint $table) {
            if (!Schema::hasColumn('guardians', 'phone')) {
                $table->string('phone', 20)->nullable();
            }
            if (!Schema::hasColumn('guardians', 'occupation')) {
                $table->string('occupation', 100)->nullable();
            }
            if (!Schema::hasColumn('guardians', 'company')) {
                $table->string('company', 100)->nullable();
            }
            if (!Schema::hasColumn('guardians', 'nid_number')) {
                $table->string('nid_number', 50)->nullable();
            }
            if (!Schema::hasColumn('guardians', 'passport_number')) {
                $table->string('passport_number', 50)->nullable();
            }
            if (!Schema::hasColumn('guardians', 'driving_license')) {
                $table->string('driving_license', 50)->nullable();
            }
            if (!Schema::hasColumn('guardians', 'nationality')) {
                $table->string('nationality', 100)->default('Bangladeshi');
            }
            if (!Schema::hasColumn('guardians', 'religion')) {
                $table->string('religion', 50)->nullable();
            }
            if (!Schema::hasColumn('guardians', 'blood_group')) {
                $table->string('blood_group', 5)->nullable();
            }
            if (!Schema::hasColumn('guardians', 'present_address')) {
                $table->text('present_address')->nullable();
            }
            if (!Schema::hasColumn('guardians', 'permanent_address')) {
                $table->text('permanent_address')->nullable();
            }
            if (!Schema::hasColumn('guardians', 'city')) {
                $table->string('city', 100)->nullable();
            }
            if (!Schema::hasColumn('guardians', 'state')) {
                $table->string('state', 100)->nullable();
            }
            if (!Schema::hasColumn('guardians', 'zip_code')) {
                $table->string('zip_code', 20)->nullable();
            }
            if (!Schema::hasColumn('guardians', 'country')) {
                $table->string('country', 100)->default('Bangladesh');
            }
            if (!Schema::hasColumn('guardians', 'office_phone')) {
                $table->string('office_phone', 20)->nullable();
            }
            if (!Schema::hasColumn('guardians', 'emergency_contact')) {
                $table->string('emergency_contact', 20)->nullable();
            }
            if (!Schema::hasColumn('guardians', 'relationship')) {
                $table->string('relationship', 50)->nullable();
            }
            if (!Schema::hasColumn('guardians', 'is_primary')) {
                $table->boolean('is_primary')->default(false);
            }
            if (!Schema::hasColumn('guardians', 'monthly_income')) {
                $table->decimal('monthly_income', 12, 2)->nullable();
            }
            if (!Schema::hasColumn('guardians', 'education_level')) {
                $table->string('education_level', 100)->nullable();
            }
            if (!Schema::hasColumn('guardians', 'notes')) {
                $table->text('notes')->nullable();
            }
        });
    }

    public function down(): void
    {
        // No-op: rolling back would drop data and we don't know whether
        // these columns were created by this migration or the original.
        // Re-running `php artisan migrate:fresh` is the supported rollback.
    }
};
