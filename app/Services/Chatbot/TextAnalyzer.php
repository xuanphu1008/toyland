<?php

namespace App\Services\Chatbot;

/**
 * Xử lý văn bản tiếng Việt cho chatbot và bộ tìm kiếm.
 *
 * VÌ SAO PHẢI BỎ DẤU?
 * Khách gõ trên điện thoại rất hay bỏ dấu: "co do choi cho be 3 tuoi khong".
 * Nếu so chuỗi nguyên dấu thì không khớp được gì. Chuẩn hóa cả câu hỏi lẫn
 * dữ liệu về cùng một dạng KHÔNG DẤU rồi mới so sánh.
 *
 * VÌ SAO TỰ VIẾT MÀ KHÔNG DÙNG THƯ VIỆN?
 * iconv/transliterator trên Windows hay thiếu hoặc cho kết quả khác nhau giữa
 * các máy. Bảng ánh xạ thủ công dưới đây chạy giống hệt nhau ở mọi nơi.
 */
class TextAnalyzer
{
    /** Bảng ánh xạ ký tự có dấu -> không dấu */
    private const ACCENT_MAP = [
        'a' => 'àáạảãâầấậẩẫăằắặẳẵ',
        'e' => 'èéẹẻẽêềếệểễ',
        'i' => 'ìíịỉĩ',
        'o' => 'òóọỏõôồốộổỗơờớợởỡ',
        'u' => 'ùúụủũưừứựửữ',
        'y' => 'ỳýỵỷỹ',
        'd' => 'đ',
    ];

    /**
     * Từ dừng tiếng Việt - những từ xuất hiện ở mọi câu nên không mang thông tin
     * phân biệt. Loại bỏ để phép đo tương đồng không bị nhiễu.
     */
    private const STOP_WORDS = [
        'la', 'thi', 'ma', 'va', 'cua', 'cho', 'co', 'khong', 'duoc', 'nhu',
        'the', 'nao', 'gi', 'nhe', 'ah', 'oi', 'voi', 'den', 'trong',
        'ra', 'vao', 'len', 'xuong', 'toi', 'minh', 'ban', 'em', 'anh',
        'da', 'se', 'dang', 'roi', 'nay', 'kia', 'ay', 'nhung',
        'hay', 'hoac', 'neu', 'boi', 'nen', 'cung', 'van', 'moi',
        'cac', 'de', 'bi', 've', 'tai', 'theo',
    ];
    // CỐ Ý KHÔNG coi là từ dừng: "do" (đồ), "choi" (chơi), "qua" (quà),
    // "be" (bé), "tuoi" (tuổi), "gia" (giá) - đây là từ khóa cốt lõi của
    // một cửa hàng đồ chơi, bỏ đi là mất luôn ý nghĩa câu hỏi.

    private static ?array $flatMap = null;

