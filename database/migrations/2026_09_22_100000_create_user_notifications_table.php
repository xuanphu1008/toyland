<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * THÔNG BÁO TRONG ỨNG DỤNG
 *
 * Email có hai điểm yếu: khách không phải lúc nào cũng mở hộp thư, và ở đồ án
 * này SMTP còn hay hỏng (sai Mật khẩu ứng dụng Gmail là cả luồng thông báo tắt
 * ngấm). Thông báo trong ứng dụng thì không phụ thuộc gì bên ngoài - ghi vào
 * CSDL là chắc chắn tới nơi.
 *
 * VÌ SAO ĐẶT TÊN `user_notifications` CHỨ KHÔNG PHẢI `notifications`:
 * Laravel có sẵn hệ thống Notification dùng đúng bảng tên `notifications`
 * (tạo bằng `php artisan notifications:table`). Nếu sau này ai đó chạy lệnh đó
 * thì hai bảng đâm nhau. Đặt tên khác là tránh hẳn va chạm.
 */
class CreateUserNotificationsTable extends Migration
{
    public function up()
    {
        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id')->index();

            // order | order_status | birthday | coupon | review | system
            // Quyết định biểu tượng và màu hiển thị, xem UserNotification::TYPES
            $table->string('type', 30)->index();

            $table->string('title', 160);
            $table->string('body', 400)->nullable();

            // Bấm vào thông báo thì đi đâu. Lưu URL đã dựng sẵn thay vì tên route
            // + tham số, để thông báo cũ không vỡ khi route sau này đổi tên.
            $table->string('url', 400)->nullable();

            // null = chưa đọc. Dùng timestamp thay vì cờ boolean vì còn biết
            // được khách đọc lúc nào - dữ liệu có ích mà không tốn thêm cột.
            $table->timestamp('read_at')->nullable();

            $table->timestamps();

            // Truy vấn nóng nhất: "thông báo chưa đọc của user X, mới nhất trước"
            $table->index(['user_id', 'read_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_notifications');
    }
}
