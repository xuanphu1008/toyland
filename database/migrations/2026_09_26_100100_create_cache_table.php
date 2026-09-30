<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng cache (CACHE_DRIVER=database).
 *
 * Cần cho hai việc phải "nhớ" qua các lần máy chủ khởi động lại:
 *  - chống chạy trùng việc hằng ngày (nhắc sinh nhật chỉ gửi 1 lần/ngày),
 *  - giới hạn gửi lại email xác thực (1 lần / 10 phút / tài khoản).
 *
 * Khác mẫu gốc của Laravel (key là khóa chính): MySQL trên Aiven bật
 * sql_require_primary_key, còn Laravel 8 lại thêm khóa chính kiểu chuỗi bằng
 * lệnh ALTER riêng SAU khi tạo bảng -> bị chặn. Nên dùng cột id tự tăng làm
 * khóa chính (nằm ngay trong CREATE) + ràng buộc UNIQUE cho key. Laravel chỉ
 * insert/update/delete theo key, và UNIQUE vẫn giữ tính nguyên tử cho add()/lock.
 */
class CreateCacheTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('cache')) {
            Schema::create('cache', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->mediumText('value');
                $table->integer('expiration');
            });
        }

        if (! Schema::hasTable('cache_locks')) {
            Schema::create('cache_locks', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->string('owner');
                $table->integer('expiration');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
    }
}
