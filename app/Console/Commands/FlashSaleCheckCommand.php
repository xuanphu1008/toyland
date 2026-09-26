<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * SOI FLASH SALE — php artisan flashsale:check
 *
 * Khi một chương trình khuyến mãi "đã khai mà không chạy", nguyên nhân gần như
 * luôn nằm ở một trong bốn chỗ: múi giờ, giá khuyến mãi không rẻ hơn giá gốc,
 * chưa tới giờ bắt đầu, hoặc hết hàng. Lệnh này in thẳng cả bốn ra cùng lúc
 * nên không phải đoán.
 *
 * Bài học từ lần hỏng thật: ứng dụng chạy múi giờ UTC trong khi admin nhập giờ
 * Việt Nam, lệch 7 tiếng - đặt sale 14:00 thì 21:00 mới chạy. Nhìn giao diện
 * không tài nào thấy được, nhưng dòng "Giờ ứng dụng" ở đây thì lộ ngay.
 */
class FlashSaleCheckCommand extends Command
{
    protected $signature = 'flashsale:check {--all : In cả những món không khai khuyến mãi}';

    protected $description = 'Kiểm tra vì sao một chương trình flash sale chạy hoặc không chạy';

    public function handle(): int
    {
        $this->newLine();
        $this->line('<fg=white;bg=blue> SOI FLASH SALE </>');
        $this->newLine();

        $this->showClocks();
        $this->showProducts();

        return self::SUCCESS;
    }

    // ===================================================================

    /**
     * So ba cái đồng hồ với nhau. Lệch nhau là ra ngay nguyên nhân.
     */
    private function showClocks(): void
    {
        $this->line('<options=bold>1. Đồng hồ</>');

        $appTz = config('app.timezone');
        $now = now();

        $this->line('   Múi giờ ứng dụng : ' . $appTz);
        $this->line('   Giờ ứng dụng     : ' . $now->format('H:i:s d/m/Y'));
        $this->line('   Giờ máy chủ (PHP): ' . date('H:i:s d/m/Y') . ' (' . date_default_timezone_get() . ')');

        try {
            // Giờ của CSDL - nếu lệch hẳn với giờ ứng dụng thì các so sánh
            // thời gian chạy ở tầng SQL sẽ cho kết quả khác tầng PHP.
            $driver = DB::connection()->getDriverName();
            $sql = $driver === 'sqlsrv' ? 'SELECT GETDATE() as t' : 'SELECT NOW() as t';
            $dbNow = DB::selectOne($sql)->t ?? null;

            $this->line('   Giờ CSDL         : ' . ($dbNow ? date('H:i:s d/m/Y', strtotime($dbNow)) : '(không đọc được)'));
        } catch (\Throwable $e) {
            $this->line('   Giờ CSDL         : <fg=yellow>không đọc được (' . $e->getMessage() . ')</>');
        }

        if ($appTz !== 'Asia/Ho_Chi_Minh') {
            $this->newLine();
            $this->line('   <fg=red>✘ Múi giờ ứng dụng KHÔNG phải Asia/Ho_Chi_Minh.</>');
            $this->line('   <fg=yellow>  Ô nhập ngày giờ trên trình duyệt gửi chuỗi KHÔNG kèm múi giờ, nên</>');
            $this->line('   <fg=yellow>  admin gõ 14:00 mà ứng dụng chạy UTC thì phải đợi tới 21:00 giờ VN</>');
            $this->line('   <fg=yellow>  chương trình mới bắt đầu. Sửa trong config/app.php rồi chạy:</>');
            $this->line('   <fg=yellow>  php artisan config:clear</>');
        } else {
            $this->line('   <fg=green>✔ Múi giờ đúng với giờ Việt Nam.</>');
        }

        $this->newLine();
    }

    private function showProducts(): void
    {
        $this->line('<options=bold>2. Các sản phẩm có khai khuyến mãi</>');

        $query = Product::query()->orderBy('id');

        if (! $this->option('all')) {
            $query->whereNotNull('sale_price');
        }

        $products = $query->get();

        if ($products->isEmpty()) {
            $this->line('   <fg=yellow>Chưa món nào được khai giá khuyến mãi.</>');
            $this->line('   Vào Quản trị → Sản phẩm → sửa một món → khối "Giá sốc có hạn".');
            $this->newLine();

            return;
        }

        $rows = [];
        $running = 0;

        foreach ($products as $p) {
            $reason = $this->reason($p);

            if ($p->is_on_sale) {
                $running++;
            }

            $rows[] = [
                $p->id,
                mb_strimwidth($p->name, 0, 30, '…'),
                number_format($p->price, 0, ',', '.'),
                $p->sale_price ? number_format($p->sale_price, 0, ',', '.') : '—',
                optional($p->sale_starts_at)->format('H:i d/m') ?: 'ngay',
                optional($p->sale_ends_at)->format('H:i d/m') ?: 'vô hạn',
                (int) $p->quantity,
                $p->is_on_sale ? 'CÓ' : 'không',
                $reason,
            ];
        }

        $this->table(
            ['ID', 'Tên', 'Giá gốc', 'Giá sale', 'Bắt đầu', 'Kết thúc', 'Kho', 'Đang chạy', 'Lý do'],
            $rows
        );

        $this->line($running > 0
            ? "   <fg=green;options=bold>{$running} món đang chạy khuyến mãi.</>"
            : '   <fg=yellow>Không món nào đang chạy - xem cột "Lý do" ở trên.</>');

        $this->newLine();
    }

    /** Vì sao món này đang chạy hoặc không chạy - kiểm đúng thứ tự như accessor is_on_sale */
    private function reason(Product $p): string
    {
        if (! $p->sale_price) {
            return 'chưa khai giá sale';
        }

        if ($p->sale_price >= $p->price) {
            return 'giá sale KHÔNG rẻ hơn giá gốc';
        }

        if ($p->sale_starts_at && now()->lt($p->sale_starts_at)) {
            return 'chưa tới giờ (còn ' . now()->diffForHumans($p->sale_starts_at, ['syntax' => true]) . ')';
        }

        if ($p->sale_ends_at && now()->gte($p->sale_ends_at)) {
            return 'đã hết giờ';
        }

        if ($p->quantity <= 0) {
            // Vẫn giảm giá, nhưng không lên trang chủ vì scopeOnFlashSale lọc hết hàng
            return 'HẾT HÀNG - không lên trang chủ';
        }

        return 'đang chạy, giảm ' . $p->discount_percent . '%';
    }
}
