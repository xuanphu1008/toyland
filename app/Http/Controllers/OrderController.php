<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Services\GHNOrderService;
use App\Services\GHNService;
use App\Services\MomoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

class OrderController extends Controller
{
    protected GHNService $ghnService;
    protected GHNOrderService $ghnOrderService;

    public function __construct(GHNService $ghnService, GHNOrderService $ghnOrderService)
    {
        $this->ghnService = $ghnService;
        $this->ghnOrderService = $ghnOrderService;
    }

    public function index()
    {
        $orders = Auth::user()->orders()
            // Nạp sẵn giao dịch thanh toán gần nhất để trang lịch sử đơn hiển thị
            // trạng thái/nút "Thanh toán lại" mà không bị truy vấn N+1
            ->with(['items.product', 'latestPaymentTransaction'])
            ->latest()
            ->paginate(10);

        // Để nút "Thanh toán lại" cho khách chọn lại hình thức MoMo (ví/QR, ATM, thẻ quốc tế...)
        $momoOptions = MomoService::paymentOptions();

        return view('orders.index', compact('orders', 'momoOptions'));
    }

    /**
     * Trang theo dõi 1 đơn hàng: dòng thời gian trạng thái từ lúc đặt đến lúc giao.
     */
    public function show(Order $order)
    {
        abort_if((int) $order->user_id !== (int) Auth::id(), 403);

        $order->load(['items.product', 'paymentTransactions' => fn ($q) => $q->latest()]);

        $statusLogs = OrderStatusLog::where('order_id', $order->id)->orderBy('id')->get();

        return view('orders.show', compact('order', 'statusLogs'));
    }

    /**
     * Hóa đơn để in hoặc lưu thành PDF.
     *
     * Không dùng thư viện sinh PDF: "In → Lưu thành PDF" có sẵn trong mọi trình
     * duyệt cho ra file đúng chuẩn, chữ Việt không lỗi phông, mà không phải cài
     * thêm gói nào. Chi tiết xem chú thích đầu file orders/invoice.blade.php.
     */
    public function invoice(Order $order)
    {
        abort_if((int) $order->user_id !== (int) Auth::id(), 403);

        return view('orders.invoice', self::invoiceData($order, route('orders.show', $order->id)));
    }

    /**
     * Dựng dữ liệu cho hóa đơn. Là hàm static để trang quản trị dùng lại được
     * y nguyên - một bản hóa đơn duy nhất cho cả hai phía, sửa một lần là đúng
     * cả hai chỗ.
     */
    public static function invoiceData(Order $order, string $backUrl): array
    {
        $order->load('items.product', 'user', 'coupon');

        // Tạm tính dựng lại từ các dòng hàng chứ không lấy total_price trừ ngược:
        // giá trong order_items là giá TẠI THỜI ĐIỂM MUA, nên hóa đơn in lại sau
        // này vẫn khớp số tiền khách đã trả dù sản phẩm đã đổi giá.
        $subtotal = $order->items->sum(fn ($item) => (int) $item->price * (int) $item->quantity);

        return [
            'order' => $order,
            'backUrl' => $backUrl,
            'subtotal' => $subtotal,
            'discount' => (int) ($order->discount_amount ?? 0),
            'shippingFee' => (int) ($order->ghn_total_fee ?? 0),
        ];
    }

    /**
     * Khách tự hủy đơn khi shop chưa đóng gói và đơn chưa thanh toán online.
     * Hủy xong hoàn lại tồn kho ngay.
     */
    public function cancel(Request $request, Order $order)
    {
        abort_if((int) $order->user_id !== (int) Auth::id(), 403);

        $data = $request->validate([
            'cancel_reason' => ['required', 'string', 'max:255'],
        ], [], ['cancel_reason' => 'Lý do hủy']);

        if (! $order->canBeCancelledByCustomer()) {
            return back()->with('error', 'Đơn hàng này không còn ở trạng thái có thể tự hủy. Vui lòng liên hệ shop qua khung chat để được hỗ trợ.');
        }

        DB::transaction(function () use ($order, $data) {
            $from = $order->status;

            if (is_null($order->restocked_at)) {
                foreach ($order->items as $item) {
                    Product::where('id', $item->product_id)->increment('quantity', $item->quantity);
                }
                $order->restocked_at = now();
            }

            $order->status = 'cancelled';
            $order->shipping_status = 'cancelled';
            $order->payment_status = 'cancelled';
            $order->cancel_reason = $data['cancel_reason'];
            $order->cancelled_at = now();
            $order->save();

            PaymentTransaction::where('order_id', $order->id)
                ->whereIn('status', ['pending', 'initiated'])
                ->update(['status' => 'cancelled', 'message' => 'Khách hàng tự hủy đơn']);

            OrderStatusLog::create([
                'order_id' => $order->id,
                'changed_by' => Auth::id(),
                'from_status' => $from,
                'to_status' => 'cancelled',
                'note' => 'Khách tự hủy. Lý do: ' . $data['cancel_reason'],
            ]);
        });

        return redirect()->route('orders.index')->with('success', 'Đã hủy đơn hàng #' . $order->id . '. Sản phẩm đã được trả lại kho.');
    }

    // AJAX: danh sách Tỉnh/Thành phố cho select box GHN
    public function getProvinces()
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $this->ghnService->getProvinces(),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // AJAX: danh sách Quận/Huyện theo province_id
    public function getDistricts(Request $request)
    {
        $request->validate([
            'province_id' => 'required|integer',
        ]);

        try {
            return response()->json([
                'success' => true,
                'data' => $this->ghnService->getDistricts((int) $request->province_id),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // AJAX: danh sách Phường/Xã theo district_id
    public function getWards(Request $request)
    {
        $request->validate([
            'district_id' => 'required|integer',
        ]);

        try {
            return response()->json([
                'success' => true,
                'data' => $this->ghnService->getWards((int) $request->district_id),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // AJAX: tính phí vận chuyển dựa trên giỏ hàng trong session + địa chỉ nhận hàng đã chọn
    public function getShippingFee(Request $request)
    {
        $request->validate([
            'to_district_id' => 'required|integer',
            'to_ward_code' => 'required|string',
        ]);

        $cart = session('cart', []);

        if (empty($cart)) {
            return response()->json(['success' => false, 'message' => 'Giỏ hàng của bạn đang trống.'], 422);
        }

        $weight = $this->ghnOrderService->calculateWeightFromCart($cart);

        try {
            $result = $this->ghnService->calculateFee([
                'to_district_id' => (int) $request->to_district_id,
                'to_ward_code' => (string) $request->to_ward_code,
                'weight' => $weight,
            ]);

            return response()->json([
                'success' => true,
                'total_fee' => $result['total'] ?? 0,
                'data' => $result,
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
