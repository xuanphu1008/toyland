<?php

namespace App\Console\Commands;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Services\Chatbot\ChatbotService;
use App\Services\Chatbot\LlmClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

/**
 * BÁC SĨ CHATBOT — php artisan chatbot:doctor
 *
 * Khung chat trên trình duyệt chỉ nói được "Mình đang gặp trục trặc", vì lỗi
 * thật nằm ở phía máy chủ. Lệnh này chạy đúng luồng mà trình duyệt gọi, nhưng
 * ngay trong terminal, nên lỗi hiện ra nguyên văn kèm tên file và số dòng.
 *
 * Thứ tự kiểm tra đi từ nền móng lên: CSDL -> ghi hội thoại -> từng ý định ->
 * mô hình ngôn ngữ. Hỏng ở tầng nào thì dừng đọc ở tầng đó, khỏi đoán mò.
 */
class ChatbotDoctorCommand extends Command
{
    protected $signature = 'chatbot:doctor {--fix-hint : In sẵn các dòng .env cần sửa}';

    protected $description = 'Dò tìm nguyên nhân khi chatbot báo lỗi trên trình duyệt';

    /** Mỗi ý định một câu đại diện — chạy thật để xem hàm xử lý nào ném lỗi */
    private const PROBES = [
        'greeting' => 'xin chào',
        'product_search' => 'Gợi ý đồ chơi cho bé 3 tuổi',
        'bestseller' => 'Món nào bán chạy nhất shop',
        'coupon' => 'Có mã giảm giá nào đang chạy không',
        'loyalty' => 'Điểm thưởng của tôi dùng sao',
        'order_status' => 'Đơn hàng #1 tới đâu rồi',
        'order_list' => 'Cho xem lịch sử đơn hàng',
        'shipping' => 'Phí vận chuyển tính thế nào',
        'payment' => 'Shop thanh toán thế nào',
        'return_policy' => 'Chính sách đổi trả ra sao',
        'store_info' => 'Cho xin hotline',
        'safety' => 'Đồ chơi có an toàn cho bé không',
        'human_handoff' => 'Cho tôi gặp nhân viên tư vấn',
        'unknown' => 'hôm nay trời đẹp nhỉ',
    ];

    private int $problems = 0;

    public function handle(ChatbotService $chatbot, LlmClient $llm): int
    {
        $this->newLine();
        $this->line('<fg=white;bg=blue> BÁC SĨ CHATBOT TOYLAND </>');
        $this->newLine();

        $this->checkDatabase();
        $this->checkLogging();
        $this->checkIntents($chatbot);
        $this->checkLlm($llm);

        $this->newLine();

        if ($this->problems === 0) {
            $this->line('<fg=green;options=bold>✔ Không phát hiện lỗi nào ở phía máy chủ.</>');
            $this->line('  Nếu trình duyệt vẫn báo trục trặc thì nhấn Ctrl+F5 để nạp lại');
            $this->line('  file JavaScript, rồi mở Console (F12) xem thông báo màu đỏ.');
        } else {
            $this->line("<fg=red;options=bold>✘ Có {$this->problems} chỗ cần xử lý — xem phần đánh dấu ✘ ở trên.</>");
        }

        if ($this->option('fix-hint')) {
            $this->envHint();
        }

        $this->newLine();

        return $this->problems === 0 ? self::SUCCESS : self::FAILURE;
    }

    // ===================================================================

    private function checkDatabase(): void
    {
        $this->line('<options=bold>1. Kết nối CSDL và các bảng cần thiết</>');

        try {
            DB::connection()->getPdo();
            $this->ok('Kết nối được CSDL (' . config('database.default') . ')');
        } catch (Throwable $e) {
            $this->fail('KHÔNG kết nối được CSDL', $e);
            $this->newLine();

            return;
        }

        // chat_* là bảng của đợt nâng cấp này — thiếu nghĩa là chưa chạy migrate
        $required = [
            'chat_conversations' => 'php artisan migrate',
            'chat_messages' => 'php artisan migrate',
            'products' => null,
            'orders' => null,
            'categories' => null,
        ];

        foreach ($required as $table => $hint) {
            try {
                if (Schema::hasTable($table)) {
                    $this->ok("Bảng `{$table}` đã có");
                } else {
                    $this->bad("THIẾU bảng `{$table}`" . ($hint ? " — chạy: {$hint}" : ''));
                }
            } catch (Throwable $e) {
                $this->fail("Không kiểm tra được bảng `{$table}`", $e);
            }
        }

        // Bảng tùy chọn: thiếu thì chỉ mất một phần chức năng, không chết bot
        foreach (['faqs' => 'khớp câu hỏi thường gặp', 'coupons' => 'tra mã giảm giá'] as $table => $feature) {
            try {
                if (! Schema::hasTable($table)) {
                    $this->warn("   ! Thiếu bảng `{$table}` — mất chức năng {$feature}");
                }
            } catch (Throwable $e) {
                // bỏ qua, đã báo ở trên
            }
        }

        $this->newLine();
    }

