<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\UserAddress;
use App\Mail\OrderPlacedMail;
use App\Models\UserNotification;
use App\Models\OrderStatusLog;
use App\Services\GHNOrderService;
use App\Services\GHNService;
use App\Services\MomoService;
use App\Services\Orders\OrderWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class CheckoutController extends Controller
{
    use \App\Http\Controllers\Concerns\HandlesAddressForm;

    protected GHNOrderService $ghnOrderService;
    protected GHNService $ghnService;
    protected MomoService $momoService;

    public function __construct(GHNOrderService $ghnOrderService, GHNService $ghnService, MomoService $momoService)
    {
        $this->ghnOrderService = $ghnOrderService;
        $this->ghnService = $ghnService;
        $this->momoService = $momoService;
    }

    public function index()
    {
        $cart = $this->syncCartPrices(session('cart', []));

        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng của bạn đang trống.');
        }

        $user = Auth::user();
        $addresses = $user->addresses()->orderByDesc('is_default')->latest()->get();

        $subtotal = $this->cartSubtotal($cart);

        // Ưu đãi hạng thành viên được áp dụng tự động cho mọi đơn
        $tierDiscount = $this->tierDiscount($user, $subtotal);

        // Mã giảm giá khách nhập ở bước trước, lưu trong session
        [$coupon, $couponDiscount] = $this->resolveCoupon($user, $subtotal - $tierDiscount);

        $discountTotal = $tierDiscount + $couponDiscount;

        // $total = tiền hàng sau giảm giá (chưa gồm phí ship, phí ship do JS cộng thêm ở form)
        $total = max(0, $subtotal - $discountTotal);

        // Danh sách phương thức thanh toán MoMo để khách tự chọn (ví/QR, ATM, thẻ quốc tế...)
        $momoOptions = MomoService::paymentOptions();
        $defaultMomoOption = $this->momoService->defaultPaymentOption();

        return view('checkout.index', compact(
            'cart', 'addresses', 'total', 'subtotal', 'tierDiscount', 'coupon', 'couponDiscount', 'discountTotal',
            'momoOptions', 'defaultMomoOption', 'user'
        ));
    }

    public function process(Request $request)
    {
        // Đồng bộ giá TRƯỚC khi tính tiền - flash sale có thể đã bật hoặc tắt
        // kể từ lúc khách bỏ món vào giỏ.
        $cart = $this->syncCartPrices(session('cart', []));

        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng của bạn đang trống.');
        }

        // Trả về kho những món đang bị giữ bởi đơn MoMo quá hạn chưa thanh toán,
        // TRƯỚC khi kiểm tra tồn kho cho đơn này.
        app(OrderWorkflow::class)->releaseExpired(true);

        $request->validate([
            'address_id' => 'nullable|exists:user_addresses,id',
            'new_name' => 'required_without:address_id|nullable|string|max:255',
            'new_phone' => 'required_without:address_id|nullable|string|max:20',
            'new_address' => 'required_without:address_id|nullable|string|max:500',
            'new_province_id' => 'nullable|integer',
            'new_province_name' => 'nullable|string|max:120',
            'new_district_name' => 'nullable|string|max:120',
            'new_ward_name' => 'nullable|string|max:120',
            'payment_method' => 'required|in:cod,momo,wallet',
            // Phương thức con của MoMo (ví/QR, thẻ ATM, thẻ quốc tế...). Chỉ có ý nghĩa khi
            // payment_method = momo; giá trị hợp lệ lấy từ whitelist trong MomoService.
            'momo_payment_option' => 'nullable|in:' . implode(',', array_keys(MomoService::PAYMENT_OPTIONS)),
            // Do GHNService trả về (qua AJAX locations.fee) và được ghi vào các input ẩn trên form checkout
            'to_district_id' => 'nullable|integer',
            'to_ward_code' => 'nullable|string|max:50',
            'ghn_total_fee' => 'nullable|integer|min:0',
        ]);

        // TÍNH LẠI PHÍ SHIP Ở SERVER.
        // Giá trị trong input ẩn do JS điền nên khách hoàn toàn có thể sửa bằng DevTools
        // để trả ít tiền hơn. Vì vậy server luôn gọi lại GHN để lấy phí thật và chỉ dùng
        // giá trị client gửi lên khi API GHN lỗi/timeout (để không chặn đứng việc đặt hàng).
        // Chốt điểm giao TRƯỚC khi tính phí: nếu khách chọn địa chỉ đã lưu thì lấy
        // mã hành chính từ chính địa chỉ đó, không phụ thuộc input ẩn trên form.
        $destination = $this->resolveDestination($request);

        if (! $destination['district_id'] || ! $destination['ward_code']) {
            return back()
                ->withInput()
                ->with('error', 'Chưa xác định được nơi giao hàng. Hãy chọn Tỉnh/Quận/Phường để hệ thống tính phí vận chuyển.');
        }

        $clientFee = (int) ($request->input('ghn_total_fee') ?? 0);
        $shippingFee = $this->resolveShippingFee($destination, $cart, $clientFee);

        try {
            $order = DB::transaction(function () use ($request, $cart, $shippingFee, $destination) {
                $user = Auth::user();

                if ($request->filled('address_id')) {
                    $address = $user->addresses()->findOrFail($request->address_id);

                    // Địa chỉ cũ (lưu từ trước khi có cột tỉnh/quận/phường) sẽ được
                    // bổ sung mã hành chính khách vừa chọn, lần sau khỏi phải chọn lại.
                    if (! $address->hasLocation()) {
                        $address->update([
                            'province_id' => $destination['province_id'],
                            'province_name' => $destination['province_name'],
                            'district_id' => $destination['district_id'],
                            'district_name' => $destination['district_name'],
                            'ward_code' => $destination['ward_code'],
                            'ward_name' => $destination['ward_name'],
                        ]);
                    }
                } else {
                    // Lưu địa chỉ mới KÈM mã hành chính để dùng cho các đơn sau
                    $address = $user->addresses()->create([
                        'name' => $request->new_name,
                        'phone' => $request->new_phone,
                        'address' => $request->new_address,
                        'province_id' => $destination['province_id'],
                        'province_name' => $destination['province_name'],
                        'district_id' => $destination['district_id'],
                        'district_name' => $destination['district_name'],
                        'ward_code' => $destination['ward_code'],
                        'ward_name' => $destination['ward_name'],
                        'is_default' => $user->addresses()->count() === 0,
                    ]);
                }

                $name = $address->name;
                $phone = $address->phone;
                $addressText = $address->full_address;

                $subtotal = $this->cartSubtotal($cart);

                // Chốt lại giảm giá Ở SERVER tại thời điểm đặt hàng (không tin số liệu client gửi lên)
                $tierDiscount = $this->tierDiscount($user, $subtotal);
                [$coupon, $couponDiscount] = $this->resolveCoupon($user, $subtotal - $tierDiscount);
                $discountTotal = $tierDiscount + $couponDiscount;

                $totalPrice = max(0, $subtotal - $discountTotal) + $shippingFee;

                // COD: vào thẳng hàng chờ shop xác nhận.
                // MoMo: GIỮ HÀNG (đã trừ kho bên dưới) trong 30 phút chờ khách trả tiền;
                // quá hạn thì OrderWorkflow::releaseExpired() tự hủy và trả kho.
                $isMomo = $request->payment_method === 'momo';
                $isWallet = $request->payment_method === 'wallet';
                $workflow = app(OrderWorkflow::class);

                $order = Order::create([
                    'user_id' => $user->id,
                    'total_price' => $totalPrice,
                    'status' => $isMomo ? 'awaiting_payment' : 'pending',
                    'payment_method' => $request->payment_method,
                    'payment_status' => $isWallet ? 'paid' : 'pending',
                    'payment_expires_at' => $isMomo ? now()->addMinutes($workflow->holdMinutes()) : null,
                    'shipping_status' => 'not_shipped',
                    'shipping_address' => "{$name} | {$phone} | {$addressText}",
                    'customer_phone' => $phone,
                    'to_district_id' => $destination['district_id'],
                    'to_ward_code' => $destination['ward_code'],
                    'ghn_total_fee' => $shippingFee,
                    'coupon_id' => $coupon->id ?? null,
                    'discount_amount' => $discountTotal,
                    'points_earned' => 0,
                ]);

                // Ghi nhận lượt dùng mã giảm giá để kiểm soát giới hạn
                if ($coupon && $couponDiscount > 0) {
                    CouponUsage::create([
                        'coupon_id' => $coupon->id,
                        'user_id' => $user->id,
                        'order_id' => $order->id,
                        'discount_amount' => $couponDiscount,
                    ]);

                    Coupon::whereKey($coupon->id)->increment('used_count');
                }

                foreach ($cart as $item) {
                    $product = Product::lockForUpdate()->findOrFail($item['product_id']);
                    $variant = ! empty($item['variant_id'])
                        ? ProductVariant::where('product_id', $product->id)->lockForUpdate()->find($item['variant_id'])
                        : null;

                    if (! empty($item['variant_id']) && ! $variant) {
                        throw new \RuntimeException("Phân loại của \"{$product->name}\" không còn bán, vui lòng cập nhật lại giỏ hàng.");
                    }

                    $label = $product->name . ($variant ? " ({$variant->name})" : '');
                    $stock = $variant ? $variant->quantity : $product->quantity;

                    if ($stock < $item['quantity']) {
                        throw new \RuntimeException("Sản phẩm \"{$label}\" không đủ số lượng trong kho, vui lòng cập nhật lại giỏ hàng.");
                    }

                    // CHỐT GIÁ LẦN CUỐI, trên bản ghi đã khóa trong giao dịch.
                    //
                    // Giữa lúc hiện trang thanh toán và lúc bấm Đặt hàng có thể
                    // trôi qua vài phút - đủ để một đợt flash sale kết thúc. Nếu
                    // giá đã đổi thì HỦY đơn kèm lời giải thích, thay vì âm thầm
                    // tính một con số khác với con số khách vừa nhìn thấy.
                    //
                    // Nguyên tắc: không bao giờ thu của khách một mức giá mà họ
                    // chưa từng được xem.
                    $currentPrice = $variant ? $variant->effective_price : $product->effective_price;
                    if ((int) $currentPrice !== (int) $item['price']) {
                        throw new \RuntimeException(
                            "Giá của \"{$label}\" vừa thay đổi (chương trình khuyến mãi bắt đầu hoặc kết thúc). "
                            . 'Mời bạn xem lại giỏ hàng với giá mới rồi đặt lại giúp shop nhé.'
                        );
                    }

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'variant_id' => $variant?->id,
                        'variant_name' => $variant?->name,
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                    ]);

                    // Tổng tồn kho của sản phẩm luôn bằng tổng các biến thể -> trừ cả hai
                    if ($variant) {
                        $variant->decrement('quantity', $item['quantity']);
                    }
                    $product->decrement('quantity', $item['quantity']);
                }

                // VÍ TOYLAND: trả TRỌN đơn bằng số dư (không trả một phần, không kèm COD/MoMo).
                // Trừ ví nằm trong cùng giao dịch CSDL với việc tạo đơn và trừ kho: lỗi ở đâu thì
                // hoàn tác ở đó, không thể xảy ra "ví đã trừ mà đơn không có" hay ngược lại.
                if ($isWallet) {
                    app(\App\Services\WalletService::class)->debit($user->id, (int) $totalPrice, 'order_payment', [
                        'order_id' => $order->id, 'note' => 'Thanh toán đơn #' . $order->id,
                    ]);
                    PaymentTransaction::create([
                        'order_id' => $order->id, 'gateway' => 'wallet', 'amount' => $totalPrice,
                        'status' => 'paid', 'paid_at' => now(), 'message' => 'Thanh toán bằng Ví ToyLand',
                    ]);
                }

                return $order;
            });
        } catch (\Throwable $e) {
            return redirect()->route('checkout.index')->with('error', $e->getMessage());
        }

        session()->forget('cart');
        session()->forget(CouponController::SESSION_KEY);

        // Ghi mốc đầu tiên vào nhật ký trạng thái đơn hàng
        OrderStatusLog::create([
            'order_id' => $order->id,
            'changed_by' => Auth::id(),
            'from_status' => null,
            'to_status' => $order->status,
            'note' => $order->status === 'awaiting_payment'
                ? '[Khách] Đặt hàng, chờ thanh toán MoMo (giữ hàng tới ' . $order->payment_expires_at->format('H:i d/m') . ')'
                : ($order->payment_method === 'wallet' ? '[Khách] Đặt hàng, đã thanh toán bằng Ví ToyLand' : '[Khách] Đặt hàng thành công (COD)'),
        ]);

        // Email xác nhận đơn hàng. Bọc try/catch để SMTP lỗi không làm hỏng việc đặt hàng.
        $this->sendOrderConfirmation($order);

        // Theo yêu cầu mới nhất của khách hàng: TẠM THỜI chỉ tính phí & lưu thông tin GHN
        // (to_district_id, to_ward_code, ghn_total_fee) cục bộ trong DB, CHƯA đẩy đơn thật
        // sang hệ thống GHN ở bước đặt hàng. Việc tạo vận đơn GHN sẽ được kích hoạt lại sau
        // (ví dụ từ trang quản trị) bằng cách gọi lại pushOrderToGhn() bên dưới.
        // if ($order->payment_method === 'cod') {
        //     $this->pushOrderToGhn($order);
        // }

        // Đơn COD: chỉ ghi nhật ký giao dịch ở trạng thái 'pending' (thu tiền khi giao hàng),
        // không gọi cổng thanh toán nào cả.
        if ($order->payment_method === 'wallet') {
            return redirect()->route('orders.show', $order->id)->with('success', '🎉 Đặt hàng thành công! Đơn đã được thanh toán bằng Ví ToyLand.');
        }

        if ($order->payment_method !== 'momo') {
            PaymentTransaction::create([
                'order_id' => $order->id,
                'gateway' => 'cod',
                'amount' => $order->total_price,
                'status' => 'pending',
                'message' => 'Thanh toán khi nhận hàng',
            ]);

            return redirect()->route('orders.index')->with('success', '🎉 Đặt hàng thành công! Cảm ơn bạn đã mua sắm tại ToyLand.');
        }

        // Đơn MoMo: tạo bản ghi giao dịch rồi gọi API create để lấy payUrl.
        // Việc này nằm NGOÀI DB::transaction ở trên - đơn hàng và tồn kho đã được chốt an toàn,
        // nếu MoMo lỗi thì đơn vẫn còn nguyên ở trạng thái chưa thanh toán và khách
        // có thể bấm "Thanh toán lại" ở trang lịch sử đơn hàng.
        $paymentOption = $this->momoService->resolvePaymentOption($request->input('momo_payment_option'));

        $transaction = PaymentTransaction::create([
            'order_id' => $order->id,
            'gateway' => 'momo',
            'payment_option' => $paymentOption,
            'gateway_order_id' => $this->momoService->orderId($order),
            'amount' => $order->total_price,
            'status' => 'pending',
        ]);

        try {
            $response = $this->momoService->createPayment($order, $transaction, $paymentOption);
        } catch (Throwable $e) {
            Log::error('Không tạo được yêu cầu thanh toán MoMo cho đơn hàng #' . $order->id, [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);

            return redirect()->route('orders.index')
                ->with('error', 'Đặt hàng thành công nhưng chưa kết nối được cổng MoMo: ' . $e->getMessage()
                    . ' Vui lòng bấm "Thanh toán lại bằng MoMo" ở đơn hàng bên dưới.');
        }

        // Chuyển khách sang trang thanh toán của MoMo
        return redirect()->away($response['payUrl']);
    }

    // Xóa 1 địa chỉ đã lưu trong sổ địa chỉ của chính người dùng đang đăng nhập
    public function destroyAddress(UserAddress $address)
    {
        // So sánh ép kiểu (int) chứ không dùng !== thẳng: user_id lấy từ DB qua route-model-binding
        // là string (PDO trả string cho cột số), trong khi Auth::id() là int - !== sẽ luôn true
        // (khác kiểu) dù cùng giá trị, khiến chính chủ sở hữu cũng bị chặn 403 nhầm.
        abort_if((int) $address->user_id !== (int) Auth::id(), 403);

        $address->delete();

        return back()->with('success', 'Đã xóa địa chỉ khỏi sổ địa chỉ.');
    }

    // Tổng tiền hàng trong giỏ (chưa giảm giá, chưa phí ship)
    /**
     * Đọc lại giá hiệu lực của mọi món trong giỏ từ CSDL.
     *
     * VÌ SAO CẦN:
     * Giỏ hàng nằm trong session, mỗi dòng lưu kèm giá tại thời điểm bỏ vào giỏ.
     * Flash sale thì có khung giờ. Khách bỏ món vào giỏ lúc đang giảm giá rồi
     * để đó qua đêm, sáng hôm sau hết sale mà giỏ vẫn giữ giá cũ thì shop bán
     * lỗ; ngược lại món vừa vào đợt giảm giá thì khách phải được hưởng ngay.
     *
     * Hàm này ghi ngược kết quả vào session để trang giỏ hàng, trang thanh toán
     * và lúc tạo đơn đều đọc CÙNG MỘT con số.
     *
     * Món đã bị gỡ khỏi hệ thống thì loại luôn khỏi giỏ.
     */
    protected function syncCartPrices(array $cart): array
    {
        if (empty($cart)) {
            return $cart;
        }

        $products = Product::with('variants')
            ->whereIn('id', collect($cart)->pluck('product_id')->unique())
            ->get()->keyBy('id');

        foreach ($cart as $key => $item) {
            $product = $products[$item['product_id']] ?? null;
            $variant = ! empty($item['variant_id']) && $product
                ? $product->variants->firstWhere('id', $item['variant_id']) : null;

            // Sản phẩm/biến thể đã bị gỡ khỏi hệ thống -> loại khỏi giỏ
            if (! $product || (! empty($item['variant_id']) && ! $variant)) {
                unset($cart[$key]);

                continue;
            }

            $cart[$key]['price'] = $variant ? $variant->effective_price : $product->effective_price;
            $cart[$key]['base_price'] = $variant ? $variant->base_price : (int) $product->price;
            $cart[$key]['on_sale'] = $product->is_on_sale;
            $cart[$key]['name'] = $product->name;
            $cart[$key]['variant_name'] = $variant?->name;
            $cart[$key]['image'] = $variant && $variant->image ? $variant->image : $product->image;
        }

        session(['cart' => $cart]);

        return $cart;
    }

    protected function cartSubtotal(array $cart): int
    {
        $total = 0;

        foreach ($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        return (int) $total;
    }

    // Số tiền được giảm theo hạng thành viên (Đồng 0% - Bạc 2% - Vàng 5% - Kim cương 8%)
    protected function tierDiscount($user, int $subtotal): int
    {
        if (! $user || $user->tier_discount <= 0) {
            return 0;
        }

        return (int) floor($subtotal * $user->tier_discount / 100);
    }

    /**
     * Lấy mã giảm giá đang lưu trong session và tính số tiền giảm.
     * Tự gỡ mã khỏi session nếu mã không còn hợp lệ (hết hạn, hết lượt, không đủ điều kiện).
     *
     * @return array{0: ?Coupon, 1: int}
     */
    protected function resolveCoupon($user, int $baseAmount): array
    {
        $couponId = session(CouponController::SESSION_KEY);

        if (! $couponId) {
            return [null, 0];
        }

        // Bọc try/catch: một mã giảm giá hỏng/không còn tồn tại trong session
        // KHÔNG được phép làm sập cả trang thanh toán. Gặp sự cố thì gỡ mã và đi tiếp.
        try {
            $coupon = Coupon::find($couponId);

            if (! $coupon || $coupon->validateFor($user, max(0, $baseAmount))) {
                session()->forget(CouponController::SESSION_KEY);

                return [null, 0];
            }

            return [$coupon, $coupon->discountFor(max(0, $baseAmount))];
        } catch (Throwable $e) {
            Log::warning('Không xử lý được mã giảm giá trong session, đã tự gỡ mã', [
                'coupon_id' => $couponId,
                'message' => $e->getMessage(),
            ]);

            session()->forget(CouponController::SESSION_KEY);

            return [null, 0];
        }
    }

    /**
     * Gọi lại API GHN để lấy phí vận chuyển thật theo giỏ hàng và địa chỉ nhận.
     * Trả về phí do client gửi lên nếu thiếu thông tin địa chỉ hoặc API lỗi.
     */
    /**
     * Xác định nơi giao hàng (tỉnh / quận / phường) cho đơn đang đặt.
     *
     * Thứ tự ưu tiên:
     *   1. Khách chọn địa chỉ đã lưu VÀ địa chỉ đó đã có mã hành chính -> dùng luôn.
     *      Đây chính là phần trước đây bị thiếu, khiến mỗi lần đặt hàng phải
     *      chọn lại Tỉnh/Quận/Phường thì mới tính được phí ship.
     *   2. Ngược lại (địa chỉ mới, hoặc địa chỉ cũ chưa có mã) -> lấy từ bộ chọn
     *      GHN trên form checkout.
     */
    protected function resolveDestination(Request $request): array
    {
        if ($request->filled('address_id')) {
            $address = Auth::user()->addresses()->find($request->input('address_id'));

            if ($address && $address->hasLocation()) {
                return [
                    'province_id' => $address->province_id,
                    'province_name' => $address->province_name,
                    'district_id' => (int) $address->district_id,
                    'district_name' => $address->district_name,
                    'ward_code' => (string) $address->ward_code,
                    'ward_name' => $address->ward_name,
                ];
            }
        }

        return [
            'province_id' => $request->input('to_province_id') ?: null,
            'province_name' => $request->input('new_province_name') ?: null,
            'district_id' => (int) $request->input('to_district_id'),
            'district_name' => $request->input('new_district_name') ?: null,
            'ward_code' => (string) $request->input('to_ward_code'),
            'ward_name' => $request->input('new_ward_name') ?: null,
        ];
    }

    protected function resolveShippingFee(array $destination, array $cart, int $clientFee): int
    {
        $districtId = (int) $destination['district_id'];
        $wardCode = (string) $destination['ward_code'];

        if (! $districtId || $wardCode === '') {
            return $clientFee;
        }

        try {
            $result = $this->ghnService->calculateFee([
                'to_district_id' => $districtId,
                'to_ward_code' => $wardCode,
                'weight' => $this->ghnOrderService->calculateWeightFromCart($cart),
            ]);

            $serverFee = (int) ($result['total'] ?? 0);

            if ($serverFee !== $clientFee) {
                Log::info('Phí ship client gửi lên khác phí server tính lại', [
                    'client_fee' => $clientFee,
                    'server_fee' => $serverFee,
                ]);
            }

            return $serverFee;
        } catch (Throwable $e) {
            Log::warning('Không tính lại được phí GHN ở server, tạm dùng giá trị client gửi lên', [
                'message' => $e->getMessage(),
            ]);

            return $clientFee;
        }
    }

    // Gửi email xác nhận đơn hàng cho khách. Lỗi SMTP chỉ ghi log, không chặn luồng đặt hàng.
    protected function sendOrderConfirmation(Order $order): void
    {
        // Thông báo trong ứng dụng: chắc chắn tới nơi kể cả khi SMTP hỏng.
        // send() tự nuốt lỗi nên không cần bọc try/catch ở đây.
        UserNotification::send(
            $order->user_id,
            'order',
            'Đã nhận đơn hàng #' . $order->id,
            'Tổng ' . number_format($order->total_price, 0, ',', '.') . ' đ. Shop sẽ xác nhận trong vài giờ tới.',
            route('orders.show', $order->id)
        );

        // Báo cho quản trị viên để không bỏ sót đơn mới
        UserNotification::sendToAdmins(
            'order',
            'Đơn hàng mới #' . $order->id,
            ($order->user->name ?? 'Khách') . ' vừa đặt '
                . number_format($order->total_price, 0, ',', '.') . ' đ.',
            route('admin.orders.show', $order->id)
        );

        try {
            $email = $order->user->email ?? null;

            if ($email) {
                $order->loadMissing('items.product', 'user');
                Mail::to($email)->send(new OrderPlacedMail($order));
            }
        } catch (Throwable $e) {
            Log::warning('Không gửi được email xác nhận đơn hàng #' . $order->id, [
                'message' => $e->getMessage(),
            ]);
        }
    }

    // Gọi GHNOrderService để tạo vận đơn thật cho đơn COD vừa đặt thành công.
    // Bọc try/catch riêng, cô lập khỏi luồng đặt hàng chính: nếu GHN lỗi/timeout/sập,
    // đơn hàng trong hệ thống vẫn được giữ nguyên (shipping_status = 'not_shipped')
    // để admin có thể tạo lại vận đơn thủ công sau, thay vì làm hỏng cả request checkout.
    protected function pushOrderToGhn(Order $order): void
    {
        try {
            $result = $this->ghnOrderService->create($order, false);

            if (!empty($result['order_code'])) {
                $order->update([
                    'ghn_order_code' => $result['order_code'],
                    'shipping_status' => 'processing',
                ]);
            } else {
                Log::warning('GHN tạo đơn không trả về order_code', [
                    'order_id' => $order->id,
                    'response' => $result,
                ]);
            }
        } catch (Throwable $e) {
            Log::error('Không thể tạo vận đơn GHN cho đơn hàng #' . $order->id, [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
