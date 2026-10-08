<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Biến thể sản phẩm (màu, kích cỡ, phiên bản...).
 *
 * Thiết kế:
 *  - products.quantity VẪN LÀ TỔNG tồn kho (= tổng các biến thể) nên danh sách,
 *    bộ lọc, chatbot, thống kê... không cần biết đến biến thể.
 *  - Giá biến thể = giá hiệu lực của sản phẩm + price_adjust (có thể âm). Nhờ vậy
 *    flash sale vẫn áp cho CẢ sản phẩm, mọi biến thể cùng giảm.
 *  - order_items lưu variant_id (null với đơn cũ) và variant_name chụp lại tại
 *    thời điểm mua để đơn cũ không đổi khi admin sửa tên biến thể.
 */
class CreateProductVariantsTable extends Migration
{
    public function up()
    {
        // Aiven MySQL bắt buộc có khóa chính ngay trong lệnh tạo bảng: id() đảm bảo điều đó.
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('sku', 60)->nullable();
            $table->integer('price_adjust')->default(0);
            $table->unsignedInteger('quantity')->default(0);
            $table->string('image')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'sort_order']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('variant_id')->nullable()->after('product_id');
            $table->string('variant_name', 120)->nullable()->after('variant_id');
        });
    }

    public function down()
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['variant_id', 'variant_name']);
        });

        Schema::dropIfExists('product_variants');
    }
}
