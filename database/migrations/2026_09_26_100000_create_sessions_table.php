<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng phiên đăng nhập (SESSION_DRIVER=database).
 *
 * Trên Render gói miễn phí, ổ đĩa bị xóa mỗi lần máy chủ ngủ dậy. Nếu để
 * session dạng file thì cứ 15 phút không ai vào là mọi người bị đăng xuất
 * và mất sạch giỏ hàng (giỏ hàng nằm trong session). Lưu vào MySQL thì còn.
 *
 * Với MySQL phải viết tay lệnh CREATE: Aiven bật sql_require_primary_key,
 * mà Laravel 8 thêm khóa chính kiểu chuỗi (cột id) bằng lệnh ALTER riêng SAU
 * khi tạo bảng -> bị chặn. Viết tay thì khóa chính nằm ngay trong CREATE.
 * Cột id ở đây là mã phiên do Laravel sinh, không thể thay bằng số tự tăng.
 */
class CreateSessionsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('sessions')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('CREATE TABLE `sessions` (
                `id` VARCHAR(255) NOT NULL,
                `user_id` BIGINT UNSIGNED NULL,
                `ip_address` VARCHAR(45) NULL,
                `user_agent` TEXT NULL,
                `payload` LONGTEXT NOT NULL,
                `last_activity` INT NOT NULL,
                PRIMARY KEY (`id`),
                INDEX `sessions_user_id_index` (`user_id`),
                INDEX `sessions_last_activity_index` (`last_activity`)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

            return;
        }

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sessions');
    }
}
