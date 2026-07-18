<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('website_contents')) {
            return;
        }

        Schema::table('website_contents', function (Blueprint $table) {
            if (! Schema::hasColumn('website_contents', 'title_en')) {
                $table->string('title_en')->nullable()->after('title');
            }
            if (! Schema::hasColumn('website_contents', 'title_bn')) {
                $table->string('title_bn')->nullable()->after('title_en');
            }
            if (! Schema::hasColumn('website_contents', 'meta_description_en')) {
                $table->string('meta_description_en', 500)->nullable()->after('meta_description');
            }
            if (! Schema::hasColumn('website_contents', 'meta_description_bn')) {
                $table->string('meta_description_bn', 500)->nullable()->after('meta_description_en');
            }
            if (! Schema::hasColumn('website_contents', 'content_en')) {
                $table->json('content_en')->nullable()->after('content');
            }
            if (! Schema::hasColumn('website_contents', 'content_bn')) {
                $table->json('content_bn')->nullable()->after('content_en');
            }
            if (! Schema::hasColumn('website_contents', 'cms_input_mode')) {
                $table->string('cms_input_mode', 16)->default('json')->after('content_bn');
            }
        });

        foreach (DB::table('website_contents')->orderBy('id')->cursor() as $row) {
            $update = [];

            if (isset($row->content_en) && $row->content_en === null && isset($row->content) && $row->content !== null && $row->content !== '') {
                $decoded = is_string($row->content) ? json_decode($row->content, true) : $row->content;
                if (is_array($decoded)) {
                    $payload = json_encode($decoded, JSON_UNESCAPED_UNICODE);
                    $update['content_en'] = $payload;
                    $update['content_bn'] = $payload;
                }
            }

            if (isset($row->title_en) && $row->title_en === null && isset($row->title) && $row->title !== null) {
                $update['title_en'] = $row->title;
                $update['title_bn'] = $row->title;
            }

            if (isset($row->meta_description_en) && $row->meta_description_en === null && isset($row->meta_description) && $row->meta_description !== null) {
                $update['meta_description_en'] = $row->meta_description;
                $update['meta_description_bn'] = $row->meta_description;
            }

            if ($update !== []) {
                DB::table('website_contents')->where('id', $row->id)->update($update);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('website_contents')) {
            return;
        }

        Schema::table('website_contents', function (Blueprint $table) {
            foreach (['title_en', 'title_bn', 'meta_description_en', 'meta_description_bn', 'content_en', 'content_bn', 'cms_input_mode'] as $col) {
                if (Schema::hasColumn('website_contents', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
