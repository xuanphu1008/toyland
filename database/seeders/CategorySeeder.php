<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder chỉ tạo DANH MỤC (không đụng tới bảng products).
 *
 * Lưu ý lịch sử: file này trước đây khai báo nhầm `class DatabaseSeeder`,
 * trùng tên với DatabaseSeeder.php nên gây lỗi autoload.
 *
 * Từ khi có cây danh mục 2 cấp, phần tạo danh mục đã được gom vào
 * ToyCatalogSeeder để cha - con luôn khớp nhau. File này giữ lại như một
 * lối tắt: chỉ nạp danh mục, không nạp sản phẩm.
 *
 *   php artisan db:seed --class=CategorySeeder
 */
class CategorySeeder extends Seeder
{
    public function run()
    {
        // Gọi lại phần tạo danh mục của ToyCatalogSeeder qua Reflection
        // để tránh chép lặp cây danh mục ở hai nơi (dễ lệch nhau về sau).
        $seeder = new ToyCatalogSeeder();

        $method = new \ReflectionMethod($seeder, 'seedCategories');
        $method->setAccessible(true);
        $method->invoke($seeder);

        $this->command->info('Đã nạp xong cây danh mục (không nạp sản phẩm).');
    }
}
