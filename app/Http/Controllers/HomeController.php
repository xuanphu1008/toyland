<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ChildProfile;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductView;
use App\Models\Review;
use App\Models\Wishlist;
use App\Services\GiftFinderService;
use App\Services\RecommendationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function __construct(
        private RecommendationService $recommender,
        private GiftFinderService $giftFinder
    ) {
    }

    // Các kiểu sắp xếp cho phép ở trang khách hàng
    public const SORTS = [
        'newest' => 'Mới nhất',
        'best_selling' => 'Bán chạy nhất',
        'price_asc' => 'Giá thấp đến cao',
        'price_desc' => 'Giá cao đến thấp',
        'rating' => 'Đánh giá cao nhất',
        'name_asc' => 'Tên A → Z',
    ];

    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'integer', 'exists:categories,id'],
            'brand' => ['nullable', 'string', 'max:100'],
            'character' => ['nullable', 'string', 'max:120'],
            'gender' => ['nullable', 'in:boy,girl,unisex'],
            'age' => ['nullable', 'in:' . implode(',', array_keys(Product::AGE_BUCKETS))],
            'price_min' => ['nullable', 'integer', 'min:0'],
            'price_max' => ['nullable', 'integer', 'min:0'],
            'in_stock' => ['nullable', 'in:1'],
            'sort' => ['nullable', 'in:' . implode(',', array_keys(self::SORTS))],
        ]);

        $products = $this->baseQuery()
            ->filter($filters)
            ->sorted($filters['sort'] ?? null)
            ->paginate(12)
            ->appends($request->query());

        $categoryTree = Category::tree();
        $categories = Category::ordered()->with('parent')->get();

        // Các khối gợi ý chỉ hiện ở trang chủ mặc định, không hiện khi đang lọc/tìm kiếm
        $isFiltering = collect($filters)->filter(fn ($value) => $value !== null && $value !== '')->isNotEmpty();

        $bestSellers = $isFiltering ? collect() : Product::bestSellers(4);
        $recentlyViewed = $isFiltering ? collect() : $this->recentlyViewed(4);
        // Gợi ý riêng cho khách: gộp tín hiệu từ mọi món đã mua/đã xem
        $recommended = $isFiltering ? collect() : $this->personalRecommendations(4);
        $latestPosts = $isFiltering ? collect() : Post::published()->orderByDesc('published_at')->limit(3)->get();

        // Hàng đang giảm giá theo khung giờ. Lọc thô ở SQL rồi lọc tinh lại bằng
        // accessor is_on_sale để chỉ có MỘT định nghĩa "đang sale" duy nhất.
        $flashSale = $isFiltering ? collect() : Product::onFlashSale()
            ->with('category')
            ->withSoldQuantity()
            ->withAvg('visibleReviews as visible_reviews_avg_rating', 'rating')
            ->orderBy('sale_ends_at')
            ->limit(8)
            ->get()
            ->filter->is_on_sale
            ->values();

        // Khối gợi ý riêng cho BÉ ĐANG ĐƯỢC CHỌN.
        // Khác với $recommended (học từ lịch sử mua của tài khoản), khối này
        // chấm điểm theo đặc điểm của đứa trẻ nên có kết quả ngay cả với khách
        // vừa đăng ký xong, chưa mua món nào.
        $activeChild = $this->activeChild();
        $childPicks = ($activeChild && ! $isFiltering)
            ? $this->giftFinder->forChild($activeChild, 4)
            : collect();

        // Dữ liệu đổ vào các ô select của bộ lọc
        $brands = Product::whereNotNull('brand')->where('brand', '!=', '')
            ->distinct()->orderBy('brand')->pluck('brand');
        $characters = Product::whereNotNull('character_name')->where('character_name', '!=', '')
            ->distinct()->orderBy('character_name')->pluck('character_name');

        $sorts = self::SORTS;
        $genders = Product::GENDERS;
        $ageBuckets = Product::AGE_BUCKETS;
        $wishlistIds = $this->wishlistIds();

        return view('welcome', compact(
            'products', 'categories', 'categoryTree', 'bestSellers', 'recentlyViewed', 'recommended', 'latestPosts',
            'brands', 'characters', 'ageBuckets', 'sorts', 'genders', 'filters', 'isFiltering', 'wishlistIds',
            'childPicks', 'flashSale'
        ));
    }

    /**
     * Bé đang được chọn trong phiên duyệt web.
     * Luôn lọc theo user_id nên id rác trong session chỉ trả về null.
     */
    private function activeChild(): ?ChildProfile
    {
        $id = session('active_child_id');

        if (! $id || ! Auth::check()) {
            return null;
        }

        return ChildProfile::where('user_id', Auth::id())->find($id);
    }

    /**
     * Trang "Danh mục" riêng đã được gỡ bỏ: toàn bộ danh mục cha - con nay nằm
     * ngay trên menu và trong bộ lọc của trang chủ nên trang này bị trùng lặp.
     * Giữ lại route dưới dạng chuyển hướng để các link/bookmark cũ không bị 404.
     */
    public function categories(Request $request)
    {
        return redirect()->route('home', $request->only('category'));
    }

    // Route Model Binding: tự động trả về 404 nếu $product không tồn tại
    public function show(Product $product)
    {
        $product->load('category');

        // Ghi nhận lịch sử duyệt sản phẩm của khách (không tính admin)
        $this->trackView($product);

        // Danh sách đánh giá đang hiển thị, mới nhất lên đầu
        $reviews = $product->visibleReviews()->with('user')->latest()->get();

        $ratingAvg = round((float) $reviews->avg('rating'), 1);
        $ratingCount = $reviews->count();

        // Gán sẵn điểm trung bình để accessor `rating`/`stars` không phải query lại DB
        $product->setAttribute('visible_reviews_avg_rating', $ratingAvg);

        // Phân bố số sao 5→1 để vẽ thanh tỉ lệ
        $ratingBreakdown = collect(range(5, 1))->mapWithKeys(fn ($star) => [
            $star => $reviews->where('rating', $star)->count(),
        ]);

        $myReview = null;
        $canReview = false;
        $isWishlisted = false;

        if (Auth::check() && ! Auth::user()->isAdmin()) {
            $myReview = Review::where('user_id', Auth::id())->where('product_id', $product->id)->first();
            $canReview = Auth::user()->hasPurchased($product->id);
            $isWishlisted = Auth::user()->hasWishlisted($product->id);
        }

        // "Khách mua món này cũng mua..." - lọc cộng tác trên dữ liệu đơn hàng thật,
        // tự chuyển sang gợi ý theo nội dung khi shop chưa đủ đơn để học.
        $related = $this->recommender->alsoBought($product, 4);

        $wishlistIds = $this->wishlistIds();

        return view('products.show', compact(
            'product', 'reviews', 'ratingAvg', 'ratingCount', 'ratingBreakdown',
            'myReview', 'canReview', 'isWishlisted', 'related', 'wishlistIds'
        ));
    }

    // ===================== HÀM DÙNG CHUNG =====================

    /**
     * Query sản phẩm chuẩn cho trang khách: kèm danh mục, số đã bán,
     * điểm đánh giá trung bình và số lượt đánh giá.
     */
    private function baseQuery()
    {
        return Product::with('category')
            ->withSoldQuantity()
            ->withAvg('visibleReviews as visible_reviews_avg_rating', 'rating')
            ->withCount('visibleReviews');
    }

    // ID các sản phẩm khách đang để trong danh sách yêu thích (để tô đậm nút trái tim)
    private function wishlistIds(): array
    {
        if (! Auth::check()) {
            return [];
        }

        return Wishlist::where('user_id', Auth::id())->pluck('product_id')->all();
    }

    /**
     * Ghi/cập nhật lịch sử duyệt sản phẩm.
     * updateOrCreate nên mỗi sản phẩm chỉ có 1 dòng, chỉ cập nhật thời điểm xem gần nhất.
     */
    private function trackView(Product $product): void
    {
        if (! Auth::check() || Auth::user()->isAdmin()) {
            return;
        }

        ProductView::updateOrCreate(
            ['user_id' => Auth::id(), 'product_id' => $product->id],
            ['viewed_at' => now()]
        );
    }

    // Sản phẩm khách vừa xem gần đây
    private function recentlyViewed(int $limit)
    {
        if (! Auth::check() || Auth::user()->isAdmin()) {
            return collect();
        }

        $ids = ProductView::where('user_id', Auth::id())
            ->orderByDesc('viewed_at')
            ->limit($limit)
            ->pluck('product_id')
            ->all();

        if (empty($ids)) {
            return collect();
        }

        // Giữ đúng thứ tự "xem gần nhất trước" sau khi lấy ra khỏi DB
        return $this->baseQuery()->whereIn('id', $ids)->get()
            ->sortBy(fn ($product) => array_search($product->id, $ids))
            ->values();
    }

    /**
     * Gợi ý riêng cho từng khách.
     * Ủy quyền cho RecommendationService - nơi chứa thuật toán lọc cộng tác.
     * Controller chỉ lo phần "ai đang đăng nhập", không ôm luôn thuật toán.
     */
    private function personalRecommendations(int $limit)
    {
        if (! Auth::check() || Auth::user()->isAdmin()) {
            return Product::bestSellers($limit);
        }

        return $this->recommender->forUser(Auth::id(), $limit);
    }
}