    /** Thử ghi một hội thoại thật rồi xóa đi — đúng thao tác mà route /chatbot/gui làm */
    private function checkLogging(): void
    {
        $this->line('<options=bold>2. Ghi lịch sử hội thoại</>');

        try {
            $conversation = ChatConversation::create([
                'user_id' => null,
                'session_id' => 'doctor-' . Str::random(12),
                'message_count' => 0,
                'last_message_at' => now(),
            ]);

            ChatMessage::create([
                'conversation_id' => $conversation->id,
                'role' => 'user',
                'content' => 'câu kiểm tra của lệnh chatbot:doctor',
                'intent' => null,
                'confidence' => null,
                'meta' => null,
            ]);

            ChatMessage::create([
                'conversation_id' => $conversation->id,
                'role' => 'bot',
                'content' => 'trả lời kiểm tra',
                'intent' => 'greeting',
                'confidence' => 0.9876,
                'meta' => json_encode(['entities' => ['age_months' => 36]], JSON_UNESCAPED_UNICODE),
            ]);

            $count = $conversation->messages()->count();
            $conversation->delete();

            $this->ok("Ghi và xóa được hội thoại thử ({$count} tin nhắn)");
        } catch (Throwable $e) {
            $this->fail('KHÔNG ghi được hội thoại', $e);
            $this->line('   <fg=yellow>Bot vẫn trả lời được, nhưng trang quản trị sẽ không có dữ liệu.</>');
        }

        $this->newLine();
    }

    /** Chạy thật từng ý định — đây là chỗ bắt được hàm xử lý nào ném lỗi */
    private function checkIntents(ChatbotService $chatbot): void
    {
        $this->line('<options=bold>3. Chạy thử từng nhóm câu hỏi</>');

        $broken = [];

        foreach (self::PROBES as $intent => $question) {
            try {
                $result = $chatbot->reply($question, null);

                $extra = '';
                if (! empty($result['products'])) {
                    $extra = ' (' . count($result['products']) . ' sản phẩm)';
                }

                $this->ok(str_pad($intent, 16) . Str::limit($question, 34) . $extra);
            } catch (Throwable $e) {
                $broken[] = $intent;
                $this->fail(str_pad($intent, 16) . Str::limit($question, 34), $e);
            }
        }

        if ($broken) {
            $this->newLine();
            $this->line('   <fg=red>Các nhóm câu hỏi hỏng: ' . implode(', ', $broken) . '</>');
            $this->line('   <fg=yellow>Đọc dòng "tại ...php:123" ở trên để biết chính xác chỗ cần sửa.</>');
        }

        $this->newLine();
    }

