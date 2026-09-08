<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fees')) {
            return;
        }

        if (! Schema::hasColumn('fees', 'class_id') && Schema::hasColumn('fees', 'school_class_id')) {
            Schema::table('fees', function (Blueprint $table) {
                $table->unsignedBigInteger('class_id')->nullable();
            });

            DB::statement('UPDATE fees SET class_id = school_class_id WHERE school_class_id IS NOT NULL');

            Schema::table('fees', function (Blueprint $table) {
                $table->dropConstrainedForeignId('school_class_id');
            });

            Schema::table('fees', function (Blueprint $table) {
                $table->foreign('class_id')->references('id')->on('school_classes')->cascadeOnDelete();
            });
        }

        if (Schema::hasColumn('fees', 'type') && ! Schema::hasColumn('fees', 'fee_type')) {
            Schema::table('fees', function (Blueprint $table) {
                $table->renameColumn('type', 'fee_type');
            });
        }

        if (! Schema::hasColumn('fees', 'fee_type')) {
            Schema::table('fees', function (Blueprint $table) {
                $table->string('fee_type')->default('other');
            });
        }

        Schema::table('fees', function (Blueprint $table) {
            if (! Schema::hasColumn('fees', 'section_id')) {
                $table->foreignId('section_id')->nullable()->constrained('sections')->nullOnDelete();
            }
            if (! Schema::hasColumn('fees', 'student_id')) {
                $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            }
            if (! Schema::hasColumn('fees', 'frequency')) {
                $table->string('frequency')->default('one_time');
            }
            if (! Schema::hasColumn('fees', 'start_date')) {
                $table->date('start_date')->nullable();
            }
            if (! Schema::hasColumn('fees', 'end_date')) {
                $table->date('end_date')->nullable();
            }
            if (! Schema::hasColumn('fees', 'fine_amount')) {
                $table->decimal('fine_amount', 10, 2)->default(0);
            }
            if (! Schema::hasColumn('fees', 'fine_type')) {
                $table->string('fine_type')->default('fixed');
            }
            if (! Schema::hasColumn('fees', 'fine_grace_days')) {
                $table->integer('fine_grace_days')->default(0);
            }
            if (! Schema::hasColumn('fees', 'discount_amount')) {
                $table->decimal('discount_amount', 10, 2)->default(0);
            }
            if (! Schema::hasColumn('fees', 'discount_type')) {
                $table->string('discount_type')->default('fixed');
            }
            if (! Schema::hasColumn('fees', 'status')) {
                $table->string('status')->default('active');
            }
            if (! Schema::hasColumn('fees', 'metadata')) {
                $table->json('metadata')->nullable();
            }
            if (! Schema::hasColumn('fees', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            }
        });

        $firstUserId = DB::table('users')->orderBy('id')->value('id');
        if ($firstUserId) {
            DB::table('fees')->whereNull('created_by')->update(['created_by' => $firstUserId]);
        }
    }

    public function down(): void
    {
        // Non-reversible data migration; leave schema as-is.
    }
};
