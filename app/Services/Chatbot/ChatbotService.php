<?php

namespace App\Services\Chatbot;

use App\Models\ChildProfile;
use App\Models\Coupon;
use App\Models\Faq;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\GiftFinderService;
use App\Services\RecommendationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * BỘ NÃO CỦA CHATBOT
 *
 * Luồng xử lý một câu hỏi:
 *
 *   Câu hỏi
 *      │
 *      ├─▶ IntentMatcher   : câu này muốn gì?          (điểm tin cậy 0..1)
 *      ├─▶ EntityExtractor : trong câu có dữ kiện gì?  (tuổi, giá, mã đơn...)
 *      │
 *      ├─ Tin cậy ĐỦ  ─▶ Gọi hàm xử lý tương ứng ─▶ TRUY VẤN CSDL THẬT
 *      │
 *      └─ Tin cậy THẤP ─▶ Thử khớp với FAQ (độ tương đồng văn bản)
 *                      └─▶ Vẫn không ra ─▶ LLM (nếu có key) ─▶ Câu mặc định
 *
 * Điểm mấu chốt: mọi câu trả lời về giá, tồn kho, đơn hàng, mã giảm giá đều
 * lấy TRỰC TIẾP từ CSDL tại thời điểm hỏi. Không có câu trả lời nào viết cứng
 * về số liệu, nên bot không bao giờ nói sai giá hay sai trạng thái đơn.
 */
class ChatbotService
{
    /** Độ tương đồng tối thiểu để coi là khớp FAQ */
    private const FAQ_THRESHOLD = 0.42;

    /**
     * Những thực thể chỉ xuất hiện khi khách đang TÌM MUA HÀNG.
     * Cố ý KHÔNG có order_id - mã đơn là dấu hiệu của tra cứu đơn hàng.
     */
    private const SHOPPING_ENTITIES = [
        'age_months', 'gender', 'price_min', 'price_max', 'brand', 'character', 'category_id',
    ];

    public function __construct(
        private IntentMatcher $intents,
        private EntityExtractor $entities,
        private LlmClient $llm,
        private LlmGuard $guard,
        private LlmQuota $quota,
        private RecommendationService $recommender,
        private GiftFinderService $giftFinder
    ) {
    }

    /**
     * Trả lời một câu hỏi.
     *
     * @return array{reply: string, intent: string, confidence: float, products: array, actions: array, meta: array}
     */
    public function reply(string $message, ?User $user = null, array $history = []): array
    {
        $message = trim($message);

        if ($message === '') {
            return $this->answer('Bạn nhắn gì đó cho mình với nhé!', 'unknown', 0);
        }

        $match = $this->intents->match($message);
        $entities = $this->entities->extract($message);

        // Thực thể cũng là bằng chứng về ý định - xem chú thích ở boostWithEntities()
        $match = $this->boostWithEntities($match, $entities);

        $result = $this->handle($match['intent'], $message, $entities, $user);

        // Ý định không rõ -> thử khớp câu hỏi thường gặp
        if ($result === null) {
            $result = $this->matchFaq($message);
        }

        // Vẫn không ra -> nhờ LLM (nếu đã khai API key trong .env)
        if ($result === null) {
            $result = $this->askLlm($message, $history);
        }

        // Cuối cùng mới tới câu trả lời mặc định
        if ($result === null) {
            $result = $this->fallback();
        }

        $result['intent'] = $result['intent'] ?? $match['intent'];
        $result['confidence'] = $result['confidence'] ?? $match['confidence'];
        $result['meta'] = array_merge($result['meta'] ?? [], [
            'entities' => $entities,
            'scores' => $match['scores'],
        ]);

        return $result;
    }

    /**
     * THỰC THỂ LÀ BẰNG CHỨNG VỀ Ý ĐỊNH.
     *
     * Bộ chấm điểm từ khóa không thể liệt kê hết mọi cách người Việt hỏi mua hàng.
     * Câu "tìm món hàng cho trẻ 8 tuổi khoảng 700k cho bé gái" chỉ khớp được vài
     * từ khóa yếu nên điểm thấp, bị xếp vào "không hiểu" rồi đẩy sang LLM - mà LLM
     * KHÔNG nhìn thấy kho hàng nên nó bịa ra một món không tồn tại.
     *
     * Nhưng bộ bóc tách thực thể lại đọc được rất rõ: tuổi 8, giá ~700k, bé gái.
     * Ba dữ kiện đó chỉ có ý nghĩa khi người ta đang CHỌN ĐỒ CHƠI. Vậy nên khi
     * từ khóa bó tay mà thực thể lại đầy đủ, ta tin vào thực thể và chuyển thẳng
     * sang tìm sản phẩm thật trong CSDL.
     *
     * Chỉ can thiệp khi ý định là 'unknown' - đã hiểu rõ rồi thì không đụng vào,
     * để câu "đơn #12 mua cho bé 3 tuổi" vẫn là tra cứu đơn hàng.
     */
    private function boostWithEntities(array $match, array $entities): array
    {
        if ($match['intent'] !== 'unknown') {
            return $match;
        }

        $found = array_intersect(self::SHOPPING_ENTITIES, array_keys($entities));

        if (! $found) {
            return $match;
        }

        // Càng nhiều dữ kiện càng chắc chắn: 1 dữ kiện -> 0.50, từ 3 trở lên -> 0.80
        $confidence = min(0.8, 0.35 + 0.15 * count($found));

        $match['intent'] = 'product_search';
        $match['confidence'] = round($confidence, 4);
        $match['scores'] = array_merge(
            ['product_search' => $match['confidence']],
            $match['scores'] ?? []
        );

        return $match;
    }

