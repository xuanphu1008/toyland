<?php

namespace App\Console\Commands;

use App\Services\Orders\OrderWorkflow;
use Illuminate\Console\Command;

/**
 * php artisan orders:release-expired
 *
 * Hủy các đơn MoMo quá thời gian giữ hàng mà chưa thanh toán, trả hàng về kho.
 * Trên máy chủ có cron, lệnh chạy mỗi phút (app/Console/Kernel.php). Trên Render
 * gói miễn phí không có cron: việc này còn được gọi "khi cần" ở trang đặt hàng,
 * danh sách đơn... (xem OrderWorkflow::releaseExpired).
 */
class ReleaseExpiredOrdersCommand extends Command
{
    protected $signature = 'orders:release-expired';

    protected $description = 'Hủy đơn MoMo quá hạn giữ hàng chưa thanh toán và trả kho';

    public function handle(OrderWorkflow $workflow): int
    {
        $count = $workflow->releaseExpired(true);

        $this->info($count > 0 ? "Đã hủy {$count} đơn quá hạn thanh toán." : 'Không có đơn nào quá hạn.');

        return self::SUCCESS;
    }
}
