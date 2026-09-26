<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng phiên đăng nhập (SESSION_DRIVER=database).
 *
 * Trên Render gói miễn phí, ổ đĩa bị xóa mỗi lần máy chủ ngủ dậy. Nếu để
 * session dạng file thì cứ 15 phút không ai vào là mọi người bị đăng xuất
 * và mất sạch giỏ hàng (giỏ hàng nằm trong session). Lưu vào MySQL thì còn.
 */
class CreateSessionsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('sessions')) {
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
