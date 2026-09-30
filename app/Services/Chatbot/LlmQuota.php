<?php

namespace App\Services\Chatbot;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * HẠN MỨC GỌI AI - giữ cho web không bao giờ vượt hạn mức gói miễn phí.
 *
 * Google tính hạn mức theo PROJECT (không theo từng API key) và chặn bằng lỗi
 * 429 khi vượt. Thay vì để khách gặp lỗi, mình tự đếm và tự dừng TRƯỚC khi
 * chạm trần, rồi trả lời bằng bot nội bộ.
 *
 * Bốn lớp, cấu hình trong config/services.php (chatbot.*):
 *  - Mỗi phút toàn hệ thống   (mặc định 4 - trần gói free chỉ khoảng 5)
 *  - Mỗi ngày toàn hệ thống   (mặc định 18 - trần gói free có model chỉ 20/ngày)
 *  - Mỗi ngày cho MỘT khách   (mặc định 5 - một người không thể "ăn" hết hạn mức)
 *  - "Cầu dao": Google báo 429/403 thì ngừng gọi một lúc thay vì gọi tiếp vô ích
 *
 * Ngày được tính theo giờ Thái Bình Dương (America/Los_Angeles) - đúng mốc
 * Google đặt lại hạn mức ngày (nửa đêm giờ Pacific = 14h-15h giờ Việt Nam).
 *
 * Bộ đếm lưu trong cache; trên Render là cache dạng database (MySQL) nên còn
 * nguyên sau mỗi lần máy chủ ngủ dậy.
 */
class LlmQuota
{
    public const DENY_COOLDOWN = 'cooldown';

    public const DENY_MINUTE = 'minute';

    public const DENY_DAY = 'day';

    public const DENY_CLIENT = 'client';

    /**
     * Còn được gọi AI không? Trả null nếu được, hoặc lý do bị từ chối.
     * Chỉ KIỂM TRA, chưa trừ lượt - trừ lượt bằng hit() ngay trước khi gọi thật.
     */
    public function deny(string $clientKey): ?string
    {
        if ($this->cooldownSeconds() > 0) {
            return self::DENY_COOLDOWN;
        }

        if ($this->count($this->minuteKey()) >= $this->limit('rpm', 4)) {
            return self::DENY_MINUTE;
        }

        if ($this->count($this->dayKey()) >= $this->limit('rpd', 18)) {
            return self::DENY_DAY;
        }

        if ($this->count($this->clientKey($clientKey)) >= $this->limit('per_client_day', 5)) {
            return self::DENY_CLIENT;
        }

        return null;
    }

    /** Trừ một lượt cho cả ba bộ đếm */
    public function hit(string $clientKey): void
    {
        $this->bump($this->minuteKey(), 120);
        $this->bump($this->dayKey(), 60 * 60 * 26);
        $this->bump($this->clientKey($clientKey), 60 * 60 * 26);
    }

    /** Ngắt cầu dao: ngừng gọi AI trong $seconds giây */
    public function tripCooldown(int $seconds, string $reason): void
    {
        $seconds = max(10, min($seconds, 60 * 60 * 24));

        Cache::put('chatbot:llm:cooldown_until', now()->addSeconds($seconds)->timestamp, $seconds);
        $this->recordError($reason);
    }

    public function cooldownSeconds(): int
    {
        $until = (int) Cache::get('chatbot:llm:cooldown_until', 0);

        return max(0, $until - now()->timestamp);
    }

    /** Số giây tới nửa đêm giờ Pacific - lúc Google đặt lại hạn mức ngày */
    public function secondsUntilDailyReset(): int
    {
        $now = Carbon::now('America/Los_Angeles');

        return max(60, $now->copy()->addDay()->startOfDay()->diffInSeconds($now));
    }

    /** Ghi lại lỗi gần nhất (đã làm sạch, không chứa API key) để admin xem */
    public function recordError(string $message): void
    {
        Cache::put('chatbot:llm:last_error', [
            'message' => $message,
            'at' => now()->toDateTimeString(),
        ], 60 * 60 * 24 * 3);
    }

    /** Số liệu cho trang quản trị */
    public function stats(): array
    {
        return [
            'today' => $this->count($this->dayKey()),
            'day_limit' => $this->limit('rpd', 18),
            'minute' => $this->count($this->minuteKey()),
            'minute_limit' => $this->limit('rpm', 4),
            'per_client_limit' => $this->limit('per_client_day', 5),
            'cooldown' => $this->cooldownSeconds(),
            'last_error' => Cache::get('chatbot:llm:last_error'),
            'reset_in' => $this->secondsUntilDailyReset(),
        ];
    }

    // ===================================================================

    private function limit(string $key, int $default): int
    {
        return max(0, (int) config('services.chatbot.' . $key, $default));
    }

    private function count(string $key): int
    {
        return (int) Cache::get($key, 0);
    }

    private function bump(string $key, int $ttl): void
    {
        // add() chỉ tạo khi chưa có -> không ghi đè bộ đếm đang chạy
        Cache::add($key, 0, $ttl);
        Cache::increment($key);
    }

    private function minuteKey(): string
    {
        return 'chatbot:llm:m:' . now()->format('YmdHi');
    }

    private function dayKey(): string
    {
        return 'chatbot:llm:d:' . Carbon::now('America/Los_Angeles')->format('Ymd');
    }

    private function clientKey(string $client): string
    {
        return 'chatbot:llm:c:' . Carbon::now('America/Los_Angeles')->format('Ymd') . ':' . sha1($client);
    }
}
