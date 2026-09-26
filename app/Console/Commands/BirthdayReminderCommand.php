<?php

namespace App\Console\Commands;

use App\Mail\BirthdayReminderMail;
use App\Models\ChildProfile;
use App\Models\UserNotification;
use App\Services\GiftFinderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * NHẮC SINH NHẬT
 *
 *   php artisan toyland:birthday-reminder              # gửi thật
 *   php artisan toyland:birthday-reminder --dry-run    # chỉ in ra, KHÔNG gửi
 *   php artisan toyland:birthday-reminder --days=7     # đổi mốc nhắc
 *
 * Lệnh gửi cho những bé có sinh nhật ĐÚNG N ngày nữa, chứ không phải "trong
 * vòng N ngày". Nếu gửi cho cả khoảng thì mỗi ngày chạy lệnh là bố mẹ lại nhận
 * một email cho cùng một bé - phiền tới mức họ sẽ chặn địa chỉ của shop.
 *
 * Chạy tự động hằng ngày: xem app/Console/Kernel.php, và máy chủ cần bật
 * `php artisan schedule:work` (hoặc cron gọi `schedule:run` mỗi phút).
 */
class BirthdayReminderCommand extends Command
{
    protected $signature = 'toyland:birthday-reminder
                            {--days= : Nhắc trước bao nhiêu ngày (mặc định lấy ChildProfile::REMIND_DAYS)}
                            {--dry-run : Chỉ liệt kê, không gửi email}';

    protected $description = 'Gửi email nhắc bố mẹ trước sinh nhật của bé, kèm gợi ý quà';

    public function handle(GiftFinderService $giftFinder): int
    {
        $days = (int) ($this->option('days') ?: ChildProfile::REMIND_DAYS);
        $dry = (bool) $this->option('dry-run');

        $this->newLine();
        $this->line('<fg=white;bg=blue> NHẮC SINH NHẬT TOYLAND </>');
        $this->line('   Mốc nhắc: đúng ' . $days . ' ngày trước sinh nhật'
            . ($dry ? '  <fg=yellow>(chạy thử, không gửi)</>' : ''));
        $this->newLine();

        // Lọc ở PHP: so sánh ngày/tháng bất kể năm mỗi hệ CSDL viết một kiểu,
        // trong khi bảng hồ sơ bé vốn nhỏ. Xem thêm chú thích ở Admin\BirthdayController.
        $targets = ChildProfile::with('user')
            ->get()
            ->filter(fn (ChildProfile $child) => $child->days_until_birthday === $days)
            ->filter(fn (ChildProfile $child) => filled(optional($child->user)->email));

        if ($targets->isEmpty()) {
            $this->line('   <fg=yellow>Không có bé nào sinh nhật sau đúng ' . $days . ' ngày.</>');
            $this->newLine();

            return self::SUCCESS;
        }

        $sent = 0;
        $failed = 0;

        foreach ($targets as $child) {
            // Quà theo tuổi bé SẮP TRÒN, không phải tuổi hiện tại
            $gifts = $giftFinder->forChild($child, 4, $child->gift_age_months);

            $line = sprintf(
                '   %s %-16s tròn %2d tuổi  →  %s  (%d gợi ý)',
                $child->avatar ?: '🧒',
                $child->name,
                $child->turning_age,
                $child->user->email,
                $gifts->count()
            );

            if ($dry) {
                $this->line($line);
                $sent++;

                continue;
            }

            // Thông báo trong ứng dụng gửi TRƯỚC email: nó không phụ thuộc SMTP
            // nên dù email hỏng, bố mẹ vẫn thấy lời nhắc khi vào web.
            UserNotification::send(
                $child->user_id,
                'birthday',
                'Sinh nhật ' . $child->name . ' còn ' . $days . ' ngày nữa',
                'Bé sắp tròn ' . $child->turning_age . ' tuổi. Xem gợi ý quà đúng độ tuổi nhé!',
                route('children.birthdays')
            );

            try {
                Mail::to($child->user->email)->send(new BirthdayReminderMail($child, $gifts));
                $this->line('<fg=green>✔</>' . $line);
                $sent++;
            } catch (Throwable $e) {
                // Một email hỏng không được làm dừng cả lượt gửi
                $failed++;
                $this->line('<fg=red>✘</>' . $line);
                $this->line('     <fg=red>' . $e->getMessage() . '</>');
            }
        }

        $this->newLine();
        $this->line($dry
            ? "   Sẽ gửi cho <options=bold>{$sent}</> bé."
            : "   Đã gửi <fg=green;options=bold>{$sent}</> email"
                . ($failed ? ", <fg=red;options=bold>{$failed}</> lỗi" : '') . '.');

        if ($failed) {
            $this->line('   <fg=yellow>Email lỗi thường do MAIL_PASSWORD chưa phải Mật khẩu ứng dụng 16 ký tự của Gmail.</>');
        }

        $this->newLine();

        return self::SUCCESS;
    }
}
