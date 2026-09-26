<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDetailFieldsToProductsTable extends Migration
{
    /**
     * Bổ sung các thuộc tính mà một web bán đồ chơi cần để khách chọn đúng món:
     * mã hàng, nhân vật/chủ đề, khoảng tuổi dạng SỐ (để lọc chính xác),
     * kỹ năng phát triển, số chi tiết, pin, bảo hành, kích thước, cân nặng...
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $columns = [
                // Mã hàng nội bộ, in trên hóa đơn & dùng để tra cứu nhanh
                'sku' => fn () => $table->string('sku', 40)->nullable(),

                // Nhân vật / chủ đề bé yêu thích: Marvel, Doraemon, Elsa, Pokémon...
                // KHÔNG đặt tên cột là `character` vì đó là từ khóa dành riêng của SQL Server.
                'character_name' => fn () => $table->string('character_name', 120)->nullable(),

                // Khoảng tuổi quy ra THÁNG để lọc chính xác ("3 tuổi" = 36 tháng).
                // Cột age_range dạng chữ vẫn giữ để hiển thị cho đẹp.
                'age_min_months' => fn () => $table->unsignedSmallInteger('age_min_months')->nullable(),
                'age_max_months' => fn () => $table->unsignedSmallInteger('age_max_months')->nullable(),

                // Kỹ năng bé rèn được: "Tư duy logic, Vận động tinh" (ngăn cách bằng dấu phẩy)
                'skills' => fn () => $table->string('skills', 255)->nullable(),

                'piece_count' => fn () => $table->unsignedInteger('piece_count')->nullable(),
                'battery' => fn () => $table->string('battery', 120)->nullable(),
                'warranty_months' => fn () => $table->unsignedSmallInteger('warranty_months')->nullable(),
                'dimensions' => fn () => $table->string('dimensions', 120)->nullable(),

                // Cân nặng (gram) - dùng luôn cho việc tính phí GHN thay vì gán cứng
                'weight_grams' => fn () => $table->unsignedInteger('weight_grams')->nullable(),

                'safety_note' => fn () => $table->string('safety_note', 255)->nullable(),
                'is_featured' => fn () => $table->boolean('is_featured')->default(false),
            ];

            foreach ($columns as $name => $definition) {
                if (! Schema::hasColumn('products', $name)) {
                    $definition();
                }
            }
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $columns = [
                'sku', 'character_name', 'age_min_months', 'age_max_months', 'skills',
                'piece_count', 'battery', 'warranty_months', 'dimensions',
                'weight_grams', 'safety_note', 'is_featured',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
