<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Các lệnh chạy MỖI NGÀY MỘT LẦN, khai một chỗ dùng cho cả hai cách chạy:
     *  - máy chủ có cron: schedule() bên dưới gọi đúng giờ,
     *  - Render gói miễn phí (không có cron): dịch vụ ngoài gọi /cron/daily,
     *    xem App\Http\Controllers\CronController.
     */
    public const DAILY_COMMANDS = [
        'toyland:birthday-reminder',
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Nhắc sinh nhật bé: chạy mỗi sáng 8h.
        // Muốn lịch này chạy thật thì máy chủ phải bật một trong hai:
        //   php artisan schedule:work         (chạy ngay trong terminal, hợp lúc dev)
        //   cron gọi `schedule:run` mỗi phút  (dùng khi lên máy chủ thật)
        // Không bật cũng không sao, vẫn gửi tay được:
        //   php artisan toyland:birthday-reminder
        // Trả kho cho đơn MoMo quá 30 phút chưa thanh toán
        $schedule->command('orders:release-expired')->everyMinute()->withoutOverlapping();

        foreach (self::DAILY_COMMANDS as $command) {
            $schedule->command($command)
                ->dailyAt('08:00')
                ->withoutOverlapping();
        }
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
