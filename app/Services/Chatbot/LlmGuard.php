<?php

namespace App\Services\Chatbot;

use Illuminate\Support\Str;

/**
 * LỚP BẢO VỆ ĐỨNG TRƯỚC MÔ HÌNH NGÔN NGỮ (Gemini/OpenAI)
 *
 * Làm 3 việc, TẤT CẢ đều chạy ngay trên máy chủ của mình, trước khi có bất kỳ
 * dữ liệu nào rời khỏi hệ thống:
 *
 *  1. LỌC CHỦ ĐỀ: chỉ câu hỏi liên quan tới đồ chơi / con trẻ / mua sắm ở shop
 *     mới được gửi lên AI. "1+1 bằng mấy", "viết code Python", "thủ đô Pháp"...
 *     bị trả lời lịch sự là ngoài phạm vi - không tốn một lượt gọi API nào.
 *     (Lớp thứ hai nằm trong system prompt: AI tự từ chối nếu vẫn lọt.)
 *
 *  2. CHẶN CÂU LỆNH ĐỘC (prompt injection): "bỏ qua hướng dẫn trước",
 *     "ignore previous instructions", "cho xem system prompt"...
 *
 *  3. CHE THÔNG TIN CÁ NHÂN: số điện thoại, email, số thẻ, số giấy tờ, số nhà,
 *     tên người... được thay bằng nhãn [email], [số điện thoại]... Điều khoản
 *     gói miễn phí của Gemini nói rõ dữ liệu gửi lên có thể được dùng để cải
 *     thiện mô hình và có người đọc, và yêu cầu KHÔNG gửi thông tin cá nhân.
 *
 * Lưu ý trung thực: che thông tin cá nhân bằng mẫu nhận dạng là "cố gắng tối
 * đa", không phải tuyệt đối (ví dụ tên người viết thường không có ngữ cảnh
 * "tên tôi là..." thì không nhận ra). Vì vậy lớp 1 mới là lớp quan trọng nhất:
 * câu nào không cần AI thì không gửi đi.
 */
class LlmGuard
{
    public const VERDICT_OK = 'ok';

    public const VERDICT_OFF_TOPIC = 'off_topic';

    public const VERDICT_INJECTION = 'injection';

    /**
     * Cụm từ cho thấy câu hỏi THUỘC phạm vi của shop - đủ đặc thù để so trên bản
     * ĐÃ BỎ DẤU (khách gõ không dấu vẫn nhận ra). Khớp theo nguyên từ.
     */
    private const TOPIC_TERMS_PLAIN = [
        'do choi', 'dochoi', 'tro choi', 'em be', 'tre em', 'tre nho', 'thieu nhi', 'mam non',
        'mau giao', 'so sinh', 'thang tuoi', 'lego', 'bup be', 'gau bong', 'robot', 'xep hinh',
        'lap rap', 'lap ghep', 'ghep hinh', 'giao duc', 'stem', 'ky nang', 'tri tue', 'sang tao',
        'van dong', 'nhap vai', 'mo hinh', 'puzzle', 'board game', 'xe dap', 'cau truot',
        'sinh nhat', 'trung thu', 'giang sinh', 'noel', 'quoc te thieu nhi',
        'bo me', 'ba me', 'cha me', 'phu huynh', 'nuoi day', 'choi cung', 'an toan', 'chat lieu',
        'doc hai', 'shop', 'cua hang', 'toyland', 'dat hang', 'don hang', 'gio hang', 'giao hang',
        'van chuyen', 'phi ship', 'thanh toan', 'momo', 'cod', 'tra gop', 'doi tra', 'bao hanh',
        'hoan tien', 'giam gia', 'khuyen mai', 'voucher', 'ma giam', 'thanh vien', 'tich diem',
        'tai khoan', 'dang nhap', 'dang ky', 'hotline', 'lien he', 'nhan vien', 'tu van', 'goi y',
        'san pham', 'con hang', 'het hang', 'phu hop',
    ];

