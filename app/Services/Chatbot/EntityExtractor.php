<?php

namespace App\Services\Chatbot;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;

/**
 * Bóc tách THỰC THỂ (entity) từ câu nói tự nhiên của khách.
 *
 * Ví dụ: "tìm quà cho bé gái 5 tuổi thích Elsa, tầm 300k"
 *   -> age_months  = 60
 *      gender      = girl
 *      character   = Elsa
 *      price_max   = 300000
 *
 * Kết quả trả về đúng định dạng mà Product::scopeFilter() đang nhận, nên
 * chatbot dùng lại y nguyên bộ lọc của trang chủ - không viết lại logic tìm kiếm.
 */
class EntityExtractor
{
    /** Cách viết tắt tiền tệ người Việt hay dùng */
    private const MONEY_UNITS = [
        'trieu' => 1000000,
        'tr' => 1000000,
        'củ' => 1000000,
        'nghin' => 1000,
        'ngan' => 1000,
        'k' => 1000,
        'd' => 1,
        'dong' => 1,
        'vnd' => 1,
    ];

    public function extract(string $message): array
    {
        $text = TextAnalyzer::normalize($message);

        return array_filter([
            'age_months' => $this->extractAge($text),
            'gender' => $this->extractGender($text),
            'price_min' => $this->extractPriceMin($text),
            'price_max' => $this->extractPriceMax($text),
            'brand' => $this->matchFromDatabase($text, 'brand'),
            'character' => $this->matchFromDatabase($text, 'character_name'),
            'category_id' => $this->extractCategory($text),
            'order_id' => $this->extractOrderId($message),
        ], fn ($value) => $value !== null);
    }

    /**
     * Tuổi -> quy về THÁNG để so với age_min_months/age_max_months của sản phẩm.
     * Bắt được: "3 tuổi", "bé 3t", "8 tháng", "lớp 1", "sơ sinh".
     */
    private function extractAge(string $text): ?int
    {
        // "8 thang", "8 thang tuoi"
        if (preg_match('/(\d{1,2})\s*thang/u', $text, $m)) {
            return (int) $m[1];
        }

        // "3 tuoi", "3t", "be 3 tuoi"
        if (preg_match('/(\d{1,2})\s*(tuoi|t\b)/u', $text, $m)) {
            $years = (int) $m[1];

            // Chặn số vô lý: đồ chơi trẻ em không có "50 tuổi"
            return $years >= 0 && $years <= 18 ? $years * 12 : null;
        }

        // "lop 1" -> khoảng 6 tuổi, "lop 5" -> khoảng 10 tuổi
        if (preg_match('/lop\s*(\d{1,2})/u', $text, $m)) {
            $grade = (int) $m[1];

            return $grade >= 1 && $grade <= 12 ? ($grade + 5) * 12 : null;
        }

        if (TextAnalyzer::containsAny($text, ['so sinh', 'moi sinh', 'newborn'])) {
            return 3;
        }

        if (TextAnalyzer::containsAny($text, ['mam non', 'mau giao', 'nha tre'])) {
            return 48;
        }

        if (TextAnalyzer::containsAny($text, ['tieu hoc', 'cap 1'])) {
            return 84;
        }

        return null;
    }

    private function extractGender(string $text): ?string
    {
        if (TextAnalyzer::containsAny($text, ['be gai', 'con gai', 'chau gai', 'em gai', 'cho gai', 'bang gai'])) {
            return 'girl';
        }

        if (TextAnalyzer::containsAny($text, ['be trai', 'con trai', 'chau trai', 'em trai', 'thang cu'])) {
            return 'boy';
        }

        return null;
    }

    /** "tu 200k", "tren 500 nghin", "gia tu 1 trieu" */
    private function extractPriceMin(string $text): ?int
    {
        if (preg_match('/(?:tu|tren|hon|lon hon)\s+([\d.,]+)\s*(trieu|tr|nghin|ngan|k|dong|d|vnd)?/u', $text, $m)) {
            return $this->parseMoney($m[1], $m[2] ?? null);
        }

        return null;
    }

    /** "duoi 300k", "khoang 500 nghin", "toi da 1 trieu", "tam 200k" */
    private function extractPriceMax(string $text): ?int
    {
        $patterns = [
            '/(?:duoi|nho hon|toi da|khong qua|re hon)\s+([\d.,]+)\s*(trieu|tr|nghin|ngan|k|dong|d|vnd)?/u',
            '/(?:khoang|tam|gia)\s+([\d.,]+)\s*(trieu|tr|nghin|ngan|k|dong|d|vnd)/u',
            // "den 500k" trong cụm "tu 200k den 500k"
            '/den\s+([\d.,]+)\s*(trieu|tr|nghin|ngan|k|dong|d|vnd)/u',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                return $this->parseMoney($m[1], $m[2] ?? null);
            }
        }