    /** Điều hướng tới hàm xử lý của từng ý định */
    private function handle(string $intent, string $message, array $entities, ?User $user): ?array
    {
        return match ($intent) {
            'greeting' => $this->handleGreeting($user),
            'thanks' => $this->answer('Không có gì ạ! Bạn cần gì thêm cứ nhắn mình nhé 😊', 'thanks', 1.0),
            'order_status' => $this->handleOrderStatus($entities, $user),
            'order_list' => $this->handleOrderList($user),
            'product_search' => $this->handleProductSearch($entities),
            'bestseller' => $this->handleBestseller(),
            'shipping' => $this->handleShipping(),
            'payment' => $this->handlePayment(),
            'return_policy' => $this->handleReturnPolicy(),
            'coupon' => $this->handleCoupon($user),
            'loyalty' => $this->handleLoyalty($user),
            'store_info' => $this->handleStoreInfo(),
            'safety' => $this->handleSafety(),
            'child_gift' => $this->handleChildGift($user),
            'human_handoff' => $this->handleHandoff(),
            default => null,
        };
    }

    // ===================================================================
    // CÁC HÀM XỬ LÝ Ý ĐỊNH
    // ===================================================================

    private function handleGreeting(?User $user): array
    {
        $name = $user ? ' ' . explode(' ', trim($user->name))[array_key_last(explode(' ', trim($user->name)))] : '';

        return $this->answer(
            "Chào{$name} 👋 Mình là trợ lý của ToyLand. Mình giúp được bạn:\n"
            . "• Tìm đồ chơi theo tuổi, ngân sách, nhân vật bé thích\n"
            . "• Tra cứu đơn hàng\n"
            . "• Giải đáp phí ship, thanh toán, đổi trả\n\n"
            . 'Bạn cần gì ạ?',
            'greeting',
            1.0,
            [],
            $this->quickReplies()
        );
    }

    /** Tra cứu đơn hàng - CHỈ đơn của chính người đang đăng nhập */
    private function handleOrderStatus(array $entities, ?User $user): array
    {
        if (! $user) {
            return $this->answer(
                "Bạn đăng nhập giúp mình để tra cứu đơn hàng nhé — mình cần xác minh đúng chủ đơn mới xem được.",
                'order_status',
                1.0,
                [],
                [['label' => 'Đăng nhập', 'url' => route('login')]]
            );
        }

        $orderId = $entities['order_id'] ?? null;

        if (! $orderId) {
            // Không nêu mã đơn -> lấy đơn mới nhất
            $order = Order::where('user_id', $user->id)->latest('id')->first();

            if (! $order) {
                return $this->answer(
                    'Bạn chưa có đơn hàng nào. Ghé xem đồ chơi của shop nhé!',
                    'order_status',
                    1.0,
                    [],
                    [['label' => 'Xem đồ chơi', 'url' => route('home')]]
                );
            }

            return $this->describeOrder($order, 'Đơn gần nhất của bạn');
        }

        // QUAN TRỌNG: lọc theo user_id để khách không xem được đơn của người khác
        $order = Order::where('user_id', $user->id)->find($orderId);

        if (! $order) {
            return $this->answer(
                "Mình không tìm thấy đơn #{$orderId} trong tài khoản của bạn. "
                . 'Bạn kiểm tra lại mã đơn giúp mình nhé.',
                'order_status',
                1.0,
                [],
                [['label' => 'Xem tất cả đơn của tôi', 'url' => route('orders.index')]]
            );
        }

        return $this->describeOrder($order);
    }

