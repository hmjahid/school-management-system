<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('website_contents')) {
            return;
        }

        Schema::table('website_contents', function (Blueprint $table) {
            if (! Schema::hasColumn('website_contents', 'page')) {
                $table->string('page')->unique()->after('id');
            }
            if (! Schema::hasColumn('website_contents', 'title')) {
                $table->string('title')->nullable()->after('page');
            }
            if (! Schema::hasColumn('website_contents', 'content')) {
                $table->json('content')->nullable()->after('title');
            }
            if (! Schema::hasColumn('website_contents', 'meta_description')) {
                $table->string('meta_description', 500)->nullable()->after('content');
            }
            if (! Schema::hasColumn('website_contents', 'meta_keywords')) {
                $table->string('meta_keywords', 500)->nullable()->after('meta_description');
            }
            if (! Schema::hasColumn('website_contents', 'images')) {
                $table->json('images')->nullable()->after('meta_keywords');
            }
            if (! Schema::hasColumn('website_contents', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('images');
            }
            if (! Schema::hasColumn('website_contents', 'settings')) {
                $table->json('settings')->nullable()->after('is_active');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('website_contents')) {
            return;
        }

        Schema::table('website_contents', function (Blueprint $table) {
            $cols = ['page', 'title', 'content', 'meta_description', 'meta_keywords', 'images', 'is_active', 'settings'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('website_contents', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
