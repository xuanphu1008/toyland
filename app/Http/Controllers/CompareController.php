<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

/**
 * SO SÁNH SẢN PHẨM
 *
 * Danh sách so sánh nằm trong SESSION chứ không phải CSDL: đây là thao tác
 * nhất thời ("hai món này chọn cái nào?"), khách vãng lai cũng phải dùng được,
 * và không ai muốn xem lại danh sách so sánh của tuần trước.
 *
 * Giới hạn 4 món vì bảng so sánh xếp theo cột - quá 4 là tràn màn hình điện
 * thoại và chẳng ai đọc nổi.
 */
class CompareController extends Controller
{
    public const MAX_ITEMS = 4;

    private const SESSION_KEY = 'compare';

    /** Bảng so sánh - mỗi hàng một thông số, mỗi cột một sản phẩm */
    public function index()
    {
        $ids = $this->ids();

        $products = $ids
            ? Product::with('category')
                ->withAvg('visibleReviews as visible_reviews_avg_rating', 'rating')
                ->withCount('visibleReviews')
                ->whereIn('id', $ids)
                ->get()
                // whereIn trả về theo thứ tự của CSDL; sắp lại theo đúng thứ tự
                // khách đã chọn để các cột không nhảy lung tung mỗi lần tải trang
                ->sortBy(fn ($p) => array_search($p->id, $ids))
                ->values()
            : collect();

        // Món đã bị gỡ khỏi hệ thống thì dọn khỏi danh sách so sánh
        if ($products->count() !== count($ids)) {
            $this->store($products->pluck('id')->all());
        }

        return view('compare.index', [
            'products' => $products,
            'rows' => $this->rows($products),
        ]);
    }

    /** Thêm hoặc bỏ một sản phẩm khỏi danh sách - một nút bấm cho cả hai việc */
    public function toggle(Request $request, Product $product)
    {
        $ids = $this->ids();
        $added = false;

        if (in_array($product->id, $ids, true)) {
            $ids = array_values(array_diff($ids, [$product->id]));
            $message = 'Đã bỏ "' . $product->name . '" khỏi danh sách so sánh.';
        } elseif (count($ids) >= self::MAX_ITEMS) {
            $message = 'Chỉ so sánh được tối đa ' . self::MAX_ITEMS . ' sản phẩm cùng lúc.';

            return $this->respond($request, $ids, $message, false, 'error');
        } else {
            $ids[] = $product->id;
            $added = true;
            $message = 'Đã thêm "' . $product->name . '" vào danh sách so sánh.';
        }

        $this->store($ids);

        return $this->respond($request, $ids, $message, $added);
    }

    public function remove(Request $request, int $id)
    {
        $ids = array_values(array_diff($this->ids(), [$id]));
        $this->store($ids);

        return $this->respond($request, $ids, 'Đã bỏ khỏi danh sách so sánh.', false);
    }

    public function clear(Request $request)
    {
        $this->store([]);

        return $this->respond($request, [], 'Đã xóa danh sách so sánh.', false);
    }

    // ===================================================================

    private function ids(): array
    {
        return array_values(array_filter(array_map('intval', (array) session(self::SESSION_KEY, []))));
    }

    private function store(array $ids): void
    {
        session([self::SESSION_KEY => array_slice(array_values(array_unique($ids)), 0, self::MAX_ITEMS)]);
    }

    /** AJAX thì trả JSON để thanh so sánh cập nhật tại chỗ; không thì quay lại trang cũ */
    private function respond(Request $request, array $ids, string $message, bool $added, string $type = 'success')
    {
        if ($request->ajax()) {
            return response()->json([
                'success' => $type === 'success',
                'added' => $added,
                'count' => count($ids),
                'ids' => $ids,
                'message' => $message,
            ]);
        }

        return back()->with($type, $message);
    }

    /**
     * Dựng các hàng của bảng so sánh.
     *
     * Mỗi hàng là [nhãn, mảng giá trị theo từng sản phẩm, có-phải-hàng-tiền-tệ].
     * Gom hết vào một chỗ như thế này để thêm một thông số mới chỉ phải sửa
     * đúng một mảng, không phải mò trong Blade.
     */
    private function rows($products): array
    {
        $map = fn (callable $fn) => $products->map($fn)->all();

        return [
            ['Giá bán', $map(fn ($p) => number_format($p->effective_price, 0, ',', '.') . ' đ'), true],
            ['Đang khuyến mãi', $map(fn ($p) => $p->is_on_sale ? '−' . $p->discount_percent . '%' : '—')],
            ['Danh mục', $map(fn ($p) => $p->category->name ?? '—')],
            ['Thương hiệu', $map(fn ($p) => $p->brand ?: '—')],
            ['Nhân vật', $map(fn ($p) => $p->character_name ?: '—')],
            ['Độ tuổi', $map(fn ($p) => $p->age_label ?: '—')],
            ['Phù hợp với', $map(fn ($p) => Product::GENDERS[$p->gender] ?? 'Bé trai & Bé gái')],
            ['Kỹ năng phát triển', $map(fn ($p) => $p->skills ?: '—')],
            ['Chất liệu', $map(fn ($p) => $p->material ?: '—')],
            ['Xuất xứ', $map(fn ($p) => $p->origin ?: '—')],
            ['Số chi tiết', $map(fn ($p) => $p->piece_count ? number_format($p->piece_count, 0, ',', '.') . ' chi tiết' : '—')],
            ['Pin', $map(fn ($p) => $p->battery ?: 'Không dùng pin')],
            ['Kích thước', $map(fn ($p) => $p->dimensions ?: '—')],
            ['Cân nặng', $map(fn ($p) => $p->weight_grams ? number_format($p->weight_grams, 0, ',', '.') . ' g' : '—')],
            ['Bảo hành', $map(fn ($p) => $p->warranty_months ? $p->warranty_months . ' tháng' : 'Không')],
            ['Đánh giá', $map(fn ($p) => $p->visible_reviews_count > 0
                ? $p->stars . ' (' . $p->visible_reviews_count . ')'
                : 'Chưa có')],
            ['Tình trạng kho', $map(fn ($p) => $p->quantity > 0
                ? 'Còn ' . number_format($p->quantity, 0, ',', '.')
                : 'Hết hàng')],
            ['Lưu ý an toàn', $map(fn ($p) => $p->safety_note ?: '—')],
        ];
    }
}
