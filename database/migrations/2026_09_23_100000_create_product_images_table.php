<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * THƯ VIỆN ẢNH CHO SẢN PHẨM
 *
 * Cột `products.image` CŨ vẫn giữ nguyên làm ẢNH ĐẠI DIỆN - thứ hiện trên thẻ
 * sản phẩm, kết quả tìm kiếm, giỏ hàng, hóa đơn và email. Bảng này chỉ chứa
 * các ảnh PHỤ.
 *
 * Vì sao không dồn tất cả vào bảng này rồi bỏ cột cũ: mọi chỗ trong hệ thống
 * đang đọc `products.image` trực tiếp, đổi hết là một cuộc phẫu thuật lớn với
 * rủi ro cao mà giá trị thêm gần bằng không. Giữ cột cũ là đường an toàn.
 */
class CreateProductImagesTable extends Migration
{
    public function up()
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('product_id')->index();

            // Đường dẫn tương đối trong public/uploads, giống hệt products.image
            $table->string('path', 300);

            // Thứ tự hiển thị trong thư viện ảnh; số nhỏ đứng trước
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['product_id', 'sort_order']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_images');
    }
}
