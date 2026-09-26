<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCmsTables extends Migration
{
    public function up()
    {
        // Trang tĩnh: Về chúng tôi, Liên hệ, Chính sách đổi trả, Bảo mật...
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('content');
            $table->boolean('is_published')->default(true);
            $table->boolean('show_in_footer')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Bài viết / tin tức về đồ chơi
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('category')->default('Tin tức');
            $table->string('image')->nullable();
            $table->text('excerpt')->nullable();
            $table->text('content');
            $table->unsignedBigInteger('author_id')->nullable();
            $table->integer('views')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['is_published', 'published_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('posts');
        Schema::dropIfExists('pages');
    }
}