    private function describeOrder(Order $order, ?string $prefix = null): array
    {
        $order->loadMissing('items.product');

        $lines = [];
        $lines[] = ($prefix ? $prefix . ' — ' : '') . "Đơn #{$order->id}";
        $lines[] = '• Trạng thái: ' . $order->status_label;
        $lines[] = '• Đặt lúc: ' . optional($order->created_at)->format('H:i d/m/Y');
        $lines[] = '• Tổng tiền: ' . number_format($order->total_price, 0, ',', '.') . ' đ';
        $lines[] = '• Thanh toán: ' . $order->payment_method_label
            . ($order->customerHasPaid() ? ' (đã thanh toán)' : ' (chưa thanh toán)');

        $itemCount = $order->items->count();
        if ($itemCount > 0) {
            $names = $order->items->take(3)->map(fn ($i) => $i->display_name ?? 'Sản phẩm')->implode(', ');
            $lines[] = '• Gồm ' . $itemCount . ' món: ' . $names . ($itemCount > 3 ? '...' : '');
        }

        if ($order->ghn_order_code) {
            $lines[] = '• Mã vận đơn: ' . $order->ghn_order_code . ' (' . $order->shipping_label . ')';
        }

        $lines[] = match ($order->status) {
            'awaiting_payment' => "\nĐơn đang chờ bạn thanh toán MoMo"
                . ($order->payment_expires_at ? ' trước ' . $order->payment_expires_at->format('H:i') : '')
                . ', quá hạn đơn sẽ tự hủy.',
            'pending' => "\nShop sẽ xác nhận đơn trong vài giờ tới.",
            'confirmed' => "\nShop đang chuẩn bị và đóng gói hàng cho bạn.",
            'packed' => "\nĐơn đã đóng gói, đang chờ đơn vị vận chuyển tới lấy.",
            'shipping' => $order->shipping_status === 'delivery_fail'
                ? "\nShipper chưa giao được lần trước, shop sẽ liên hệ để giao lại."
                : "\nĐơn đang trên đường tới bạn rồi nhé!",
            'returning', 'returned' => "\nĐơn không giao được và được hoàn về shop."
                . ($order->payment_status === 'refund_pending' ? ' Shop sẽ hoàn tiền cho bạn.' : ''),
            default => '',
        };
        $lines = array_values(array_filter($lines, fn ($l) => $l !== ''));

        return $this->answer(
            implode("\n", $lines),
            'order_status',
            1.0,
            [],
            [['label' => 'Xem chi tiết đơn', 'url' => route('orders.show', $order->id)]]
        );
    }

    private function handleOrderList(?User $user): ?array
    {
        if (! $user) {
            return $this->answer(
                'Bạn đăng nhập rồi mình lấy lịch sử đơn hàng giúp nhé.',
                'order_list',
                1.0,
                [],
                [['label' => 'Đăng nhập', 'url' => route('login')]]
            );
        }

        $orders = Order::where('user_id', $user->id)->latest('id')->limit(5)->get();

        if ($orders->isEmpty()) {
            return $this->answer('Bạn chưa đặt đơn nào ở ToyLand cả.', 'order_list', 1.0);
        }

        $lines = ['Đây là ' . $orders->count() . ' đơn gần nhất của bạn:'];

        foreach ($orders as $order) {
            $lines[] = sprintf(
                '• #%d — %s — %s đ — %s',
                $order->id,
                optional($order->created_at)->format('d/m/Y'),
                number_format($order->total_price, 0, ',', '.'),
                $order->status_label
            );
        }

        return $this->answer(
            implode("\n", $lines),
            'order_list',
            1.0,
            [],
            [['label' => 'Xem tất cả', 'url' => route('orders.index')]]
        );
    }

    /**
     * Tìm sản phẩm - dùng LẠI scopeFilter của Product, tức là bot và trang chủ
     * chạy cùng một bộ lọc. Sửa logic lọc một lần là cả hai chỗ cùng đúng.
     */
    private function handleProductSearch(array $entities): array
    {
        $filters = $this->entities->toProductFilters($entities);

        $query = Product::with('category')
            ->withSoldQuantity()
            ->withAvg('visibleReviews as visible_reviews_avg_rating', 'rating')
            ->where('quantity', '>', 0);

        if (! empty($filters)) {
            $query->filter($filters);
        }

        $products = $query->orderByDesc('sold_quantity')->limit(4)->get();

        // Không ra kết quả -> nới lỏng dần điều kiện thay vì trả về tay trắng
        $relaxed = false;
        if ($products->isEmpty() && ! empty($filters)) {
            $loose = $filters;
            unset($loose['price_min'], $loose['price_max'], $loose['gender']);

            $products = Product::with('category')->withSoldQuantity()
                ->where('quantity', '>', 0)
                ->filter($loose)
                ->orderByDesc('sold_quantity')
                ->limit(4)
                ->get();

            $relaxed = $products->isNotEmpty();
        }

        if ($products->isEmpty()) {
            return $this->answer(
                "Mình chưa tìm được món nào khớp yêu cầu đó. Bạn thử nói rõ hơn giúp mình nhé, "
                . 'ví dụ: "đồ chơi lắp ghép cho bé 5 tuổi dưới 500k".',
                'product_search',
                1.0,
                [],
                [['label' => 'Xem toàn bộ đồ chơi', 'url' => route('home')]]
            );
        }

        $summary = $this->describeFilters($entities);
        $intro = $products->count() . ' gợi ý' . ($summary ? ' ' . $summary : '') . ':';

        if ($relaxed) {
            $intro = 'Không có món nào khớp hết mọi tiêu chí, mình nới bớt điều kiện giá/giới tính nhé:';
        }

        return $this->answer(
            $intro,
            'product_search',
            1.0,
            $this->productCards($products),
            [['label' => 'Xem thêm', 'url' => route('home', $this->filtersToQuery($filters))]]
        );
    }

