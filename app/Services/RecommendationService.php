<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductView;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * BỘ GỢI Ý LAI (hybrid recommender)
 *
 * Gồm hai thuật toán, tự chọn theo lượng dữ liệu đang có:
 *
 * 1) LỌC CỘNG TÁC theo sản phẩm (item-based collaborative filtering)
 *    Ý tưởng: hai món hay được mua CÙNG MỘT ĐƠN thì liên quan tới nhau.
 *    Đếm số đơn chứa cả A lẫn B, rồi chuẩn hóa bằng cosine trên vector nhị phân:
 *
 *                      |đơn chứa cả A và B|
 *        sim(A,B) = ───────────────────────────────
 *                   √|đơn chứa A| × √|đơn chứa B|
 *
 *    Chia cho căn bậc hai độ phổ biến để món BÁN CHẠY không tự động
 *    trở thành "liên quan" với tất cả mọi thứ. Không có bước này thì
 *    sản phẩm hot sẽ chiếm hết mọi ô gợi ý.
 *
 * 2) GỢI Ý THEO NỘI DUNG (content-based)
 *    Khi shop còn ít đơn (bài toán "khởi đầu lạnh" - cold start), bước 1
 *    không có dữ liệu để học. Lúc đó chấm điểm theo thuộc tính sản phẩm:
 *    cùng danh mục, khoảng tuổi giao nhau, cùng nhân vật, cùng hãng, giá gần nhau.
 *
 * Kết quả cache lại vì phép tự nối bảng order_items khá nặng.
 */
class RecommendationService
{
    /** Thời gian cache (giây) */
    private const CACHE_TTL = 900;

    /** Dưới ngưỡng này coi như chưa đủ dữ liệu cộng tác -> chuyển sang theo nội dung */
    private const MIN_CO_PURCHASE = 1;

    // ===================================================================
    // 1. "KHÁCH MUA MÓN NÀY CŨNG MUA..."
    // ===================================================================

    public function alsoBought(Product $product, int $limit = 4): Collection
    {
        $cacheKey = "reco.also_bought.{$product->id}.{$limit}";

        $ids = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($product, $limit) {
            return $this->collaborativeIds($product->id, $limit);
        });

        $collaborative = $this->loadProducts($ids);

        // Chưa đủ dữ liệu mua chung -> bù bằng gợi ý theo nội dung
        if ($collaborative->count() < $limit) {
            $exclude = $collaborative->pluck('id')->push($product->id)->all();
            $fallback = $this->contentBased($product, $limit - $collaborative->count(), $exclude);

            $collaborative = $collaborative->concat($fallback);
        }

