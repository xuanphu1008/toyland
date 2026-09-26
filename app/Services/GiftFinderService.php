<?php

namespace App\Services;

use App\Models\ChildProfile;
use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * TÌM QUÀ CHO MỘT ĐỨA TRẺ CỤ THỂ
 *
 * Khác gì với RecommendationService?
 *   RecommendationService trả lời "ai mua món A cũng mua món B" - nó học từ
 *   hành vi mua hàng của cả cửa hàng.
 *   Lớp này trả lời "bé 5 tuổi, thích Spider-Man, thì hợp món nào" - nó chấm
 *   điểm theo ĐẶC ĐIỂM CỦA ĐỨA TRẺ, không cần bất kỳ lịch sử mua hàng nào.
 *
 * Vì vậy nó hoạt động ngay từ khách hàng đầu tiên, trong khi lọc cộng tác cần
 * hàng trăm đơn mới bắt đầu có ý nghĩa. Hai cái bổ sung cho nhau chứ không
 * thay thế nhau.
 *
 * CÁCH CHẤM ĐIỂM - tổng của các tín hiệu độc lập:
 *
 *   Độ tuổi       0 .. 5.0   trọng số lớn nhất: đồ chơi sai tuổi là đồ chơi hỏng
 *   Nhân vật      0 .. 3.0   bé mê Spider-Man thì món có Spider-Man luôn thắng
 *   Danh mục      0 .. 2.0   danh mục bé thích nhất
 *   Giới tính    -1.5 .. 1.5 âm khi lệch hẳn, vì gợi ý sai là phản tác dụng
 *   Độ phổ biến   0 .. 1.0   hòa điểm thì ưu tiên món nhiều người mua
 *   Nổi bật       0 .. 0.5   shop đang muốn đẩy
 */
class GiftFinderService
{
    /** Chấm điểm trên bao nhiêu ứng viên - đủ rộng mà vẫn nhẹ */
    private const CANDIDATE_LIMIT = 120;

    /**
     * Gợi ý quà cho một bé.
     *
     * @param  int|null  $ageMonths  Tuổi dùng để gợi ý, tính bằng tháng.
     *                               Để trống thì lấy tuổi hiện tại của bé.
     *                               Trang Sổ sinh nhật truyền vào tuổi bé SẮP tròn.
     */
    public function forChild(ChildProfile $child, int $limit = 4, ?int $ageMonths = null): Collection
    {
        return $this->search(
            $ageMonths ?? $child->age_months,
            $child->gender,
            $child->interest_list,
            $child->favorite_category_id,
            $limit
        );
    }

    /**
     * Bộ tìm quà tổng quát - dùng cho cả hồ sơ bé lẫn phễu chọn quà nhanh.
     */
    public function search(
        int $ageMonths,
        ?string $gender = null,
        array $interests = [],
        ?int $categoryId = null,
        int $limit = 4,
        array $excludeIds = []
    ): Collection {
        $candidates = $this->candidates($ageMonths, $excludeIds);

        if ($candidates->isEmpty()) {
            return collect();
        }

        $maxSold = max(1, (int) $candidates->max('sold_quantity'));

        return $candidates
            ->map(function (Product $product) use ($ageMonths, $gender, $interests, $categoryId, $maxSold) {
                $product->setAttribute(
                    'gift_score',
                    round($this->score($product, $ageMonths, $gender, $interests, $categoryId, $maxSold), 3)
                );

                return $product;
            })
            ->sortByDesc('gift_score')
            ->take($limit)
            ->values();
    }

    // ===================================================================