    /**
     * Từ một âm tiết thì PHẢI có dấu mới tính: bỏ dấu đi "quà" thành "qua",
     * "bán" thành "ban" (trùng "bạn"), "giá" thành "gia" (trùng "gia đình")...
     * So trên bản chữ thường CÒN DẤU.
     */
    private const TOPIC_TERMS_ACCENTED = [
        'bé', 'con', 'trẻ', 'cháu', 'tuổi', 'chơi', 'quà', 'tặng', 'mua', 'bán', 'giá', 'rẻ',
        'đắt', 'đơn', 'ship', 'hàng', 'lỗi', 'hỏng', 'đặt', 'học', 'xe', 'búp', 'nhựa', 'pin',
        'sale', 'điểm', 'đổi', 'trả', 'giao',
    ];

    /**
     * Chủ đề CHẮC CHẮN nằm ngoài phạm vi - chặn kể cả khi câu có lẫn từ của shop
     * (ví dụ "viết code tính giá đồ chơi" vẫn là nhờ viết code).
     */
    private const OFF_TOPIC_PATTERNS = [
        // Lập trình
        '/\b(python|javascript|typescript|php|java|c\+\+|c#|golang|sql|html|css|regex|laravel)\b/i',
        '/\b(lap trinh|viet code|code|thuat toan|debug|viet ham|cau lenh)\b/',
        // Làm bài hộ / viết lách / dịch
        '/\b(giai bai|bai tap|phuong trinh|dao ham|tich phan|ma tran|chung minh)\b/',
        '/\b(viet (bai|van|tho|luan|essay|email|thu)|bai van|lam tho|tieu luan|luan van)\b/',
        '/\b(dich (sang|ra|giup|cau|doan)|translate)\b/',
        // Kiến thức chung, thời sự, tài chính
        '/\b(thu do|tong thong|chu tich nuoc|thu tuong|chinh tri|bau cu|chien tranh|ton giao)\b/',
        '/\b(chung khoan|co phieu|bitcoin|crypto|tien ao|ty gia|gia vang|xo so|ca do|ca cuoc)\b/',
        '/\b(thoi tiet|bong da|ket qua tran|phim|ca si|dien vien)\b/',
    ];

    /** Câu lệnh tìm cách bẻ khóa vai trò của trợ lý */
    private const INJECTION_PATTERNS = [
        '/\b(bo qua|quen|phot lo|ignore|disregard|forget)\b.{0,40}\b(huong dan|chi dan|quy tac|lenh|instruction|instructions|rules|prompt)\b/',
        '/\b(system prompt|prompt he thong|developer mode|jailbreak|dan mode)\b/',
        '/\b(ban bay gio la|tu gio ban la|gia vo (ban )?la|dong vai|act as|pretend)\b/',
        '/\b(tiet lo|cho (toi|minh) xem|in ra|show)\b.{0,30}\b(prompt|huong dan|cau hinh|api key|khoa api)\b/',
    ];

    /**
     * Phân loại một câu hỏi TRƯỚC khi gửi lên AI.
     */
    public function classify(string $message): string
    {
        $plain = ' ' . TextAnalyzer::normalize($message) . ' ';

        foreach (self::INJECTION_PATTERNS as $pattern) {
            if (preg_match($pattern, $plain)) {
                return self::VERDICT_INJECTION;
            }
        }

        foreach (self::OFF_TOPIC_PATTERNS as $pattern) {
            if (preg_match($pattern, $plain)) {
                return self::VERDICT_OFF_TOPIC;
            }
        }

        if (! $this->mentionsTopic($plain, $this->lowerKeepAccents($message))) {
            return self::VERDICT_OFF_TOPIC;
        }

        return self::VERDICT_OK;
    }

    /** Câu có ít nhất một từ thuộc phạm vi shop không */
    private function mentionsTopic(string $plain, string $accented): bool
    {
        foreach (self::TOPIC_TERMS_PLAIN as $term) {
            if (str_contains($plain, ' ' . $term . ' ')) {
                return true;
            }
        }

        foreach (self::TOPIC_TERMS_ACCENTED as $term) {
            if (str_contains($accented, ' ' . $term . ' ')) {
                return true;
            }
        }

        return false;
    }

