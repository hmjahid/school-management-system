<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            if (Schema::hasColumn('users', 'email') && Schema::hasColumn('users', 'role_id')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->unique(['email', 'role_id'], 'users_email_role_unique');
                });
            }

            if (Schema::hasColumn('users', 'phone') && Schema::hasColumn('users', 'role_id')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->unique(['phone', 'role_id'], 'users_phone_role_unique');
                });
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function () {
            if (Schema::hasColumn('users', 'phone') && Schema::hasColumn('users', 'role_id')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->dropUnique('users_phone_role_unique');
                });
            }

            if (Schema::hasColumn('users', 'email') && Schema::hasColumn('users', 'role_id')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->dropUnique('users_email_role_unique');
                });
            }
        });
    }
};
