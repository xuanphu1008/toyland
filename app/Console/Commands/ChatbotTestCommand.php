<?php

namespace App\Console\Commands;

use App\Services\Chatbot\ChatbotService;
use App\Services\Chatbot\EntityExtractor;
use App\Services\Chatbot\IntentMatcher;
use App\Services\Chatbot\LlmClient;
use App\Services\RecommendationService;
use Illuminate\Console\Command;

/**
 * Lệnh tự kiểm tra chatbot và bộ gợi ý.
 *
 *   php artisan chatbot:test          # chạy toàn bộ bộ kiểm thử
 *   php artisan chatbot:test --chat   # mở chế độ trò chuyện ngay trong terminal
 *
 * Lệnh này vừa để kiểm chứng khi phát triển, vừa là số liệu đưa vào báo cáo:
 * độ chính xác nhận diện ý định là một con số đo được, không phải cảm tính.
 */
class ChatbotTestCommand extends Command
{
    protected $signature = 'chatbot:test {--chat : Trò chuyện thử ngay trong terminal}';

    protected $description = 'Kiểm tra độ chính xác của chatbot và trạng thái bộ gợi ý';

    /**
     * Bộ kiểm thử: [câu hỏi, ý định mong đợi]
     * Thêm câu mới vào đây mỗi khi phát hiện bot hiểu sai - đó là "hồi quy test",
     * bảo đảm sửa chỗ này không làm hỏng chỗ kia.
     */
    private const TEST_CASES = [
        ['Xin chào shop', 'greeting'],
        ['chào bạn', 'greeting'],
        ['cảm ơn bạn nhé', 'thanks'],
        ['thanks shop', 'thanks'],
        ['đơn hàng #12 tới đâu rồi', 'order_status'],
        ['cho mình tra cứu đơn hàng với', 'order_status'],
        ['đơn của tôi đã giao chưa', 'order_status'],
        ['khi nào tôi nhận được hàng', 'order_status'],
        ['tôi muốn xem lịch sử đơn hàng', 'order_list'],
        ['cho xem lại đơn hàng đã đặt', 'order_list'],
        ['tìm đồ chơi cho bé 3 tuổi', 'product_search'],
        ['có quà sinh nhật nào cho bé gái không', 'product_search'],
        ['shop có bán lego không', 'product_search'],
        ['gợi ý đồ chơi dưới 300k', 'product_search'],
        ['món nào bán chạy nhất shop', 'bestseller'],
        ['đồ chơi nào hot nhất', 'bestseller'],
        ['phí ship bao nhiêu vậy', 'shipping'],
        ['giao hàng mất bao lâu', 'shipping'],
        ['có freeship không', 'shipping'],
        ['shop thanh toán thế nào', 'payment'],
        ['có nhận momo không', 'payment'],
        ['mình muốn trả tiền khi nhận hàng', 'payment'],
        ['chính sách đổi trả ra sao', 'return_policy'],
        ['hàng lỗi có được đổi không', 'return_policy'],
        ['có mã giảm giá nào đang chạy không', 'coupon'],
        ['shop có khuyến mãi gì không', 'coupon'],
        ['điểm thưởng của tôi dùng sao', 'loyalty'],
        ['làm sao lên hạng vàng', 'loyalty'],
        ['shop ở đâu vậy, số điện thoại bao nhiêu', 'store_info'],
        ['cho xin hotline', 'store_info'],
        ['đồ chơi có an toàn cho bé không', 'safety'],
        ['hàng có chính hãng không', 'safety'],
        ['cho tôi gặp nhân viên tư vấn', 'human_handoff'],
        ['muốn chat với nhân viên', 'human_handoff'],
        ['hôm nay trời đẹp nhỉ', 'unknown'],
        ['bạn tên gì', 'unknown'],

        // Bổ sung sau khi phát hiện bot đẩy câu hỏi mua hàng sang LLM và LLM bịa
        // ra một sản phẩm không có trong kho. Giữ lại đây làm hồi quy test.
        ['tìm món hàng cho trẻ 8 tuổi khoảng 700k cho bé gái', 'product_search'],
        ['tìm món hàng cho bé', 'product_search'],
        ['nên mua gì cho bé trai 5 tuổi', 'product_search'],
        ['có gì cho bé gái không', 'product_search'],
        ['bé 3 tuổi chơi gì được', 'product_search'],
        ['gợi ý sản phẩm phù hợp với bé', 'product_search'],

        // Đợt nâng cấp Hồ sơ của bé: bot phải phân biệt được "tìm đồ chơi nói chung"
        // với "quà cho chính con tôi" - cái sau thì tra hồ sơ bé đã lưu.
        ['gợi ý quà sinh nhật cho con', 'child_gift'],
        ['bé nhà mình sắp sinh nhật rồi', 'child_gift'],
        ['sinh nhật con tôi nên mua gì', 'child_gift'],
        ['cho xem sổ sinh nhật', 'child_gift'],
    ];

    /** Câu hỏi dùng để kiểm tra việc bóc tách thực thể */
    private const ENTITY_CASES = [
        'tìm món hàng cho trẻ 8 tuổi khoảng 700k cho bé gái',
        'tìm quà cho bé gái 5 tuổi tầm 300k',
        'bé 8 tháng chơi gì được',
        'đồ chơi dưới 200 nghìn',
        'có gì từ 500k đến 1 triệu không',
        'con trai lớp 3 thích gì',
        'đơn hàng #12 tới đâu rồi',
        'quà cho bé sơ sinh',
    ];

