<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatbotTables extends Migration
{
    /**
     * Lưu lại toàn bộ hội thoại với chatbot.
     *
     * Không chỉ để "có dữ liệu cho đẹp": trang quản trị đọc bảng này để lọc ra
     * những câu bot KHÔNG HIỂU (intent = 'unknown'). Đó chính là danh sách việc
     * cần làm - admin xem câu nào khách hay hỏi mà bot chịu thua thì bổ sung
     * vào mục Câu hỏi thường gặp, lần sau bot tự trả lời được.
     */
    public function up()
    {
        Schema::create('chat_conversations', function (Blueprint $table) {
            $table->id();

            // Khách vãng lai (chưa đăng nhập) cũng chat được -> user_id cho phép null
            $table->unsignedBigInteger('user_id')->nullable()->index();

            // Định danh phiên trình duyệt, để gom tin nhắn của khách vãng lai vào một hội thoại
            $table->string('session_id', 100)->index();

            $table->unsignedInteger('message_count')->default(0);

            // Đã phải chuyển sang nhân viên thật chưa - đo hiệu quả của bot
            $table->boolean('handed_to_human')->default(false);

            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();

            // CỐ Ý không đặt khóa ngoại cascade: SQL Server rất dễ báo lỗi
            // "multiple cascade paths" khi bảng đã có nhiều quan hệ. Ràng buộc
            // xử lý ở tầng ứng dụng (xóa hội thoại thì xóa tin nhắn kèm theo).
            $table->unsignedBigInteger('conversation_id')->index();

            // 'user' = khách gõ, 'bot' = bot trả lời
            $table->string('role', 10);

            $table->text('content');

            // Ý định bot đoán được và độ tin cậy - phục vụ thống kê
            $table->string('intent', 40)->nullable()->index();
            $table->decimal('confidence', 5, 4)->nullable();

            // Thực thể bóc tách được + sản phẩm đã gợi ý, lưu dạng JSON để xem lại
            $table->text('meta')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_conversations');
    }
}
