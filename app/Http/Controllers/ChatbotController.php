<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Services\Chatbot\ChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Throwable;

/**
 * Điểm vào của chatbot phía khách hàng.
 *
 * Route KHÔNG nằm sau middleware auth: khách chưa đăng nhập vẫn hỏi được
 * (tìm sản phẩm, phí ship...). Riêng những ý định cần danh tính như tra cứu
 * đơn hàng thì ChatbotService tự kiểm tra và mời khách đăng nhập.
 *
 * NGUYÊN TẮC CHỊU LỖI (quan trọng):
 * Việc TRẢ LỜI khách và việc GHI LOG hội thoại được tách rời hẳn nhau.
 * Ghi log hỏng (thiếu bảng, chưa migrate, CSDL kén kiểu dữ liệu...) thì bot
 * vẫn phải trả lời bình thường - khách không có lỗi gì trong chuyện đó.
 * Chỉ khi chính bộ não trả lời gặp sự cố mới báo lỗi ra ngoài, và lúc đó
 * nếu APP_DEBUG=true thì kèm luôn nguyên văn lỗi để còn biết đường sửa.
 */
class ChatbotController extends Controller
{
    /** Giới hạn độ dài câu hỏi - chặn spam và chặn prompt quá dài gửi lên LLM */
    private const MAX_LENGTH = 500;

    public function __construct(private ChatbotService $chatbot)
    {
    }

    public function send(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:' . self::MAX_LENGTH],
        ], [], ['message' => 'Nội dung tin nhắn']);

        $user = Auth::user();

        // ---- 1. Chuẩn bị chỗ ghi log (hỏng cũng không sao) ----------------
        $conversation = $this->safely(fn () => $this->currentConversation($request, $user?->id));
        $history = $conversation ? ($this->safely(fn () => $this->historyFor($conversation)) ?? []) : [];

        // ---- 2. Trả lời - đây mới là phần không được phép hỏng ------------
        try {
            $result = $this->chatbot->reply($data['message'], $user, $history);
        } catch (Throwable $e) {
            report($e);

            return $this->failure($e);
        }

        // ---- 3. Ghi lại hội thoại (hỏng cũng không sao) -------------------
        if ($conversation) {
            $this->safely(function () use ($conversation, $data, $result) {
                $this->log($conversation, 'user', $data['message']);
                $this->log($conversation, 'bot', $result['reply'], $result);

                $conversation->update([
                    'message_count' => $conversation->messages()->count(),
                    'last_message_at' => now(),
                    'handed_to_human' => $conversation->handed_to_human
                        || ($result['intent'] ?? null) === 'human_handoff',
                ]);
            });
        }

        return response()->json([
            'success' => true,
            'reply' => $result['reply'],
            'products' => $result['products'] ?? [],
            'actions' => $result['actions'] ?? [],
            'intent' => $result['intent'] ?? 'unknown',
            // Cảnh báo mềm: bot trả lời được nhưng không lưu được lịch sử
            'logged' => (bool) $conversation,
        ]);
    }

    /** Lời chào + câu hỏi gợi ý khi khách vừa mở khung chat */
    public function greeting(): JsonResponse
    {
        try {
            $result = $this->chatbot->reply('xin chào', Auth::user());
        } catch (Throwable $e) {
            report($e);

            return $this->failure($e);
        }

        return response()->json([
            'success' => true,
            'reply' => $result['reply'],
            'actions' => $result['actions'] ?? [],
        ]);
    }

    // ===================================================================

    /**
     * Trả về JSON lỗi. Luôn dùng mã 200 để phía JavaScript đọc được nội dung
     * (mã 500 kèm trang lỗi HTML thì JS chỉ thấy "lỗi mạng", không biết gì thêm).
     *
     * Nguyên văn lỗi CHỈ lộ ra khi APP_DEBUG=true. Lên production thì khách
     * chỉ thấy câu xin lỗi, còn chi tiết nằm trong storage/logs/laravel.log.
     */
    private function failure(Throwable $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'reply' => 'Mình đang gặp trục trặc, bạn thử lại sau ít phút nhé.',
            'error' => config('app.debug')
                ? class_basename($e) . ': ' . $e->getMessage()
                    . ' — ' . basename($e->getFile()) . ':' . $e->getLine()
                : null,
        ]);
    }

    /** Chạy một đoạn code, nuốt lỗi và ghi log. Trả null nếu hỏng. */
    private function safely(callable $callback)
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    /** Vài lượt gần nhất làm ngữ cảnh cho LLM */
    private function historyFor(ChatConversation $conversation): array
    {
        return $conversation->messages()
            ->latest('id')
            ->limit(6)
            ->get()
            ->reverse()
            ->map(fn (ChatMessage $m) => [
                'role' => $m->role === 'bot' ? 'assistant' : 'user',
                'content' => $m->content,
            ])
            ->values()
            ->all();
    }

    /**
     * Tìm (hoặc tạo) hội thoại hiện tại.
     * Khách vãng lai được định danh bằng session id; đăng nhập rồi thì
     * hội thoại đang mở được gắn luôn user_id để admin biết ai đang hỏi.
     */
    private function currentConversation(Request $request, ?int $userId): ChatConversation
    {
        $sessionId = $request->session()->get('chatbot_session');

        if (! $sessionId) {
            $sessionId = (string) Str::uuid();
            $request->session()->put('chatbot_session', $sessionId);
        }

        $conversation = ChatConversation::where('session_id', $sessionId)->first();

        if (! $conversation) {
            return ChatConversation::create([
                'user_id' => $userId,
                'session_id' => $sessionId,
                'message_count' => 0,
                'last_message_at' => now(),
            ]);
        }

        // Khách đăng nhập giữa chừng -> gắn tài khoản vào hội thoại đang có
        if ($userId && ! $conversation->user_id) {
            $conversation->update(['user_id' => $userId]);
        }

        return $conversation;
    }

    private function log(ChatConversation $conversation, string $role, string $content, array $result = []): void
    {
        ChatMessage::create([
            'conversation_id' => $conversation->id,
            'role' => $role,
            'content' => $content,
            'intent' => $role === 'bot' ? ($result['intent'] ?? null) : null,
            'confidence' => $role === 'bot' ? ($result['confidence'] ?? null) : null,
            'meta' => $role === 'bot' && ! empty($result['meta'])
                ? json_encode([
                    'entities' => $result['meta']['entities'] ?? [],
                    'scores' => $result['meta']['scores'] ?? [],
                    'product_ids' => collect($result['products'] ?? [])->pluck('id')->all(),
                ], JSON_UNESCAPED_UNICODE)
                : null,
        ]);
    }
}