    private function checkLlm(LlmClient $llm): void
    {
        $this->line('<options=bold>4. Mô hình ngôn ngữ bổ trợ (tùy chọn)</>');

        $driver = config('services.chatbot.driver');
        $key = (string) config('services.chatbot.key');
        $model = config('services.chatbot.model');
        $baseUrl = config('services.chatbot.base_url');

        $this->line('   Nhà cung cấp : ' . ($driver ?: '(trống)'));
        $this->line('   Khóa API     : ' . ($key === '' ? '(trống)' : $this->mask($key)));
        $this->line('   Tên model    : ' . ($model ?: '(trống)'));
        $this->line('   Địa chỉ gọi  : ' . $llm->endpoint());
        $this->line('   Chờ tối đa   : ' . config('services.chatbot.timeout') . ' giây');

        if (! $llm->isEnabled()) {
            $this->line('   <fg=yellow>→ Đang TẮT. Bot chạy offline hoàn toàn, vẫn đủ chức năng.</>');
            $this->newLine();

            return;
        }

        if ($driver === 'gemini') {
            $this->line('   <fg=yellow>→ Đang dùng Gemini, lệnh này chưa gọi thử được.</>');
            $this->newLine();

            return;
        }

        // Bắt lỗi kinh điển: khai địa chỉ máy chủ nội bộ nhưng thiếu /v1
        if (Str::contains($baseUrl, ['localhost', '127.0.0.1']) && ! Str::contains($baseUrl, '/v1')) {
            $this->bad("Địa chỉ nội bộ thiếu đuôi /v1 — phải là {$baseUrl}/v1");
        }

        $this->line('   Đang gọi thử...');

        try {
            $response = Http::withToken($key)
                ->withOptions(['verify' => config('services.chatbot.verify_ssl', true)])
                ->timeout(max(5, (int) config('services.chatbot.timeout', 45)))
                ->post($llm->endpoint(), [
                    'model' => $model,
                    'messages' => [['role' => 'user', 'content' => 'Trả lời đúng một từ: xin chào']],
                    'max_tokens' => 20,
                    'temperature' => 0,
                ]);

            if ($response->successful()) {
                $answer = $response->json('choices.0.message.content');
                $this->ok('Gọi được. Model trả lời: ' . Str::limit(trim((string) $answer), 60));
            } else {
                $this->bad('Máy chủ trả mã ' . $response->status());
                $this->line('   <fg=red>' . Str::limit($response->body(), 300) . '</>');
                $this->llmHint($response->status(), $response->body());
            }
        } catch (Throwable $e) {
            $this->fail('Không gọi được máy chủ LLM', $e);
            $this->line('   <fg=yellow>Thường là do máy chủ chưa chạy. Kiểm tra bằng lệnh: ollama list</>');
        }

        $this->newLine();
    }

    /** Dịch mã lỗi HTTP sang việc cần làm */
    private function llmHint(int $status, string $body): void
    {
        $hint = match (true) {
            $status === 401 => 'Khóa API sai. Nếu dùng model nội bộ thì đặt CHATBOT_LLM_DRIVER=openai '
                . 'và CHATBOT_LLM_BASE_URL trỏ về máy mình.',
            $status === 404 && Str::contains($body, 'model') => 'Sai tên model. Xem tên đúng bằng lệnh: ollama list',
            $status === 404 => 'Sai đường dẫn. Với Ollama phải là http://localhost:11434/v1',
            $status === 429 => 'Vượt hạn mức hoặc hết tiền trong tài khoản.',
            default => null,
        };

        if ($hint) {
            $this->line('   <fg=yellow>→ ' . $hint . '</>');
        }
    }

    private function envHint(): void
    {
        $this->newLine();
        $this->line('<options=bold>Mẫu .env cho model chạy nội bộ bằng Ollama</>');
        $this->newLine();
        $this->line('   CHATBOT_LLM_DRIVER=openai');
        $this->line('   CHATBOT_LLM_KEY=ollama');
        $this->line('   CHATBOT_LLM_MODEL=qwen2.5:7b');
        $this->line('   CHATBOT_LLM_BASE_URL=http://localhost:11434/v1');
        $this->line('   CHATBOT_LLM_TIMEOUT=60');
        $this->newLine();
        $this->line('   <fg=yellow>Sửa xong nhớ chạy: php artisan config:clear</>');
    }

    // ===================================================================

    private function ok(string $message): void
    {
        $this->line('   <fg=green>✔</> ' . $message);
    }

    private function bad(string $message): void
    {
        $this->problems++;
        $this->line('   <fg=red>✘ ' . $message . '</>');
    }

    private function fail(string $message, Throwable $e): void
    {
        $this->problems++;
        $this->line('   <fg=red>✘ ' . $message . '</>');
        $this->line('     <fg=red>' . class_basename($e) . ': ' . Str::limit($e->getMessage(), 240) . '</>');
        $this->line('     <fg=gray>tại ' . basename($e->getFile()) . ':' . $e->getLine() . '</>');
    }

    private function mask(string $key): string
    {
        return mb_strlen($key) <= 8
            ? str_repeat('*', mb_strlen($key))
            : mb_substr($key, 0, 4) . str_repeat('*', 6) . mb_substr($key, -3);
    }
}
