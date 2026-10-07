<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Services\MomoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

// Xử lý kết quả trả về từ cổng thanh toán MoMo.
//
// MoMo báo kết quả về hệ thống bằng HAI đường ĐỘC LẬP nhau:
//   1. callback (GET  /payment/momo/callback) - trình duyệt của khách được redirect về.
//      Có thể KHÔNG BAO GIỜ xảy ra (khách tắt trình duyệt giữa chừng) => không tin tuyệt đối.
//   2. ipn      (POST /payment/momo/ipn)      - MoMo gọi server-to-server, đáng tin cậy nhất.
//      Đây mới là nguồn chốt tiền chính thức, và nó có thể tới TRƯỚC hoặc SAU callback.
//
// Vì vậy cả hai đều đi qua completePayment() - vốn được viết idempotent,
// chạy bao nhiêu lần trên cùng 1 giao dịch cũng chỉ ghi nhận đúng 1 lần.
class MomoController extends Controller
{
    protected MomoService $momoService;

    public function __construct(MomoService $momoService)
    {
        $this->momoService = $momoService;
    }

    /*
    |--------------------------------------------------------------------------
    | 1. Callback - MoMo redirect trình duyệt khách quay về (route KHÔNG có auth)
    |--------------------------------------------------------------------------
    */
    public function callback(Request $request)
    {
        $data = $request->all();

        Log::info('MoMo callback', ['order_id' => $data['orderId'] ?? null, 'result_code' => $data['resultCode'] ?? null]);

        // Chữ ký sai = dữ liệu không phải do MoMo gửi (khách tự sửa URL) -> chặn ngay,
        // tuyệt đối không ghi nhận đã thanh toán.
        if (!$this->momoService->isValidResponse($data)) {
            Log::warning('MoMo callback có chữ ký không hợp lệ', ['payload' => $data]);

            return redirect()->route('orders.index')
                ->with('error', 'Dữ liệu thanh toán không hợp lệ. Vui lòng liên hệ bộ phận hỗ trợ.');
        }

        $transaction = $this->completePayment($data);

        if (!$transaction) {
            return redirect()->route('orders.index')
                ->with('error', 'Không tìm thấy giao dịch tương ứng. Vui lòng liên hệ bộ phận hỗ trợ.');
        }

        if ($transaction->isPaid() || $transaction->status === 'refund_pending') {
            $order = $transaction->order;

            if ($order && $order->status === 'cancelled') {
                return redirect()->route('orders.index')
                    ->with('error', 'Đã nhận thanh toán nhưng đơn #' . $order->id . ' đã hết thời gian giữ hàng nên bị hủy. Shop sẽ hoàn lại tiền cho bạn.');
            }

            return redirect()->route('orders.index')
                ->with('success', '🎉 Thanh toán MoMo thành công cho đơn hàng #' . $transaction->order_id . '. Cảm ơn bạn đã mua sắm tại ToyLand!');
        }

        return redirect()->route('orders.index')
            ->with('error', 'Thanh toán MoMo không thành công: ' . ($data['message'] ?? 'Giao dịch bị hủy') . '. Bạn có thể bấm "Thanh toán lại" ở đơn hàng bên dưới.');
    }

    /*
    |--------------------------------------------------------------------------
    | 2. IPN - MoMo gọi server-to-server (route KHÔNG có auth, KHÔNG có CSRF)
    |--------------------------------------------------------------------------
    */
    public function ipn(Request $request)
    {
        $data = $request->all();

        Log::info('MoMo IPN', ['order_id' => $data['orderId'] ?? null, 'result_code' => $data['resultCode'] ?? null]);

        if (!$this->momoService->isValidResponse($data)) {
            Log::warning('MoMo IPN có chữ ký không hợp lệ', ['payload' => $data]);

            return response()->json(['message' => 'Invalid signature'], 400);
        }

        $transaction = $this->completePayment($data);

        if (!$transaction) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        // MoMo yêu cầu merchant trả HTTP 204 (No Content) để xác nhận đã nhận IPN,
        // nếu trả mã khác MoMo sẽ gọi lại nhiều lần.
        return response()->noContent();
    }