        return $collaborative->take($limit)->values();
    }

    /**
     * Truy vấn đồng xuất hiện: tự nối order_items với chính nó trên cùng order_id.
     * Viết bằng query builder để chạy được trên cả SQL Server lẫn MySQL.
     *
     * @return array<int, float> [product_id => điểm tương đồng], đã sắp giảm dần
     */
    private function collaborativeIds(int $productId, int $limit): array
    {
        // Bước 1: đếm số đơn (không bị hủy) chứa CẢ sản phẩm gốc lẫn từng sản phẩm khác
        $pairs = DB::table('order_items as a')
            ->join('order_items as b', function ($join) {
                $join->on('a.order_id', '=', 'b.order_id')
                    ->whereColumn('a.product_id', '!=', 'b.product_id');
            })
            ->join('orders as o', 'o.id', '=', 'a.order_id')
            ->where('a.product_id', $productId)
            ->whereNotIn('o.status', \App\Models\Order::EXCLUDED_FROM_SALES)
            ->groupBy('b.product_id')
            ->select('b.product_id')
            ->selectRaw('COUNT(DISTINCT a.order_id) as co_count')
            ->havingRaw('COUNT(DISTINCT a.order_id) >= ?', [self::MIN_CO_PURCHASE])
            ->get();

        if ($pairs->isEmpty()) {
            return [];
        }

        // Bước 2: lấy độ phổ biến của từng sản phẩm để chuẩn hóa
        $candidateIds = $pairs->pluck('product_id')->push($productId)->unique()->all();
        $popularity = $this->orderCountFor($candidateIds);

        $baseCount = max(1, $popularity[$productId] ?? 1);

        $scores = [];
        foreach ($pairs as $pair) {
            $otherCount = max(1, $popularity[$pair->product_id] ?? 1);

            // cosine trên vector nhị phân "đơn nào có chứa món này"
            $scores[(int) $pair->product_id] = $pair->co_count / sqrt($baseCount * $otherCount);
        }

        arsort($scores);

        return array_slice($scores, 0, $limit, true);
    }

    /** Số đơn hàng (không hủy) có chứa từng sản phẩm */
    private function orderCountFor(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }

        return DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->whereIn('oi.product_id', $productIds)
            ->whereNotIn('o.status', \App\Models\Order::EXCLUDED_FROM_SALES)
            ->groupBy('oi.product_id')
            ->select('oi.product_id')
            ->selectRaw('COUNT(DISTINCT oi.order_id) as order_count')
            ->pluck('order_count', 'product_id')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    // ===================================================================
    // 2. GỢI Ý THEO NỘI DUNG (dùng khi thiếu dữ liệu mua chung)
    // ===================================================================

    /**
     * Chấm điểm mức giống nhau giữa hai sản phẩm dựa trên thuộc tính.
     * Thang điểm cố ý cho danh mục con trọng số cao nhất vì đó là tín hiệu
     * rõ nhất về "cùng loại đồ chơi".
     */
    public function contentBased(Product $product, int $limit = 4, array $excludeIds = []): Collection
    {
        $excludeIds[] = $product->id;

        // Ưu tiên ứng viên cùng nhánh danh mục, nhưng không loại hẳn phần còn lại
        $branch = $product->category
            ? array_merge([$product->category->parent_id], $product->category->descendantIds())
            : [$product->category_id];

        $branch = array_values(array_unique(array_map('intval', array_filter($branch))));

        $candidates = Product::query()
            ->with('category')
            ->withSoldQuantity()
            ->where('quantity', '>', 0)
            ->whereNotIn('id', array_unique($excludeIds))
            // Danh sách id đã ép kiểu int ở trên nên nhúng thẳng vào SQL là an toàn.
            // Mảng rỗng thì bỏ qua hẳn, vì `IN ()` là cú pháp sai trên SQL Server.
            ->when(! empty($branch), fn ($q) => $q->orderByRaw(
                'CASE WHEN category_id IN (' . implode(',', $branch) . ') THEN 0 ELSE 1 END'
            ))
            ->limit(80)   // chỉ chấm điểm trên tập ứng viên gần nhất cho nhẹ
            ->get();

        $scored = $candidates->map(function (Product $candidate) use ($product) {
            $candidate->setAttribute('reco_score', $this->similarityScore($product, $candidate));

            return $candidate;
        });

        return $scored->sortByDesc('reco_score')->take($limit)->values();
    }

    private function similarityScore(Product $a, Product $b): float
    {
        $score = 0.0;

        // Cùng danh mục con: tín hiệu mạnh nhất
        if ($a->category_id && $a->category_id === $b->category_id) {
            $score += 4.0;
        } elseif ($a->category && $b->category && $a->category->parent_id
            && $a->category->parent_id === $b->category->parent_id) {
            $score += 2.5;   // cùng nhóm cha
        }

        // Cùng nhân vật/chủ đề - bé mê Marvel thì gợi ý Marvel
        if ($a->character_name && $a->character_name === $b->character_name) {
            $score += 3.0;
        }

        if ($a->brand && $a->brand === $b->brand) {
            $score += 1.5;
        }

        // Khoảng tuổi có giao nhau
        $score += $this->ageOverlapScore($a, $b);

        if ($a->gender && $a->gender === $b->gender) {
            $score += 0.5;
        }

        // Giá gần nhau: chênh càng ít điểm càng cao (tối đa 2 điểm)
        if ($a->price > 0 && $b->price > 0) {
            $ratio = min($a->price, $b->price) / max($a->price, $b->price);
            $score += $ratio * 2.0;
        }

        // Nhích nhẹ theo mức độ được ưa chuộng để hai món ngang điểm
        // thì món nhiều người mua hơn lên trước
        $score += min(1.0, (int) ($b->sold_quantity ?? 0) / 50);

        return round($score, 3);
    }

    /** 2 điểm nếu khoảng tuổi trùng khít, giảm dần theo mức chồng lấn */
    private function ageOverlapScore(Product $a, Product $b): float
    {
        $aMin = $a->age_min_months;
        $aMax = $a->age_max_months ?? 216;
        $bMin = $b->age_min_months;
        $bMax = $b->age_max_months ?? 216;

        if ($aMin === null || $bMin === null) {
            return 0.0;
        }

        $overlap = min($aMax, $bMax) - max($aMin, $bMin);

        if ($overlap <= 0) {
            return 0.0;
        }

        $aSpan = max(1, $aMax - $aMin);

        return round(min(1.0, $overlap / $aSpan) * 2.0, 3);
    }

    // ===================================================================
    // 3. GỢI Ý RIÊNG CHO TỪNG KHÁCH
    // ===================================================================

    /**
     * Gộp gợi ý từ tất cả sản phẩm khách đã MUA và đã XEM.
     * Sản phẩm đã mua có trọng số gấp đôi sản phẩm mới chỉ xem qua.
     */
    public function forUser(int $userId, int $limit = 4): Collection
    {
        $purchasedIds = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->where('o.user_id', $userId)
            ->whereNotIn('o.status', \App\Models\Order::EXCLUDED_FROM_SALES)
            ->distinct()
            ->pluck('oi.product_id')
            ->map(fn ($v) => (int) $v)
            ->all();

        $viewedIds = ProductView::where('user_id', $userId)
            ->orderByDesc('viewed_at')
            ->limit(10)
            ->pluck('product_id')
            ->map(fn ($v) => (int) $v)
            ->all();

        $seedIds = array_slice(array_unique(array_merge($purchasedIds, $viewedIds)), 0, 12);

        if (empty($seedIds)) {
            return Product::bestSellers($limit);
        }

        // Cộng dồn điểm từ mọi hạt giống
        $aggregate = [];

        foreach ($seedIds as $seedId) {
            $weight = in_array($seedId, $purchasedIds, true) ? 2.0 : 1.0;

            foreach ($this->collaborativeIds($seedId, 10) as $productId => $score) {
                $aggregate[$productId] = ($aggregate[$productId] ?? 0) + ($score * $weight);
            }
        }

        // Không gợi ý lại thứ khách đã mua hoặc vừa xem
        foreach ($seedIds as $seedId) {
            unset($aggregate[$seedId]);
        }

        arsort($aggregate);

        $result = $this->loadProducts(array_slice($aggregate, 0, $limit, true));

        // Dữ liệu còn thưa -> bù bằng gợi ý theo nội dung của món gần nhất
        if ($result->count() < $limit) {
            $seedProduct = Product::with('category')->find($seedIds[0]);

            if ($seedProduct) {
                $exclude = array_merge($seedIds, $result->pluck('id')->all());
                $result = $result->concat(
                    $this->contentBased($seedProduct, $limit - $result->count(), $exclude)
                );
            }
        }

        if ($result->count() < $limit) {
            $result = $result->concat(
                Product::bestSellers($limit * 2)
                    ->reject(fn ($p) => $result->contains('id', $p->id) || in_array($p->id, $seedIds, true))
                    ->take($limit - $result->count())
            );
        }

        return $result->take($limit)->values();
    }

    // ===================================================================
    // TIỆN ÍCH
    // ===================================================================

    /**
     * Nạp model theo ĐÚNG thứ tự điểm số.
     * whereIn trả về theo thứ tự của DB nên phải sắp lại thủ công,
     * nếu không thì công sức chấm điểm ở trên thành vô nghĩa.
     */
    private function loadProducts(array $scoreMap): Collection
    {
        if (empty($scoreMap)) {
            return collect();
        }

        $ids = array_keys($scoreMap);

        $products = Product::with('category')
            ->withSoldQuantity()
            ->withAvg('visibleReviews as visible_reviews_avg_rating', 'rating')
            ->withCount('visibleReviews')
            ->whereIn('id', $ids)
            ->where('quantity', '>', 0)
            ->get();

        return $products
            ->each(fn ($p) => $p->setAttribute('reco_score', round($scoreMap[$p->id] ?? 0, 3)))
            ->sortByDesc('reco_score')
            ->values();
    }

    /** Xóa cache - gọi sau khi có đơn mới để gợi ý cập nhật kịp thời */
    public function flush(): void
    {
        Cache::forget('reco.stats');

        // Cache theo từng sản phẩm nên không xóa hàng loạt được;
        // TTL 15 phút đủ để dữ liệu tự làm mới.
    }

    /** Thống kê cho trang quản trị: bộ gợi ý đang có bao nhiêu dữ liệu để học */
    public function stats(): array
    {
        return Cache::remember('reco.stats', 300, function () {
            $ordersWithMultipleItems = DB::table('order_items')
                ->select('order_id')
                ->groupBy('order_id')
                ->havingRaw('COUNT(*) > 1')
                ->get()
                ->count();

            $totalOrders = DB::table('orders')->whereNotIn('status', \App\Models\Order::EXCLUDED_FROM_SALES)->count();

            return [
                'total_orders' => $totalOrders,
                'multi_item_orders' => $ordersWithMultipleItems,
                'coverage' => $totalOrders > 0
                    ? round($ordersWithMultipleItems / $totalOrders * 100, 1)
                    : 0.0,
            ];
        });
    }
}