    private function handleBestseller(): array
    {
        $products = Product::bestSellers(4);

        if ($products->isEmpty()) {
            $products = Product::with('category')->where('quantity', '>', 0)
                ->orderByDesc('is_featured')->limit(4)->get();
        }

        return $this->answer(
            'Đây là những món đang được các bé yêu thích nhất:',
            'bestseller',
            1.0,
            $this->productCards($products),
            [['label' => 'Xem bảng xếp hạng', 'url' => route('home', ['sort' => 'best_selling'])]]
        );
    }

    private function handleShipping(): array
    {
        return $this->answer(
            "Về vận chuyển:\n"
            . "• ToyLand giao qua Giao Hàng Nhanh (GHN) toàn quốc.\n"
            . "• Phí ship tính tự động theo địa chỉ và cân nặng đơn — bạn sẽ thấy con số chính xác "
            . "ngay ở bước chọn Tỉnh/Quận/Phường trong trang thanh toán.\n"
            . "• Nội thành thường nhận trong 24 giờ, tỉnh khác 2-4 ngày.",
            'shipping',
            1.0
        );
    }

    private function handlePayment(): array
    {
        return $this->answer(
            "ToyLand nhận 2 hình thức:\n"
            . "• COD — trả tiền mặt khi nhận hàng.\n"
            . "• MoMo — ví/QR, thẻ ATM nội địa, thẻ quốc tế Visa/Master/JCB.\n\n"
            . 'Thanh toán MoMo lỡ thất bại thì đơn vẫn được giữ, bạn vào Lịch sử đơn bấm "Thanh toán lại" là được.',
            'payment',
            1.0
        );
    }

    private function handleReturnPolicy(): array
    {
        return $this->answer(
            "Chính sách đổi trả:\n"
            . "• Đổi trả trong 7 ngày kể từ khi nhận hàng.\n"
            . "• Sản phẩm lỗi do nhà sản xuất: đổi mới miễn phí, không hỏi lý do.\n"
            . "• Giữ nguyên hộp và phụ kiện kèm theo giúp shop nhé.\n\n"
            . 'Đơn chưa được đóng gói thì bạn tự hủy được ngay trong Lịch sử đơn hàng.',
            'return_policy',
            1.0
        );
    }

    /** Mã giảm giá - đọc mã ĐANG CHẠY THẬT trong CSDL, không viết cứng */
    private function handleCoupon(?User $user): array
    {
        $coupons = Coupon::active()->orderByDesc('id')->limit(5)->get();

        if ($coupons->isEmpty()) {
            return $this->answer(
                'Hiện chưa có mã giảm giá nào đang chạy. Bạn theo dõi trang Mã giảm giá để không bỏ lỡ nhé!',
                'coupon',
                1.0,
                [],
                [['label' => 'Trang mã giảm giá', 'url' => route('coupons.index')]]
            );
        }

        $lines = ['Các mã đang chạy:'];

        foreach ($coupons as $coupon) {
            $value = $coupon->type === 'percent'
                ? $coupon->value . '%'
                : number_format($coupon->value, 0, ',', '.') . ' đ';

            $condition = $coupon->min_order_amount > 0
                ? ' (đơn từ ' . number_format($coupon->min_order_amount, 0, ',', '.') . ' đ)'
                : '';

            $lines[] = '• ' . $coupon->code . ' — giảm ' . $value . $condition;
        }

        if ($user) {
            $lines[] = "\nHạng " . $user->tier_label . ' của bạn còn được giảm thêm '
                . $user->tier_info['discount'] . '% tự động mỗi đơn.';
        }

        return $this->answer(
            implode("\n", $lines),
            'coupon',
            1.0,
            [],
            [['label' => 'Xem chi tiết', 'url' => route('coupons.index')]]
        );
    }

    private function handleLoyalty(?User $user): array
    {
        $tiers = collect(User::TIERS)->map(function ($tier) {
            return '• ' . $tier['label'] . ' — chi tiêu từ '
                . number_format($tier['min'], 0, ',', '.') . ' đ — giảm ' . $tier['discount'] . '%';
        })->implode("\n");

        $intro = "Mỗi " . number_format(User::POINTS_PER_VND, 0, ',', '.')
            . " đ chi tiêu bạn được 1 điểm thưởng. Hạng thành viên:\n" . $tiers;

        if ($user) {
            $intro .= "\n\nBạn đang ở hạng " . $user->tier_label
                . ' với ' . number_format($user->points ?? 0, 0, ',', '.') . ' điểm.';
        }

        return $this->answer(
            $intro,
            'loyalty',
            1.0,
            [],
            $user ? [['label' => 'Xem điểm của tôi', 'url' => route('profile.edit')]] : []
        );
    }