    /**
     * "Đồ Chơi Lắp Ghép" -> "do choi lap ghep"
     * Chuyển thường, bỏ dấu, gom khoảng trắng.
     */
    public static function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text), 'UTF-8');
        $text = strtr($text, self::flatMap());

        // Giữ chữ, số và khoảng trắng; dấu câu thành khoảng trắng
        $text = preg_replace('/[^a-z0-9\s]/u', ' ', $text);

        return trim(preg_replace('/\s+/u', ' ', $text));
    }

    /** Tách câu thành mảng từ, đã bỏ từ dừng và từ quá ngắn */
    public static function tokenize(string $text, bool $removeStopWords = true): array
    {
        $words = array_filter(explode(' ', self::normalize($text)));

        if ($removeStopWords) {
            $words = array_filter($words, function ($word) {
                // Giữ lại số dù chỉ 1 ký tự: "3" trong "bé 3 tuổi" là thông tin quan trọng
                if (ctype_digit($word)) {
                    return true;
                }

                return mb_strlen($word) > 1 && ! in_array($word, self::STOP_WORDS, true);
            });
        }

        return array_values($words);
    }

    /**
     * Độ tương đồng cosine giữa hai câu, tính trên túi từ (bag of words).
     *
     *            Σ (a_i × b_i)
     *   cos(A,B) = ─────────────────────
     *            √Σa_i² × √Σb_i²
     *
     * Trả về 0.0 (không liên quan) đến 1.0 (giống hệt).
     */
    public static function cosineSimilarity(string $a, string $b): float
    {
        $tokensA = self::tokenize($a);
        $tokensB = self::tokenize($b);

        if (empty($tokensA) || empty($tokensB)) {
            return 0.0;
        }

        $freqA = array_count_values($tokensA);
        $freqB = array_count_values($tokensB);

        $dot = 0;
        foreach ($freqA as $word => $count) {
            if (isset($freqB[$word])) {
                $dot += $count * $freqB[$word];
            }
        }

        if ($dot === 0) {
            return 0.0;
        }

        $magA = sqrt(array_sum(array_map(fn ($n) => $n * $n, $freqA)));
        $magB = sqrt(array_sum(array_map(fn ($n) => $n * $n, $freqB)));

        return $dot / ($magA * $magB);
    }

    /**
     * Điểm tương đồng tổng hợp: cosine theo TỪ + Jaccard theo CẶP TỪ LIỀN NHAU.
     *
     * Chỉ dùng cosine thì "giao hàng mất bao lâu" và "hàng giao lâu mất" được
     * coi là giống hệt nhau vì cùng tập từ. Thêm bigram (cặp từ liền nhau) để
     * phân biệt thứ tự, giúp khớp FAQ chính xác hơn hẳn.
     */
    public static function similarity(string $a, string $b): float
    {
        $cosine = self::cosineSimilarity($a, $b);

        if ($cosine === 0.0) {
            return 0.0;
        }

        $bigramA = self::bigrams($a);
        $bigramB = self::bigrams($b);

        if (empty($bigramA) || empty($bigramB)) {
            return $cosine;
        }

        $intersect = count(array_intersect($bigramA, $bigramB));
        $union = count(array_unique(array_merge($bigramA, $bigramB)));
        $jaccard = $union > 0 ? $intersect / $union : 0;

        // 70% trọng số cho từ đơn, 30% cho thứ tự từ
        return ($cosine * 0.7) + ($jaccard * 0.3);
    }

    /** ["do choi", "choi lap", "lap ghep"] */
    public static function bigrams(string $text): array
    {
        $tokens = self::tokenize($text);
        $result = [];

        for ($i = 0; $i < count($tokens) - 1; $i++) {
            $result[] = $tokens[$i] . ' ' . $tokens[$i + 1];
        }

        return $result;
    }

    /**
     * Khoảng cách Levenshtein đã chuẩn hóa về 0..1 (1 = giống hệt).
     * Dùng để bắt lỗi gõ sai: "lgeo" -> "lego".
     */
    public static function fuzzyMatch(string $a, string $b): float
    {
        $a = self::normalize($a);
        $b = self::normalize($b);

        if ($a === $b) {
            return 1.0;
        }

        $maxLen = max(mb_strlen($a), mb_strlen($b));

        if ($maxLen === 0) {
            return 0.0;
        }

        // levenshtein() của PHP chỉ nhận chuỗi tối đa 255 byte
        if (strlen($a) > 255 || strlen($b) > 255) {
            return self::cosineSimilarity($a, $b);
        }

        return 1 - (levenshtein($a, $b) / $maxLen);
    }

    /** Câu hỏi có chứa BẤT KỲ từ khóa nào trong danh sách không (so ở dạng không dấu) */
    public static function containsAny(string $text, array $keywords): bool
    {
        $normalized = ' ' . self::normalize($text) . ' ';

        foreach ($keywords as $keyword) {
            if (mb_strpos($normalized, ' ' . self::normalize($keyword) . ' ') !== false) {
                return true;
            }

            // Từ khóa nhiều chữ thì cho phép khớp ở giữa câu
            if (mb_strpos(self::normalize($keyword), ' ') !== false
                && mb_strpos($normalized, self::normalize($keyword)) !== false) {
                return true;
            }
        }

        return false;
    }

    /** Đếm số từ khóa khớp - dùng để chấm điểm ý định */
    public static function countMatches(string $text, array $keywords): int
    {
        $count = 0;

        foreach ($keywords as $keyword) {
            if (self::containsAny($text, [$keyword])) {
                $count++;
            }
        }

        return $count;
    }

    /** Bảng ánh xạ phẳng: ['à' => 'a', 'á' => 'a', ...] - dựng một lần rồi dùng lại */
    private static function flatMap(): array
    {
        if (self::$flatMap !== null) {
            return self::$flatMap;
        }

        $map = [];

        foreach (self::ACCENT_MAP as $plain => $accented) {
            foreach (preg_split('//u', $accented, -1, PREG_SPLIT_NO_EMPTY) as $char) {
                $map[$char] = $plain;
            }
        }

        return self::$flatMap = $map;
    }
}