    /*
    |--------------------------------------------------------------------------
    | 3. Thanh toán lại - khách bấm nút ở trang lịch sử đơn hàng (route CÓ auth)
    |--------------------------------------------------------------------------
    */
    public function payAgain(Request $request, Order $order)
    {
        // Ép kiểu (int) vì user_id lấy từ DB có thể là string, còn Auth::id() là int
        // (cùng lý do đã ghi trong CheckoutController@momo).
        abort_if((int) $order->user_id !== (int) Auth::id(), 403);

        if ($order->payment_method !== 'momo') {
            return redirect()->route('orders.index')
                ->with('error', 'Đơn hàng này không thanh toán qua MoMo.');
        }

        if ($order->payment_status === 'paid') {
            return redirect()->route('orders.index')
                ->with('success', 'Đơn hàng #' . $order->id . ' đã được thanh toán trước đó.');
        }

        // Đơn quá thời gian giữ hàng thì hủy luôn trước khi xét
        app(\App\Services\Orders\OrderWorkflow::class)->releaseExpired(true);
        $order->refresh();

        if ($order->status === 'cancelled') {
            return redirect()->route('orders.index')
                ->with('error', 'Đơn hàng #' . $order->id . ' đã hết thời gian giữ hàng hoặc đã bị hủy. Bạn vui lòng đặt lại đơn mới nhé.');
        }

        // Chỉ đơn đang chờ thanh toán (hoặc đơn cũ tạo trước khi có bước giữ hàng) mới trả tiền được
        if (! in_array($order->status, ['awaiting_payment', 'pending'], true)) {
            return redirect()->route('orders.index')
                ->with('error', 'Đơn hàng #' . $order->id . ' không còn ở trạng thái có thể thanh toán.');
        }

        // Phương thức khách chọn ở nút "Thanh toán lại" (?option=...). Nếu không truyền,
        // dùng lại phương thức của lần thanh toán gần nhất, cuối cùng mới tới mặc định.
        // resolvePaymentOption() lọc theo whitelist nên tham số URL không thể tùy tiện.
        $lastOption = $order->paymentTransactions()
            ->where('gateway', 'momo')
            ->latest('id')
            ->value('payment_option');

        $requestedOption = $request->query('option');
        $paymentOption = $this->momoService->resolvePaymentOption(
            is_string($requestedOption) && $requestedOption !== '' ? $requestedOption : $lastOption
        );

        // Mỗi lần thử lại là 1 bản ghi giao dịch MỚI (kèm gateway_order_id mới),
        // giữ nguyên lịch sử các lần thất bại trước để đối soát.
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
            return redirect()->route('orders.index')
                ->with('error', 'Không tạo được yêu cầu thanh toán MoMo: ' . $e->getMessage());
        }

        return redirect()->away($response['payUrl']);
    }

    /*
    |--------------------------------------------------------------------------
    | 4. Xử lý chung cho callback & IPN
    |--------------------------------------------------------------------------
    */

    // Tìm giao dịch theo orderId MoMo trả về rồi chốt trạng thái paid/failed.
    // Trả về null nếu không tìm thấy giao dịch tương ứng.
    protected function completePayment(array $data): ?PaymentTransaction
    {
        $transaction = PaymentTransaction::where('gateway', 'momo')
            ->where('gateway_order_id', $data['orderId'] ?? '')
            ->latest('id')
            ->first();

        if (!$transaction) {
            Log::warning('Không tìm thấy PaymentTransaction cho orderId MoMo', ['order_id' => $data['orderId'] ?? null]);

            return null;
        }

        // Đã chốt thành công rồi (callback và IPN cùng bắn về) -> bỏ qua, không xử lý lại
        // (kể cả khi tiền đã về muộn và đang chờ hoàn - MoMo có thể gửi IPN nhiều lần)
        if ($transaction->isPaid() || in_array($transaction->status, ['refund_pending', 'refunded'], true)) {
            return $transaction;
        }

        if (!$this->momoService->isSuccessful($data)) {
            return $this->momoService->markFailed($transaction, $data);
        }

        // Chữ ký đúng + resultCode = 0, nhưng vẫn phải đối chiếu số tiền:
        // phòng trường hợp số tiền nhận được lệch với số tiền đơn hàng.
        if ((int) round((float) ($data['amount'] ?? 0)) !== (int) round((float) $transaction->amount)) {
            Log::error('Số tiền MoMo trả về không khớp với giao dịch', [
                'transaction_id' => $transaction->id,
                'expected' => $transaction->amount,
                'received' => $data['amount'] ?? null,
            ]);

            return $this->momoService->markFailed($transaction, array_merge($data, [
                'message' => 'Số tiền thanh toán không khớp với đơn hàng',
            ]));
        }

        return $this->momoService->markPaid($transaction, $data);
    }
}