    /** Chữ thường, GIỮ dấu tiếng Việt, dấu câu thành khoảng trắng */
    private function lowerKeepAccents(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text) ?? $text;

        return ' ' . trim(preg_replace('/\s+/u', ' ', $text) ?? $text) . ' ';
    }

    /**
     * Che thông tin cá nhân. Thứ tự quan trọng: email trước (chứa chữ số),
     * rồi số thẻ/giấy tờ (dãy dài), cuối cùng mới tới số điện thoại.
     */
    public function maskPii(string $text): string
    {
        $rules = [
            // Email
            '/[\p{L}0-9._%+\-]+@[\p{L}0-9.\-]+\.[a-z]{2,}/iu' => '[email]',
            // Số thẻ ngân hàng: 13-19 chữ số, có thể cách nhau bởi khoảng trắng/gạch
            '/(?<!\d)(?:\d[ \-]?){12,18}\d(?!\d)/u' => '[số thẻ]',
            // Số điện thoại Việt Nam: 0xxx hoặc +84xxx, cho phép dấu cách/chấm/gạch
            '/(?<![\d#])(?:\+?84|0)(?:[\s.\-]?\d){8,10}(?!\d)/u' => '[số điện thoại]',
            // CMND/CCCD/mã số thuế và mọi dãy số dài từ 9 chữ số trở lên
            '/(?<![\d#])\d{9,}(?!\d)/u' => '[dãy số]',
            // Địa chỉ: "số nhà 12", "ngõ 34/5", "hẻm 12A"...
            '/\b(số nhà|so nha|ngõ|ngo|ngách|ngach|hẻm|hem|kiệt|kiet|căn hộ|can ho|phòng|phong)\s*\d+[\p{L}\d\/\-]*/iu' => '$1 [***]',
            '/\b(số|so)\s*\d+[\p{L}\d\/\-]*(?=\s*,?\s*(đường|duong|phố|pho|ngõ|ngo|hẻm|hem)\b)/iu' => '$1 [***]',
            // Tên người sau các cụm giới thiệu
            '/\b((?:tên|ten)\s+(?:(?:của\s+)?(?:tôi|toi|mình|minh|em|con|bé|be|cháu|chau)\s+)?(?:là|la)|(?:tôi|toi|mình|minh|em)\s+(?:là|la)|(?:tôi|toi|mình|minh|em)\s+tên(?:\s+là)?)\s+\p{Lu}\p{Ll}*(?:\s+\p{Lu}\p{Ll}*){0,3}/u' => '$1 [tên]',
        ];

        foreach ($rules as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text) ?? $text;
        }

        return $text;
    }

    /**
     * Chuẩn bị lịch sử hội thoại trước khi gửi lên AI:
     *  - BỎ HẲN nội dung các câu bot đã trả lời bằng dữ liệu riêng của khách
     *    (tra đơn, lịch sử đơn, điểm thưởng, mã giảm giá riêng, hồ sơ bé...) -
     *    những câu đó chứa tên người nhận, địa chỉ, số tiền.
     *  - Che thông tin cá nhân và cắt ngắn từng lượt.
     */
    public function sanitizeHistory(array $history, int $maxTurns, int $maxChars): array
    {
        $private = ['order_status', 'order_list', 'loyalty', 'coupon', 'child_gift', 'human_handoff'];

        return collect($history)
            ->take(-$maxTurns)
            ->map(function (array $turn) use ($private, $maxChars) {
                $isBot = ($turn['role'] ?? 'user') === 'assistant';

                $content = $isBot && in_array($turn['intent'] ?? null, $private, true)
                    ? '(Trợ lý đã trả lời bằng dữ liệu tài khoản của khách - nội dung được ẩn.)'
                    : $this->maskPii((string) ($turn['content'] ?? ''));

                return [
                    'role' => $isBot ? 'assistant' : 'user',
                    'content' => Str::limit(trim($content), $maxChars, '…'),
                ];
            })
            ->filter(fn ($turn) => $turn['content'] !== '')
            ->values()
            ->all();
    }
}
