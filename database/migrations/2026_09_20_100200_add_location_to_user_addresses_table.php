<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLocationToUserAddressesTable extends Migration
{
    /**
     * NGUYÊN NHÂN GỐC của lỗi "đặt lại đơn thì mất địa chỉ, không tính được phí ship":
     * sổ địa chỉ trước đây chỉ lưu name / phone / address (một dòng chữ).
     * GHN lại cần DistrictID + WardCode dạng SỐ mới tính được cước.
     * Vì vậy địa chỉ lưu lại không đủ dữ liệu, mỗi lần đặt hàng khách phải chọn lại
     * Tỉnh/Quận/Phường từ đầu. Migration này lưu luôn cả mã lẫn tên để:
     *   - tính phí ship ngay khi chọn địa chỉ cũ;
     *   - hiển thị đầy đủ "Phường X, Quận Y, Tỉnh Z" ở trang hồ sơ.
     */
    public function up()
    {
        Schema::table('user_addresses', function (Blueprint $table) {
            $columns = [
                'province_id' => fn () => $table->unsignedInteger('province_id')->nullable(),
                'province_name' => fn () => $table->string('province_name', 120)->nullable(),
                'district_id' => fn () => $table->unsignedInteger('district_id')->nullable(),
                'district_name' => fn () => $table->string('district_name', 120)->nullable(),
                'ward_code' => fn () => $table->string('ward_code', 30)->nullable(),
                'ward_name' => fn () => $table->string('ward_name', 120)->nullable(),
            ];

            foreach ($columns as $name => $definition) {
                if (! Schema::hasColumn('user_addresses', $name)) {
                    $definition();
                }
            }
        });
    }

    public function down()
    {
        Schema::table('user_addresses', function (Blueprint $table) {
            foreach (['province_id', 'province_name', 'district_id', 'district_name', 'ward_code', 'ward_name'] as $column) {
                if (Schema::hasColumn('user_addresses', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
