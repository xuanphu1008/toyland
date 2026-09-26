<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductViewsTable extends Migration
{
    /**
     * Lịch sử duyệt sản phẩm - dùng cho mục "Đã xem gần đây"
     * và làm dữ liệu đầu vào cho gợi ý sản phẩm.
     */
    public function up()
    {
        Schema::create('product_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('product_id');
            $table->timestamp('viewed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'product_id']);
            $table->index('viewed_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_views');
    }
}
