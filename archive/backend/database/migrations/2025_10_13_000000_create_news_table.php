<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('content');
            $table->string('image_url')->nullable();
            $table->string('category')->nullable();
            $table->boolean('is_published')->default(false);
            $table->boolean('is_event')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('event_date')->nullable();
            $table->string('event_location')->nullable();
            $table->string('author_name')->nullable();
            $table->string('author_avatar')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('news');
    }
};
