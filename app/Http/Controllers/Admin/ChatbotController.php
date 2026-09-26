<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Services\Chatbot\IntentMatcher;
use App\Services\Chatbot\LlmClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Trang theo dõi chatbot.
 *
 * Giá trị thật của trang này nằm ở mục "Bot chưa trả lời được": nó gom lại
 * đúng những câu khách hỏi mà bot chịu thua. Admin đọc danh sách đó rồi thêm
 * vào Câu hỏi thường gặp là lần sau bot tự trả lời được - bot tự khá lên theo
 * thời gian mà không cần lập trình viên đụng vào code.
 */
class ChatbotController extends Controller
{
    public function index(Request $request)
    {
        $conversations = ChatConversation::with('user')
            ->when($request->filled('only_handoff'), fn ($q) => $q->where('handed_to_human', true))
            ->orderByDesc('last_message_at')
            ->paginate(15)
            ->appends($request->query());

        return view('admin.chatbot.index', [
            'conversations' => $conversations,
            'stats' => $this->stats(),
            'intentStats' => $this->intentStats(),
            'unanswered' => $this->unanswered(),
            'llmEnabled' => app(LlmClient::class)->isEnabled(),
        ]);
    }

    public function show(ChatConversation $conversation)
    {
        $conversation->load('user', 'messages');

        return view('admin.chatbot.show', compact('conversation'));
    }

    public function destroy(ChatConversation $conversation)
    {
        // Model tự xóa tin nhắn con trong sự kiện deleting()
        $conversation->delete();

        return redirect()->route('admin.chatbot.index')->with('success', 'Đã xóa hội thoại.');
    }

    // ===================================================================

    private function stats(): array
    {
        $totalBotMessages = ChatMessage::where('role', 'bot')->count();
        $unknown = ChatMessage::where('role', 'bot')->where('intent', 'unknown')->count();

        return [
            'conversations' => ChatConversation::count(),
            'messages' => ChatMessage::count(),
            'handoffs' => ChatConversation::where('handed_to_human', true)->count(),
            'bot_messages' => $totalBotMessages,
            // Tỉ lệ bot tự xử lý được - chỉ số quan trọng nhất của một chatbot
            'resolve_rate' => $totalBotMessages > 0
                ? round(($totalBotMessages - $unknown) / $totalBotMessages * 100, 1)
                : 0.0,
        ];
    }

    /** Ý định nào khách hỏi nhiều nhất */
    private function intentStats()
    {
        return ChatMessage::where('role', 'bot')
            ->whereNotNull('intent')
            ->select('intent')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('AVG(confidence) as avg_confidence')
            ->groupBy('intent')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'intent' => $row->intent,
                'label' => IntentMatcher::label($row->intent),
                'total' => (int) $row->total,
                'confidence' => round((float) $row->avg_confidence * 100),
            ]);
    }

    /**
     * Những câu bot KHÔNG hiểu, gom theo nội dung để thấy câu nào lặp nhiều nhất.
     * Lấy tin nhắn của KHÁCH ngay trước mỗi câu trả lời 'unknown' của bot.
     */
    private function unanswered()
    {
        $unknownBotIds = ChatMessage::where('role', 'bot')
            ->where('intent', 'unknown')
            ->orderByDesc('id')
            ->limit(200)
            ->pluck('id', 'conversation_id');

        if ($unknownBotIds->isEmpty()) {
            return collect();
        }

        // Câu hỏi của khách luôn được ghi NGAY TRƯỚC câu trả lời của bot
        // (xem ChatbotController::send), nên id nhỏ hơn đúng 1 bậc.
        $questions = ChatMessage::where('role', 'user')
            ->whereIn('id', $unknownBotIds->values()->map(fn ($id) => $id - 1)->all())
            ->orderByDesc('id')
            ->limit(200)
            ->get(['id', 'conversation_id', 'content', 'created_at']);

        return $questions
            ->groupBy(fn ($m) => mb_strtolower(trim($m->content)))
            ->map(fn ($group) => [
                'question' => $group->first()->content,
                'count' => $group->count(),
                'conversation_id' => $group->first()->conversation_id,
                'last_asked' => $group->max('created_at'),
            ])
            ->sortByDesc('count')
            ->take(20)
            ->values();
    }
}