    private function handleStoreInfo(): array
    {
        return $this->answer(
            "ToyLand — Thế giới đồ chơi cho bé\n"
            . "• Hotline: 1900 6868 (8h00 - 21h00 tất cả các ngày)\n"
            . "• Email: hotro@toyland.vn\n"
            . '• Đặt hàng online 24/7 ngay trên website.',
            'store_info',
            1.0
        );
    }

    private function handleSafety(): array
    {
        return $this->answer(
            "Về an toàn, ToyLand cam kết:\n"
            . "• Mỗi sản phẩm đều ghi rõ chất liệu, xuất xứ và độ tuổi phù hợp.\n"
            . "• Món có chi tiết nhỏ luôn có cảnh báo không dành cho bé dưới 3 tuổi.\n"
            . "• Hàng nhập chính hãng, có tem nhà phân phối.\n\n"
            . 'Bạn xem mục "Thông tin sản phẩm" ở từng món để biết chi tiết nhé.',
            'safety',
            1.0
        );
    }

    /**
     * Gợi ý quà dựa trên HỒ SƠ BÉ đã lưu.
     *
     * Đây là chỗ chatbot khác hẳn một ô tìm kiếm: khách chỉ cần gõ "quà sinh
     * nhật cho con" là bot đã biết bé mấy tuổi, thích nhân vật nào, còn mấy
     * ngày nữa sinh nhật - vì bố mẹ đã khai một lần ở mục Hồ sơ của bé.
     */
    private function handleChildGift(?User $user): array
    {
        if (! $user) {
            return $this->answer(
                "Bạn đăng nhập rồi lưu hồ sơ của bé (tên + ngày sinh) là mình gợi ý quà đúng lứa tuổi ngay.\n"
                . 'Mình cũng sẽ nhắc bạn trước ' . ChildProfile::REMIND_DAYS . ' ngày khi sinh nhật bé tới.',
                'child_gift',
                1.0,
                [],
                [['label' => 'Đăng nhập', 'url' => route('login')]]
            );
        }

        $children = ChildProfile::where('user_id', $user->id)->get();

        if ($children->isEmpty()) {
            return $this->answer(
                "Bạn chưa lưu hồ sơ bé nào cả. Khai tên và ngày sinh của bé một lần thôi, "
                . "sau đó mình sẽ:\n"
                . "• Gợi ý đồ chơi đúng độ tuổi bé\n"
                . "• Nhắc bạn trước " . ChildProfile::REMIND_DAYS . " ngày khi sinh nhật bé tới\n"
                . '• Chọn quà theo tuổi bé SẮP tròn, không phải tuổi hiện tại',
                'child_gift',
                1.0,
                [],
                [['label' => '🧒 Thêm hồ sơ bé', 'url' => route('children.create')]]
            );
        }

        // Ưu tiên bé có sinh nhật gần nhất - đó là bé đang cần quà
        $child = $children->sortBy('days_until_birthday')->first();

        // Sắp sinh nhật thì gợi ý theo tuổi SẮP TRÒN, còn xa thì theo tuổi hiện tại
        $useGiftAge = $child->is_birthday_soon;
        $ageMonths = $useGiftAge ? $child->gift_age_months : $child->age_months;

        $gifts = $this->giftFinder->forChild($child, 4, $ageMonths);

        $lines = [];
        $lines[] = ($child->avatar ?: '🧒') . ' ' . $child->name . ' — ' . $child->age_label;

        if ($useGiftAge) {
            $lines[] = '🎂 ' . $child->countdown_label . ', bé sắp tròn ' . $child->turning_age . ' tuổi.';
            $lines[] = 'Mình chọn quà theo tuổi ' . $child->turning_age . ' để bé chơi được lâu:';
        } else {
            $lines[] = 'Sinh nhật ' . $child->next_birthday->format('d/m') . '. Vài món hợp với bé lúc này:';
        }

        if ($children->count() > 1) {
            $others = $children->where('id', '!=', $child->id)->pluck('name')->implode(', ');
            $lines[] = "\n(Bạn còn hồ sơ của {$others} trong Sổ sinh nhật nữa nhé.)";
        }

        return $this->answer(
            implode("\n", $lines),
            'child_gift',
            1.0,
            $this->productCards($gifts),
            [
                ['label' => '🎂 Sổ sinh nhật', 'url' => route('children.birthdays')],
                ['label' => 'Xem tất cả đồ hợp tuổi', 'url' => route('home', $child->filterQuery($ageMonths))],
            ]
        );
    }

    private function handleHandoff(): array
    {
        return $this->answer(
            'Mình chuyển bạn sang nhân viên tư vấn ngay đây. Bạn bấm nút bên dưới nhé!',
            'human_handoff',
            1.0,
            [],
            [['label' => '💬 Gặp nhân viên tư vấn', 'action' => 'switch_to_human']]
        );
    }

    // ===================================================================
    // KHỚP FAQ & LLM
    // ===================================================================

