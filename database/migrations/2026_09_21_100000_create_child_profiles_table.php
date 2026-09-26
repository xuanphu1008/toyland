<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HỒ SƠ CỦA BÉ
 *
 * Một tài khoản (bố mẹ) có thể khai nhiều bé. Từ ngày sinh của bé, hệ thống
 * tính ra độ tuổi theo THÁNG và dùng nó để gợi ý đồ chơi đúng lứa - thay vì
 * bắt bố mẹ tự nhớ "bé nhà mình hợp nhóm mấy tuổi".
 *
 * Giá trị lớn nhất nằm ở SỔ SINH NHẬT: biết ngày sinh thì biết trước hai tuần
 * rằng bé sắp tròn mấy tuổi, và gợi ý quà theo độ tuổi bé SẮP đạt tới chứ
 * không phải tuổi hiện tại. Mua quà sinh nhật mà gợi ý theo tuổi cũ là hụt
 * mất một bậc phát triển.
 */
class CreateChildProfilesTable extends Migration
{
    public function up()
    {
        Schema::create('child_profiles', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id')->index();

            $table->string('name', 60);

            // Ngày sinh là cột quan trọng nhất của bảng: tuổi và sổ sinh nhật
            // đều tính ra từ đây. Có index vì lệnh nhắc sinh nhật quét theo cột này.
            $table->date('birthday')->index();

            // 'boy' | 'girl' - để trống nghĩa là không phân biệt khi gợi ý
            $table->string('gender', 10)->nullable();

            // Biểu tượng đại diện, chọn từ danh sách emoji có sẵn.
            // Dùng emoji thay vì cho tải ảnh: không phải xử lý upload, không tốn
            // dung lượng, và bố mẹ chọn nhanh hơn nhiều.
            $table->string('avatar', 8)->nullable();

            // Nhân vật bé thích, lưu dạng danh sách ngăn bởi dấu phẩy.
            // CỐ Ý không tách thành bảng riêng: giá trị lấy từ đúng cột
            // products.character_name nên không cần bảng tra cứu, và số lượng
            // mỗi bé chỉ vài cái. Đổi lại phải viết THỐNG NHẤT tên nhân vật.
            $table->text('interests')->nullable();

            // Một danh mục bé thích nhất, dùng để cộng điểm khi gợi ý
            $table->unsignedBigInteger('favorite_category_id')->nullable();

            $table->string('note', 255)->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('child_profiles');
    }
}
