<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReviewsTable extends Migration
{
    /**
     * Hệ thống phản hồi & đánh giá: khách chỉ được đánh giá sản phẩm
     * sau khi có đơn hàng chứa sản phẩm đó đã giao thành công.
     */
    public function up()
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            // Đơn hàng làm bằng chứng đã mua. Dùng 'no action' để tránh lỗi
            // multiple cascade paths của SQL Server (orders -> users đã cascade rồi).
            $table->unsignedBigInteger('order_id')->nullable();

            $table->unsignedTinyInteger('rating'); // 1..5 sao
            $table->text('comment')->nullable();

            // Admin có thể ẩn đánh giá vi phạm mà không xóa hẳn
            $table->boolean('is_visible')->default(true);

            // Phản hồi của shop cho đánh giá này
            $table->text('reply')->nullable();
            $table->timestamp('replied_at')->nullable();

            $table->timestamps();

            // Mỗi khách chỉ đánh giá 1 lần cho 1 sản phẩm (sửa lại được, không tạo thêm)
            $table->unique(['user_id', 'product_id']);
            $table->index(['product_id', 'is_visible']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('reviews');
    }
}
