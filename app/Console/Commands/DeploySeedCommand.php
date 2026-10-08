<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Command;

/**
 * php artisan toyland:deploy-seed
 *
 * Chạy mỗi lần container khởi động (docker/entrypoint.sh), nên phải AN TOÀN
 * KHI CHẠY LẠI:
 *  - CSDL còn trống (lần triển khai đầu)   -> nạp toàn bộ dữ liệu mẫu,
 *  - đã có dữ liệu nhưng chưa có admin     -> chỉ tạo admin (khi đã khai ADMIN_PASSWORD),
 *  - đã đủ cả                              -> không làm gì.
 * Không bao giờ ghi đè dữ liệu thật mà admin đã nhập trên web.
 */
class DeploySeedCommand extends Command
{
    protected $signature = 'toyland:deploy-seed';

    protected $description = 'Nạp dữ liệu mẫu nếu CSDL còn trống (dùng khi triển khai)';

    public function handle(): int
    {
        if (! Product::query()->exists()) {
            $this->info('CSDL còn trống -> nạp dữ liệu mẫu...');
            $this->call('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true]);

            return self::SUCCESS;
        }

        if (! User::where('role', 'admin')->exists()) {
            $this->info('Chưa có tài khoản admin -> tạo admin...');
            $this->call('db:seed', ['--class' => AdminUserSeeder::class, '--force' => true]);

            return self::SUCCESS;
        }

        $this->info('Đã có dữ liệu, bỏ qua bước nạp mẫu.');

        // Gộp sản phẩm mẫu trùng (chỉ làm gì đó ở lần deploy đầu tiên sau khi có tính năng
        // biến thể; các lần sau không còn gì để gộp nên chạy không ảnh hưởng).
        $this->call('products:merge-duplicates');

        return self::SUCCESS;
    }
}