    /**
     * So câu hỏi của khách với toàn bộ FAQ trong CSDL bằng độ tương đồng văn bản.
     * Nhờ vậy admin thêm một câu hỏi thường gặp mới là bot trả lời được ngay,
     * KHÔNG phải sửa dòng code nào.
     */
    private function matchFaq(string $message): ?array
    {
        $faqs = Cache::remember('chatbot.faqs', 600, function () {
            return Faq::active()->get(['id', 'question', 'answer'])->all();
        });

        $best = null;
        $bestScore = 0.0;

        foreach ($faqs as $faq) {
            $score = TextAnalyzer::similarity($message, $faq->question);

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $faq;
            }
        }

        if (! $best || $bestScore < self::FAQ_THRESHOLD) {
            return null;
        }

        return $this->answer(
            $best->answer,
            'faq',
            round($bestScore, 3),
            [],
            [['label' => 'Xem thêm câu hỏi thường gặp', 'url' => route('faq.index')]],
            ['faq_id' => $best->id, 'faq_question' => $best->question]
        );
    }

    /**
     * Nhờ AI trả lời - chỉ tới đây khi bot nội bộ và FAQ đều bó tay.
     *
     * Trước khi gửi đi phải qua đủ 4 cửa, cửa nào chặn thì trả lời ngay bằng
     * bot nội bộ, KHÔNG tốn lượt gọi API:
     *   1. Lọc chủ đề + chặn câu lệnh độc   (LlmGuard::classify)
     *   2. Hạn mức phút/ngày/khách + cầu dao (LlmQuota::deny)
     *   3. Câu hỏi y hệt đã trả lời gần đây  (cache câu trả lời)
     *   4. Che thông tin cá nhân + cắt ngắn   (LlmGuard::maskPii / sanitizeHistory)
     */
    private function askLlm(string $message, array $history): ?array
    {
        if (! $this->llm->isEnabled()) {
            return null;
        }

        // ---- Cửa 1: chủ đề -------------------------------------------------
        $verdict = $this->guard->classify($message);

        if ($verdict !== LlmGuard::VERDICT_OK) {
            return $this->offTopic();
        }

        // ---- Cửa 2: hạn mức ------------------------------------------------
        $client = $this->clientKey();

        if ($this->quota->deny($client) !== null) {
            return $this->llmBusy();
        }

        // ---- Cửa 4 (làm trước để khóa cache cũng không chứa dữ liệu cá nhân) --
        $maxChars = max(100, (int) config('services.chatbot.max_input_chars', 500));
        $question = Str::limit($this->guard->maskPii($message), $maxChars, '…');
        $cleanHistory = $this->guard->sanitizeHistory(
            $history,
            max(0, (int) config('services.chatbot.history_turns', 4)),
            max(50, (int) config('services.chatbot.history_chars', 300))
        );

        // ---- Cửa 3: câu hỏi mở đầu giống hệt thì dùng lại câu trả lời cũ ----
        // (chỉ áp dụng cho câu ĐẦU hội thoại - có lịch sử thì ngữ cảnh khác nhau)
        $hasUserHistory = collect($cleanHistory)->contains(fn ($t) => $t['role'] === 'user');
        $cacheKey = 'chatbot:llm:answer:' . sha1(TextAnalyzer::normalize($question));
        $answer = $hasUserHistory ? null : Cache::get($cacheKey);

        if ($answer === null) {
            $this->quota->hit($client);
            $answer = $this->llm->ask($question, $this->shopContext(), $cleanHistory);

            if (! $answer) {
                return null;
            }

            // Lớp chặn thứ hai: AI tự nhận ra câu ngoài phạm vi
            if (str_contains($answer, LlmClient::OFF_TOPIC_MARKER)) {
                return $this->offTopic();
            }

            // AI có lặp lại số điện thoại/email trong câu hỏi thì cũng che luôn
            $answer = Str::limit($this->guard->maskPii($answer), 1200, '…');

            if (! $hasUserHistory) {
                Cache::put($cacheKey, $answer, (int) config('services.chatbot.answer_cache_minutes', 360) * 60);
            }
        }

        // LƯỚI AN TOÀN: LLM không truy cập CSDL nên bị cấm nêu tên sản phẩm
        // (xem systemPrompt trong LlmClient). Bù lại, mình tự tìm hàng THẬT theo
        // từ khóa của khách rồi đính kèm thẻ có đường dẫn - khách vẫn bấm được
        // vào đúng món có bán, thay vì nghe một cái tên do máy nghĩ ra.
        $products = $this->searchByKeyword($message);

        return $this->answer(
            $answer,
            'llm',
            0.5,
            $this->productCards($products),
            $products->isEmpty() ? $this->quickReplies() : []
        );
    }

    /** Định danh khách để chia hạn mức: đăng nhập thì theo tài khoản, chưa thì theo IP */
    private function clientKey(): string
    {
        return Auth::check() ? 'user:' . Auth::id() : 'ip:' . (request()->ip() ?? 'unknown');
    }

    private function offTopic(): array
    {
        return $this->answer(
            "Mình là trợ lý của ToyLand nên chỉ hỗ trợ được các câu hỏi về đồ chơi, quà cho bé "
            . "và việc mua sắm ở shop (đơn hàng, giao hàng, thanh toán, đổi trả, khuyến mãi) thôi nhé 😊\n"
            . 'Bạn thử hỏi kiểu: "đồ chơi nào giúp bé 3 tuổi phát triển vận động?"',
            'off_topic',
            1.0,
            [],
            $this->quickReplies()
        );
    }

    private function llmBusy(): array
    {
        return $this->answer(
            "Trợ lý đang hơi đông khách nên chưa trả lời tự do được câu này 🙏\n"
            . "Bạn có thể hỏi theo các gợi ý bên dưới (mình tra dữ liệu thật ngay), "
            . 'hoặc bấm "Gặp nhân viên tư vấn".',
            'llm_busy',
            0.0,
            [],
            array_merge($this->quickReplies(), [
                ['label' => '💬 Gặp nhân viên tư vấn', 'action' => 'switch_to_human'],
            ])
        );
    }

    /**
     * Tìm hàng trong kho theo những từ MANG NGHĨA trong câu của khách.
     *
     * Cố ý dùng nguyên văn có dấu chứ không dùng bản bỏ dấu, vì tên sản phẩm
     * trong CSDL có dấu đầy đủ và SQL Server mặc định phân biệt dấu.
     * Lọc bỏ từ dừng và những từ chung chung của cửa hàng đồ chơi ("đồ", "chơi",
     * "món", "hàng"...) - giữ lại thì từ nào cũng khớp, tìm ra cả kho.
     *
     * CHỈ DÒ TRONG `brand` VÀ `character_name`, KHÔNG dò trong `name`.
     *
     * Lý do rút ra từ thực tế: câu tán gẫu "hôm nay trời đẹp nhỉ" từng làm bot
     * đính kèm 3 món, vì chữ "trời" khớp với nhóm "Đồ Chơi Vận Động Ngoài Trời".
     * Tên sản phẩm chứa quá nhiều từ đời thường nên dò ở đó là gặp trùng ngẫu
     * nhiên. Tên hãng và tên nhân vật thì đặc thù - khớp được là gần như chắc
     * chắn khách đang thật sự nhắc tới món đồ chơi đó.
     *
     * Đến được nhánh LLM nghĩa là bộ bóc tách thực thể ĐÃ không tìm thấy hãng
     * hay nhân vật nào (nếu thấy thì boostWithEntities đã chuyển sang tìm sản
     * phẩm rồi). Nên hàm này chỉ là lưới vét cuối, không khớp gì cũng là đúng.
     */
    private function searchByKeyword(string $message): Collection
    {
        $generic = [
            'la', 'thi', 'ma', 'va', 'cua', 'cho', 'co', 'khong', 'duoc', 'nhu', 'the', 'nao',
            'gi', 'nhe', 'voi', 'den', 'trong', 'toi', 'minh', 'ban', 'em', 'anh', 'nay',
            'do', 'choi', 'mon', 'hang', 'san', 'pham', 'qua', 'tang', 'tim', 'mua', 'ban',
            'be', 'tre', 'con', 'tuoi', 'thang', 'gia', 'khoang', 'tam', 'shop', 'loai',
            'nghin', 'trieu', 'dong', 'nen', 'giup', 'muon', 'can', 'xem', 'hoi',
        ];

        $keywords = [];

        foreach (preg_split('/\s+/u', trim($message)) as $word) {
            $word = trim($word, " \t\n\r\0\x0B.,!?;:\"'()[]");
            $plain = TextAnalyzer::normalize($word);

            // Tối thiểu 4 ký tự: từ 3 chữ cái trong tiếng Việt quá phổ biến
            // ("hôm", "cái", "này"...) nên dễ khớp trúng linh tinh.
            if (mb_strlen($plain) < 4 || in_array($plain, $generic, true) || is_numeric($plain)) {
                continue;
            }

            $keywords[] = $word;

            if (count($keywords) >= 4) {
                break;
            }
        }

        if (! $keywords) {
            return collect();
        }

        return Product::with('category')
            ->where('quantity', '>', 0)
            ->where(function ($sub) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $sub->orWhere('brand', 'like', '%' . $keyword . '%')
                        ->orWhere('character_name', 'like', '%' . $keyword . '%');
                }
            })
            ->orderByDesc('is_featured')
            ->limit(3)
            ->get();
    }

    /**
     * Bối cảnh gửi kèm cho LLM. Lấy số liệu thật từ CSDL để LLM nói đúng
     * phạm vi hàng hóa của shop thay vì đoán bừa.
     */
    private function shopContext(): string
    {
        return Cache::remember('chatbot.llm_context', 600, function () {
            $categories = \App\Models\Category::roots()->ordered()->pluck('name')->implode(', ');
            $brands = Product::whereNotNull('brand')->distinct()->limit(15)->pluck('brand')->implode(', ');
            $priceRange = Product::where('quantity', '>', 0)
                ->selectRaw('MIN(price) as min_price, MAX(price) as max_price')
                ->first();

            return "- Tên cửa hàng: ToyLand, bán đồ chơi trẻ em, giao toàn quốc qua GHN.\n"
                . "- Thanh toán: COD hoặc MoMo (ví/QR, thẻ ATM, thẻ quốc tế).\n"
                . "- Đổi trả trong 7 ngày, hàng lỗi đổi mới miễn phí.\n"
                . "- Hotline 1900 6868, email hotro@toyland.vn.\n"
                . "- Nhóm hàng: {$categories}\n"
                . "- Một số thương hiệu: {$brands}\n"
                . '- Khoảng giá: ' . number_format($priceRange->min_price ?? 0, 0, ',', '.')
                . ' đ đến ' . number_format($priceRange->max_price ?? 0, 0, ',', '.') . ' đ';
        });
    }

    private function fallback(): array
    {
        return $this->answer(
            "Xin lỗi, mình chưa hiểu ý bạn 😅 Bạn thử hỏi theo mấy cách này xem:\n"
            . "• \"tìm đồ chơi cho bé 4 tuổi dưới 300k\"\n"
            . "• \"đơn hàng #12 tới đâu rồi\"\n"
            . "• \"phí ship bao nhiêu\"\n\n"
            . 'Hoặc bấm nút bên dưới để gặp nhân viên tư vấn nhé.',
            'unknown',
            0.0,
            [],
            array_merge($this->quickReplies(), [
                ['label' => '💬 Gặp nhân viên tư vấn', 'action' => 'switch_to_human'],
            ])
        );
    }

    // ===================================================================
    // TIỆN ÍCH
    // ===================================================================

    /** Câu hỏi gợi ý sẵn để khách bấm cho nhanh, khỏi phải tự nghĩ */
    public function quickReplies(): array
    {
        return [
            ['label' => 'Quà cho bé nhà mình', 'send' => 'Gợi ý quà sinh nhật cho con'],
            ['label' => 'Gợi ý quà cho bé 3 tuổi', 'send' => 'Gợi ý đồ chơi cho bé 3 tuổi'],
            ['label' => 'Hàng bán chạy', 'send' => 'Món nào bán chạy nhất shop'],
            ['label' => 'Mã giảm giá', 'send' => 'Có mã giảm giá nào đang chạy không'],
            ['label' => 'Phí ship', 'send' => 'Phí vận chuyển tính thế nào'],
        ];
    }

    /** Gói sản phẩm thành thẻ hiển thị trong khung chat */
    private function productCards(Collection $products): array
    {
        return $products->map(fn (Product $p) => [
            'id' => $p->id,
            'name' => $p->name,
            'price' => number_format($p->price, 0, ',', '.') . ' đ',
            'image' => $p->image_url,
            'age' => $p->age_label,
            'url' => route('products.show', $p->id),
        ])->values()->all();
    }

    /** "cho bé gái 5 tuổi, dưới 300.000 đ" - nhắc lại để khách biết bot hiểu đúng chưa */
    private function describeFilters(array $entities): string
    {
        $parts = [];

        if (isset($entities['age_months'])) {
            $months = (int) $entities['age_months'];
            $parts[] = 'cho bé ' . ($months < 24 ? $months . ' tháng' : intdiv($months, 12) . ' tuổi');
        }

        if (isset($entities['gender'])) {
            $parts[] = $entities['gender'] === 'girl' ? '(bé gái)' : '(bé trai)';
        }

        if (isset($entities['character'])) {
            $parts[] = 'chủ đề ' . $entities['character'];
        }

        if (isset($entities['brand'])) {
            $parts[] = 'hãng ' . $entities['brand'];
        }

        if (isset($entities['price_max'])) {
            $parts[] = 'dưới ' . number_format($entities['price_max'], 0, ',', '.') . ' đ';
        }

        return $parts ? implode(', ', $parts) : '';
    }

    /** Chuyển filter nội bộ thành query string của trang chủ */
    private function filtersToQuery(array $filters): array
    {
        return array_filter([
            'category' => $filters['category'] ?? null,
            'brand' => $filters['brand'] ?? null,
            'character' => $filters['character'] ?? null,
            'gender' => $filters['gender'] ?? null,
            'age' => $filters['age'] ?? null,
            'price_min' => $filters['price_min'] ?? null,
            'price_max' => $filters['price_max'] ?? null,
        ]);
    }

    /** Đóng gói câu trả lời theo một định dạng thống nhất */
    private function answer(
        string $reply,
        string $intent = 'unknown',
        float $confidence = 0.0,
        array $products = [],
        array $actions = [],
        array $meta = []
    ): array {
        return compact('reply', 'intent', 'confidence', 'products', 'actions', 'meta');
    }
}