    public function handle(
        IntentMatcher $matcher,
        EntityExtractor $extractor,
        ChatbotService $chatbot,
        RecommendationService $recommender,
        LlmClient $llm
    ): int {
        if ($this->option('chat')) {
            return $this->interactive($chatbot);
        }

        $this->newLine();
        $this->line('<fg=white;bg=blue> KIỂM TRA CHATBOT TOYLAND </>');
        $this->newLine();

        $passed = $this->testIntents($matcher);
        $this->testEntities($extractor);
        $this->showRecommenderStatus($recommender);
        $this->showLlmStatus($llm);

        return $passed ? self::SUCCESS : self::FAILURE;
    }

    // ===================================================================

    private function testIntents(IntentMatcher $matcher): bool
    {
        $this->line('<options=bold>1. Nhận diện ý định</>');

        $rows = [];
        $pass = 0;

        foreach (self::TEST_CASES as [$question, $expected]) {
            $result = $matcher->match($question);
            $ok = $result['intent'] === $expected;
            $pass += $ok ? 1 : 0;

            // Chỉ in ra những câu SAI cho gọn màn hình
            if (! $ok) {
                $rows[] = [
                    $question,
                    IntentMatcher::label($expected),
                    IntentMatcher::label($result['intent']),
                    round($result['confidence'] * 100) . '%',
                ];
            }
        }

        $total = count(self::TEST_CASES);
        $rate = round($pass / $total * 100, 1);

        if ($rows) {
            $this->table(['Câu hỏi', 'Mong đợi', 'Bot hiểu thành', 'Tin cậy'], $rows);
        }

        $style = $rate >= 90 ? 'green' : ($rate >= 70 ? 'yellow' : 'red');
        $this->line("   Kết quả: <fg={$style};options=bold>{$pass}/{$total} đúng ({$rate}%)</>");
        $this->newLine();

        return $rate >= 90;
    }

    private function testEntities(EntityExtractor $extractor): void
    {
        $this->line('<options=bold>2. Bóc tách thực thể</>');

        $rows = [];

        foreach (self::ENTITY_CASES as $question) {
            $entities = $extractor->extract($question);

            $pretty = collect($entities)
                ->map(fn ($value, $key) => "{$key}={$value}")
                ->implode(', ');

            $rows[] = [$question, $pretty ?: '(không bóc được gì)'];
        }

        $this->table(['Câu hỏi', 'Bóc tách được'], $rows);
    }

    private function showRecommenderStatus(RecommendationService $recommender): void
    {
        $this->line('<options=bold>3. Bộ gợi ý</>');

        $stats = $recommender->stats();

        $this->line('   Đơn hàng hợp lệ:        ' . number_format($stats['total_orders'], 0, ',', '.'));
        $this->line('   Đơn có từ 2 món trở lên: ' . number_format($stats['multi_item_orders'], 0, ',', '.')
            . ' (' . $stats['coverage'] . '%)');

        if ($stats['multi_item_orders'] < 5) {
            $this->line('   <fg=yellow>→ Dữ liệu mua chung còn ít, bộ gợi ý đang chạy chủ yếu bằng thuật toán</>');
            $this->line('   <fg=yellow>  theo NỘI DUNG (cùng danh mục, cùng độ tuổi, cùng nhân vật...).</>');
            $this->line('   <fg=yellow>  Đặt thêm vài đơn nhiều món là lọc cộng tác bắt đầu có tác dụng.</>');
        } else {
            $this->line('   <fg=green>→ Đủ dữ liệu để lọc cộng tác hoạt động.</>');
        }

        $this->newLine();
    }

    private function showLlmStatus(LlmClient $llm): void
    {
        $this->line('<options=bold>4. Mô hình ngôn ngữ bổ trợ</>');

        if ($llm->isEnabled()) {
            $this->line('   <fg=green>Đã bật</> — nhà cung cấp: ' . config('services.chatbot.driver')
                . ', mô hình: ' . config('services.chatbot.model'));
            $this->line('   Chỉ dùng cho câu hỏi ngoài kịch bản; nghiệp vụ vẫn do bot nội bộ xử lý.');
        } else {
            $this->line('   <fg=yellow>Chưa bật</> — bot đang chạy hoàn toàn offline (vẫn đầy đủ chức năng).');
            $this->line('   Muốn bật: khai CHATBOT_LLM_DRIVER và CHATBOT_LLM_KEY trong .env');
        }

        $this->newLine();
    }

    /** Trò chuyện thử ngay trong terminal - tiện để demo nhanh */
    private function interactive(ChatbotService $chatbot): int
    {
        $this->info('Chế độ trò chuyện. Gõ "thoat" để dừng.');
        $this->newLine();

        while (true) {
            $question = $this->ask('Bạn');

            if ($question === null || in_array(mb_strtolower(trim($question)), ['thoat', 'exit', 'quit'], true)) {
                $this->line('Tạm biệt!');

                return self::SUCCESS;
            }

            $result = $chatbot->reply($question, null);

            $this->newLine();
            $this->line('<fg=cyan>Bot:</> ' . $result['reply']);

            foreach ($result['products'] ?? [] as $product) {
                $this->line('   <fg=yellow>•</> ' . $product['name'] . ' — ' . $product['price']);
            }

            $this->line('<fg=gray>   [ý định: ' . IntentMatcher::label($result['intent'])
                . ' · tin cậy ' . round($result['confidence'] * 100) . '%]</>');
            $this->newLine();
        }
    }
}