    /**
     * Lấy tập ứng viên: còn hàng, và khoảng tuổi của món có GIAO với vùng
     * tuổi của bé (nới rộng ±12 tháng để không bỏ sót món hơi lệch tuổi).
     * Lọc thô ở tầng SQL cho nhẹ, chấm điểm tinh ở tầng PHP.
     */
    private function candidates(int $ageMonths, array $excludeIds): Collection
    {
        $low = max(0, $ageMonths - 12);
        $high = $ageMonths + 12;

        return Product::query()
            ->with('category')
            ->withSoldQuantity()
            ->withAvg('visibleReviews as visible_reviews_avg_rating', 'rating')
            ->where('quantity', '>', 0)
            ->when($excludeIds, fn ($q) => $q->whereNotIn('id', $excludeIds))
            ->where(function ($q) use ($low, $high) {
                $q->where('age_min_months', '<=', $high)
                    ->where(function ($sub) use ($low) {
                        $sub->whereNull('age_max_months')->orWhere('age_max_months', '>=', $low);
                    });
            })
            ->orderByDesc('sold_quantity')
            ->limit(self::CANDIDATE_LIMIT)
            ->get();
    }

    private function score(
        Product $product,
        int $ageMonths,
        ?string $gender,
        array $interests,
        ?int $categoryId,
        int $maxSold
    ): float {
        $score = $this->ageScore($product, $ageMonths);

        // ---- Nhân vật bé thích ----
        if ($interests && filled($product->character_name)) {
            foreach ($interests as $interest) {
                // So sánh không phân biệt hoa thường, và chấp nhận khớp một phần
                // để "Marvel" bắt được cả "Marvel Avengers".
                if ($this->looselyEquals($product->character_name, $interest)) {
                    $score += 3.0;
                    break;
                }
            }
        }

        // Nhân vật bé thích cũng có thể nằm trong TÊN món (bộ LEGO Frozen chẳng hạn)
        if ($interests && $score < 8.0) {
            foreach ($interests as $interest) {
                if (mb_stripos($product->name, $interest) !== false) {
                    $score += 1.5;
                    break;
                }
            }
        }

        // ---- Danh mục bé thích ----
        if ($categoryId) {
            if ((int) $product->category_id === (int) $categoryId) {
                $score += 2.0;
            } elseif ($product->category && (int) $product->category->parent_id === (int) $categoryId) {
                // Bé thích cả nhóm cha thì các nhóm con cũng được cộng điểm
                $score += 1.2;
            }
        }

        // ---- Giới tính ----
        $score += match (true) {
            $gender === null => 0.0,
            $product->gender === $gender => 1.5,
            $product->gender === 'unisex', $product->gender === null => 0.4,
            default => -1.5,   // đồ dành hẳn cho giới tính kia: trừ điểm
        };

        // ---- Độ phổ biến ----
        $score += min(1.0, (int) $product->sold_quantity / $maxSold);

        if ($product->is_featured) {
            $score += 0.5;
        }

        return $score;
    }

    /**
     * Điểm độ tuổi - tín hiệu quan trọng nhất.
     * Nằm trọn trong khoảng tuổi của món thì ăn điểm tối đa; càng lệch xa
     * điểm càng giảm dần chứ không cắt đột ngột, để vẫn có kết quả khi kho
     * thiếu hàng đúng lứa.
     */
    private function ageScore(Product $product, int $ageMonths): float
    {
        $min = (int) ($product->age_min_months ?? 0);
        $max = $product->age_max_months !== null ? (int) $product->age_max_months : null;

        if ($ageMonths >= $min && ($max === null || $ageMonths <= $max)) {
            return 5.0;
        }

        // Lệch bao nhiêu tháng so với khoảng tuổi của món
        $distance = $ageMonths < $min ? $min - $ageMonths : $ageMonths - $max;

        return match (true) {
            $distance <= 6 => 3.0,
            $distance <= 12 => 1.5,
            $distance <= 24 => 0.5,
            default => 0.0,
        };
    }

    /** "marvel" khớp "Marvel", và "Marvel" khớp "Marvel Avengers" */
    private function looselyEquals(string $a, string $b): bool
    {
        $a = mb_strtolower(trim($a));
        $b = mb_strtolower(trim($b));

        return $a === $b || mb_strpos($a, $b) !== false || mb_strpos($b, $a) !== false;
    }
}
