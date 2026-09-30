<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePasswordResetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('password_resets', function (Blueprint $table) {
            // BẮT BUỘC có khóa chính NGAY TRONG lệnh CREATE: MySQL của Aiven bật
            // sql_require_primary_key nên bảng không khóa chính bị từ chối tạo.
            // Không dùng string('email')->primary(): Laravel 8 thêm khóa chính đó
            // bằng một lệnh ALTER riêng SAU khi tạo bảng -> vẫn bị chặn. Cột id
            // tự tăng thì nằm ngay trong CREATE. Laravel chỉ ghi/đọc theo email
            // nên thêm cột id không ảnh hưởng gì.
            $table->id();
            $table->string('email')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('password_resets');
    }
}
