<?php

namespace App\Services\Chatbot;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Lớp gọi mô hình ngôn ngữ (LLM) - CHỈ dùng khi bot nội bộ không hiểu câu hỏi.
 *
 * TRIẾT LÝ: LLM là phần PHỤ, không phải phần chính.
 * Toàn bộ nghiệp vụ (tra đơn, tìm hàng, mã giảm giá) do bot nội bộ xử lý vì
 * nó truy vấn CSDL thật và luôn chạy được kể cả khi mất mạng. LLM chỉ đỡ
 * những câu tán gẫu ngoài kịch bản. Không khai API key thì hệ thống vẫn
 * hoạt động bình thường, chỉ là câu lạ sẽ nhận câu trả lời mặc định.
 *
 * Cấu hình trong .env:
 *   CHATBOT_LLM_DRIVER=openai      (none | openai | gemini)
 *   CHATBOT_LLM_KEY=sk-...
 *   CHATBOT_LLM_MODEL=gpt-4o-mini
 */
class LlmClient
{
    /**
     * Tối đa bao nhiêu giây chờ LLM - quá thì bỏ, trả lời mặc định cho nhanh.
     * Đọc từ .env vì model chạy trên máy (Ollama/LM Studio) chậm hơn API đám mây:
     * lần gọi đầu còn phải nạp model vào RAM nên có thể mất vài chục giây.
     */
    private function timeout(): int
    {
        return max(5, (int) config('services.chatbot.timeout', 45));
    }

    public function isEnabled(): bool
    {
        $driver = config('services.chatbot.driver');

        return $driver && $driver !== 'none' && ! empty(config('services.chatbot.key'));
    }

    /**
     * @param  string  $question   câu hỏi của khách
     * @param  string  $context    thông tin về shop để LLM không bịa
     * @param  array   $history    [['role' => 'user'|'assistant', 'content' => '...'], ...]
     */
    public function ask(string $question, string $context, array $history = []): ?string
    {
        if (! $this->isEnabled()) {
            return null;
        }

        try {
            return match (config('services.chatbot.driver')) {
                'openai' => $this->askOpenAi($question, $context, $history),
                'gemini' => $this->askGemini($question, $context, $history),
                default => null,
            };
        } catch (Throwable $e) {
            // LLM lỗi thì im lặng quay về câu trả lời mặc định - không làm hỏng trải nghiệm
            Log::warning('Chatbot: gọi LLM thất bại', ['message' => $e->getMessage()]);

            return null;
        }
    }

    private function askOpenAi(string $question, string $context, array $history): ?string
    {
        $messages = array_merge(
            [['role' => 'system', 'content' => $this->systemPrompt($context)]],
            $this->trimHistory($history),
            [['role' => 'user', 'content' => $question]]
        );

        $response = Http::withToken(config('services.chatbot.key'))
            ->withOptions(['verify' => config('services.chatbot.verify_ssl', true)])
            ->timeout($this->timeout())
            ->post($this->endpoint(), [
                'model' => config('services.chatbot.model', 'gpt-4o-mini'),
                'messages' => $messages,
                'max_tokens' => 400,
                'temperature' => 0.4,
            ]);

        if (! $response->successful()) {
            // Ghi cả nội dung trả về: đây là chỗ duy nhất nói rõ vì sao hỏng
            // (sai tên model, sai key, Ollama chưa chạy, model chưa tải về...).
            Log::warning('Chatbot: máy chủ LLM trả về lỗi', [
                'status' => $response->status(),
                'url' => $this->endpoint(),
                'body' => Str::limit($response->body(), 500),
            ]);

            return null;
        }

        $answer = $response->json('choices.0.message.content');

        return is_string($answer) && trim($answer) !== '' ? trim($answer) : null;
    }

    private function askGemini(string $question, string $context, array $history): ?string
    {
        $model = config('services.chatbot.model', 'gemini-1.5-flash');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

        $contents = [];

        foreach ($this->trimHistory($history) as $turn) {
            $contents[] = [
                'role' => $turn['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $turn['content']]],
            ];
        }

        $contents[] = ['role' => 'user', 'parts' => [['text' => $question]]];

        $response = Http::withOptions(['verify' => config('services.chatbot.verify_ssl', true)])
            ->timeout($this->timeout())
            ->post($url . '?key=' . config('services.chatbot.key'), [
                'system_instruction' => ['parts' => [['text' => $this->systemPrompt($context)]]],
                'contents' => $contents,
                'generationConfig' => ['maxOutputTokens' => 400, 'temperature' => 0.4],
            ]);

        if (! $response->successful()) {
            Log::warning('Chatbot: Gemini trả về lỗi', [
                'status' => $response->status(),
                'body' => Str::limit($response->body(), 500),
            ]);

            return null;
        }

        $answer = $response->json('candidates.0.content.parts.0.text');

        return is_string($answer) && trim($answer) !== '' ? trim($answer) : null;
    }

    /**
     * Ràng buộc LLM vào đúng vai trò, và QUAN TRỌNG NHẤT là cấm bịa số liệu.
     * Mọi con số thật (giá, tồn kho, trạng thái đơn) đều do bot nội bộ trả lời,
     * LLM chỉ được nói những gì có trong phần bối cảnh.
     */
    private function systemPrompt(string $context): string
    {
        return <<<PROMPT
        Bạn là trợ lý tư vấn của ToyLand - cửa hàng đồ chơi trẻ em tại Việt Nam.

        QUY TẮC BẮT BUỘC:
        - Trả lời bằng tiếng Việt, thân thiện, ngắn gọn (tối đa 4 câu).
        - TUYỆT ĐỐI KHÔNG NÊU TÊN MỘT SẢN PHẨM CỤ THỂ. Bạn KHÔNG nhìn thấy kho hàng
          nên mọi tên món bạn nghĩ ra đều là bịa, khách bấm vào sẽ không có. Chỉ được
          nói theo NHÓM hàng (ví dụ "bộ lắp ghép", "búp bê", "đồ chơi vận động").
          Hệ thống sẽ tự đính kèm thẻ sản phẩm thật bên dưới câu trả lời của bạn.
        - TUYỆT ĐỐI KHÔNG bịa giá, tồn kho, mã giảm giá hay trạng thái đơn hàng.
          Nếu khách hỏi những thứ đó, hãy bảo khách gõ rõ hơn (ví dụ "tra cứu đơn #12")
          để hệ thống tra dữ liệu thật.
        - Chỉ dùng thông tin trong phần BỐI CẢNH dưới đây. Không biết thì nói không biết
          và mời khách bấm "Gặp nhân viên tư vấn".
        - Không nhắc tới việc bạn là AI hay mô hình ngôn ngữ.

        BỐI CẢNH VỀ CỬA HÀNG:
        {$context}
        PROMPT;
    }

    /**
     * Địa chỉ gọi model theo chuẩn OpenAI.
     * Dùng chung cho cả OpenAI thật lẫn model chạy nội bộ - Ollama, LM Studio,
     * vLLM đều dựng sẵn đường dẫn /v1/chat/completions y hệt.
     */
    public function endpoint(): string
    {
        return rtrim((string) config('services.chatbot.base_url'), '/') . '/chat/completions';
    }

    /** Chỉ giữ 6 lượt gần nhất cho gọn prompt và đỡ tốn token */
    private function trimHistory(array $history): array
    {
        return array_slice($history, -6);
    }
}
