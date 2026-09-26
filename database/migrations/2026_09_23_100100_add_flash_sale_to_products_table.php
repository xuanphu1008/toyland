<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FLASH SALE - GIÁ SỐC THEO KHUNG GIỜ
 *
 * Ba cột, tất cả đều cho phép null: không khai gì thì sản phẩm bán giá thường,
 * y như trước khi có tính năng này.
 *
 * VÌ SAO KHÔNG SỬA THẲNG products.price:
 * Sửa giá gốc là mất luôn con số cũ, hết sale không biết trả về bao nhiêu, và
 * cũng không hiện được "giá gạch ngang" để khách thấy mình đang lời bao nhiêu.
 * Giữ hai cột riêng thì giá gốc còn nguyên, bật/tắt sale chỉ là đổi mốc thời gian.
 *
 * VÌ SAO CÓ CẢ NGÀY BẮT ĐẦU LẪN KẾT THÚC:
 * Admin hẹn trước chương trình cho cuối tuần rồi đi nghỉ; hệ thống tự bật đúng
 * giờ và tự tắt, không cần ai ngồi canh. Không có cái đếm ngược nào đáng tin
 * nếu thời điểm kết thúc không nằm trong CSDL.
 */
class AddFlashSaleToProductsTable extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            // Giá bán trong thời gian khuyến mãi. Phải NHỎ HƠN price - ràng buộc
            // này kiểm ở tầng ứng dụng (ProductController::validateProduct).
            $table->unsignedBigInteger('sale_price')->nullable()->after('price');

            $table->timestamp('sale_starts_at')->nullable()->after('sale_price');
            $table->timestamp('sale_ends_at')->nullable()->after('sale_starts_at');

            // Truy vấn nóng: "món nào đang sale" - lọc theo mốc kết thúc
            $table->index('sale_ends_at');
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            // SQL Server bắt gỡ index trước khi xóa cột có index
            $table->dropIndex(['sale_ends_at']);
            $table->dropColumn(['sale_price', 'sale_starts_at', 'sale_ends_at']);
        });
    }
}