        return null;
    }

    /**
     * "300" + "k" -> 300000 ; "1.5" + "trieu" -> 1500000 ; "300000" -> 300000
     * Không có đơn vị mà số nhỏ (<= 2000) thì đoán là đang nói tắt theo nghìn.
     */
    private function parseMoney(string $number, ?string $unit): ?int
    {
        // "1.200.000" -> 1200000 ; "1,5" -> 1.5
        $clean = str_replace(',', '.', $number);
        $dotCount = substr_count($clean, '.');

        if ($dotCount > 1 || (str_contains($clean, '.') && strlen(substr($clean, strrpos($clean, '.') + 1)) === 3)) {
            $clean = str_replace('.', '', $clean);
        }

        $value = (float) $clean;

        if ($value <= 0) {
            return null;
        }

        if ($unit && isset(self::MONEY_UNITS[$unit])) {
            return (int) round($value * self::MONEY_UNITS[$unit]);
        }

        // Không nêu đơn vị: "dưới 300" gần như chắc chắn là 300 nghìn
        return (int) round($value <= 2000 ? $value * 1000 : $value);
    }

    /**
     * Dò thương hiệu / nhân vật bằng cách đối chiếu với GIÁ TRỊ THẬT trong DB.
     * Cách này tự cập nhật: admin thêm hãng mới là bot nhận ra ngay, không phải
     * sửa code hay bổ sung từ điển.
     */
    private function matchFromDatabase(string $text, string $column): ?string
    {
        $values = Cache::remember("chatbot.distinct.$column", 600, function () use ($column) {
            return Product::whereNotNull($column)
                ->where($column, '!=', '')
                ->distinct()
                ->pluck($column)
                ->all();
        });

        $best = null;
        $bestLength = 0;

        foreach ($values as $value) {
            $normalized = TextAnalyzer::normalize($value);

            if ($normalized === '') {
                continue;
            }

            // Khớp nguyên cụm, ưu tiên cụm DÀI nhất:
            // "Disney Princess" phải thắng "Disney" khi câu có cả hai.
            if (str_contains($text, $normalized) && mb_strlen($normalized) > $bestLength) {
                $best = $value;
                $bestLength = mb_strlen($normalized);
            }
        }

        return $best;
    }

    /** Dò danh mục theo tên (cả cha lẫn con) */
    private function extractCategory(string $text): ?int
    {
        $categories = Cache::remember('chatbot.categories', 600, function () {
            return Category::query()->get(['id', 'name'])->map(fn ($c) => [
                'id' => $c->id,
                'norm' => TextAnalyzer::normalize($c->name),
            ])->all();
        });

        $best = null;
        $bestLength = 0;

        foreach ($categories as $category) {
            if ($category['norm'] === '') {
                continue;
            }

            if (str_contains($text, $category['norm']) && mb_strlen($category['norm']) > $bestLength) {
                $best = $category['id'];
                $bestLength = mb_strlen($category['norm']);
            }
        }

        return $best;
    }

    /** "đơn #12", "đơn hàng số 12", "ma don 12" */
    private function extractOrderId(string $rawMessage): ?int
    {
        $text = TextAnalyzer::normalize($rawMessage);

        $patterns = [
            '/don\s*(?:hang)?\s*(?:so|ma|#)?\s*(\d{1,10})/u',
            '/#\s*(\d{1,10})/u',
            '/ma\s*don\s*(?:hang)?\s*(\d{1,10})/u',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                return (int) $m[1];
            }
        }

        // Câu chỉ có mỗi con số, lại đang trong ngữ cảnh hỏi đơn hàng
        if (preg_match('/^\s*(\d{1,10})\s*$/u', $text, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    /**
     * Chuyển entity thành mảng filter cho Product::scopeFilter().
     * Tuổi (tháng) -> chọn khoảng tuổi (age bucket) phù hợp nhất.
     */
    public function toProductFilters(array $entities): array
    {
        $filters = [];

        if (isset($entities['category_id'])) {
            $filters['category'] = $entities['category_id'];
        }

        if (isset($entities['brand'])) {
            $filters['brand'] = $entities['brand'];
        }

        if (isset($entities['character'])) {
            $filters['character'] = $entities['character'];
        }

        if (isset($entities['gender'])) {
            $filters['gender'] = $entities['gender'];
        }

        if (isset($entities['price_min'])) {
            $filters['price_min'] = $entities['price_min'];
        }

        if (isset($entities['price_max'])) {
            $filters['price_max'] = $entities['price_max'];
        }

        if (isset($entities['age_months'])) {
            $filters['age'] = $this->ageBucketFor((int) $entities['age_months']);
        }

        return array_filter($filters, fn ($v) => $v !== null);
    }

    /** 60 tháng -> '3-6y' (khoảng tuổi chứa mốc đó) */
    public function ageBucketFor(int $months): ?string
    {
        foreach (Product::AGE_BUCKETS as $key => $bucket) {
            $max = $bucket['max'];

            if ($months >= $bucket['min'] && ($max === null || $months < $max)) {
                return $key;
            }
        }

        return null;
    }
}
