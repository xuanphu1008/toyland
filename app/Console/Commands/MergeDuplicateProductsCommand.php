<?php

namespace App\Console\Commands;

use App\Services\ProductMerger;
use Illuminate\Console\Command;

/**
 * php artisan products:merge-duplicates
 *
 * Gộp các cặp sản phẩm mẫu trùng nhau (thành 1 sản phẩm, hoặc thành sản phẩm có
 * phân loại). Chạy lại bao nhiêu lần cũng an toàn - xem App\Services\ProductMerger.
 */
class MergeDuplicateProductsCommand extends Command
{
    protected $signature = 'products:merge-duplicates';

    protected $description = 'Gộp các cặp sản phẩm mẫu trùng nhau (thành 1 sản phẩm hoặc thành biến thể)';

    public function handle(): int
    {
        $r = (new ProductMerger())->run();
        $this->info("Đã gộp {$r['duplicates']} sản phẩm trùng và {$r['variants']} sản phẩm thành phân loại.");

        return self::SUCCESS;
    }
}
