<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

// Wrapper gọi cổng thanh toán MoMo (API v2 - AIO).
// Tài liệu: https://developers.momo.vn/v3/docs/payment/api/wallet/onetime
class MomoService
{
    /*
    |--------------------------------------------------------------------------
    | Các phương thức thanh toán MoMo cho khách chọn
    |--------------------------------------------------------------------------
    |
    | Khóa của mảng chính là giá trị requestType gửi sang API create của MoMo.
    | MoMo sẽ dựa vào requestType để mở đúng màn hình thanh toán tương ứng:
    |   - captureWallet : mở app MoMo / trang quét mã QR (mặc định trước đây)
    |   - payWithATM    : nhập thẻ ATM nội địa - Internet Banking
    |   - payWithCC     : nhập thẻ quốc tế Visa / Mastercard / JCB
    |   - payWithMethod : MoMo hiển thị đủ mọi phương thức để khách tự chọn tại cổng
    |
    | Lưu ý: payWithCC (thẻ quốc tế) phải được MoMo bật cho từng partner.
    | Nếu tài khoản merchant chưa đăng ký, MoMo sẽ trả về lỗi khi tạo giao dịch.
    */
    public const PAYMENT_OPTIONS = [
        'captureWallet' => [
            'label' => 'Ví MoMo / Quét mã QR',
            'description' => 'Mở ứng dụng MoMo hoặc quét mã QR để thanh toán.',
            'icon' => 'bi-qr-code-scan',
        ],
        'payWithATM' => [
            'label' => 'Thẻ ATM nội địa / Internet Banking',
            'description' => 'Thanh toán bằng thẻ ATM hoặc tài khoản ngân hàng trong nước.',
            'icon' => 'bi-bank',
        ],
        'payWithCC' => [
            'label' => 'Thẻ quốc tế Visa / Mastercard / JCB',
            'description' => 'Thanh toán bằng thẻ tín dụng hoặc thẻ ghi nợ quốc tế.',
            'icon' => 'bi-credit-card-2-front',
        ],
        'payWithMethod' => [
            'label' => 'Chọn phương thức tại cổng MoMo',
            'description' => 'MoMo sẽ hiển thị đầy đủ các phương thức để bạn chọn.',
            'icon' => 'bi-list-check',
        ],
    ];

    protected string $endpoint;
    protected string $partnerCode;
    protected string $accessKey;
    protected string $secretKey;
    protected string $partnerName;
    protected string $storeId;
    protected string $requestType;
    protected string $lang;
    protected string $redirectUrl;
    protected string $ipnUrl;
    protected bool $verifySsl;

    public function __construct()
    {
        $this->endpoint = (string) config('services.momo.endpoint');
        $this->partnerCode = (string) config('services.momo.partner_code');
        $this->accessKey = (string) config('services.momo.access_key');
        $this->secretKey = (string) config('services.momo.secret_key');
        $this->partnerName = (string) config('services.momo.partner_name', 'ToyLand');
        $this->storeId = (string) config('services.momo.store_id', 'ToyLandStore');
        $this->requestType = (string) config('services.momo.request_type', 'payWithATM');
        $this->lang = (string) config('services.momo.lang', 'vi');
        $this->redirectUrl = (string) config('services.momo.redirect_url');
        $this->ipnUrl = (string) config('services.momo.ipn_url');
        $this->verifySsl = (bool) config('services.momo.verify_ssl', true);
    }

    /*
    |--------------------------------------------------------------------------
    | Sinh mã đơn gửi sang MoMo
    |--------------------------------------------------------------------------
    */

    // MoMo yêu cầu orderId DUY NHẤT trên toàn bộ partner (gửi lại mã cũ sẽ bị từ chối),
    // nên phải gắn thêm timestamp + hậu tố ngẫu nhiên. Nhờ vậy khách bấm "Thanh toán lại"
    // nhiều lần trên cùng 1 đơn vẫn tạo được giao dịch mới mà không đụng mã đã dùng.
    public function orderId(Order $order): string
    {
        return sprintf('TOYLAND%d_%d_%s', $order->id, time(), Str::upper(Str::random(4)));
    }

    /*
    |--------------------------------------------------------------------------
    | Phương thức thanh toán khách chọn
    |--------------------------------------------------------------------------
    */

    // Danh sách phương thức để render ra form checkout / trang lịch sử đơn hàng.
    public static function paymentOptions(): array
    {
        return self::PAYMENT_OPTIONS;
    }

    public static function isValidPaymentOption(?string $option): bool
    {
        return $option !== null && array_key_exists($option, self::PAYMENT_OPTIONS);
    }

    // Nhãn hiển thị cho khách (dùng ở trang đơn hàng). Trả về null nếu không nhận diện được.
    public static function paymentOptionLabel(?string $option): ?string
    {
        return self::PAYMENT_OPTIONS[$option]['label'] ?? null;
    }

    // Phương thức mặc định khi khách không chọn gì: lấy từ .env (MOMO_REQUEST_TYPE),
    // nếu giá trị trong .env không hợp lệ thì quay về ví MoMo/QR.
    public function defaultPaymentOption(): string
    {
        return self::isValidPaymentOption($this->requestType) ? $this->requestType : 'captureWallet';
    }

    // Chỉ chấp nhận giá trị nằm trong whitelist - tuyệt đối không gửi thẳng
    // dữ liệu người dùng nhập vào requestType của MoMo.
    public function resolvePaymentOption(?string $option): string
    {
        return self::isValidPaymentOption($option) ? $option : $this->defaultPaymentOption();
    }

    /*
    |--------------------------------------------------------------------------
    | Tạo yêu cầu thanh toán
    |--------------------------------------------------------------------------
    */

    // Gọi API create của MoMo, trả về mảng response (đã kiểm tra resultCode = 0 và có payUrl).
    // Đồng thời ghi lại request/response vào bản ghi PaymentTransaction để đối soát sau này.
    //
    // $paymentOption là phương thức khách chọn (khóa trong PAYMENT_OPTIONS). Truyền null
    // hoặc giá trị lạ sẽ tự động rơi về phương thức mặc định cấu hình trong .env.
    public function createPayment(Order $order, PaymentTransaction $transaction, ?string $paymentOption = null): array
    {
        $this->ensureConfigured();

        // Ưu tiên lựa chọn vừa truyền vào, sau đó tới lựa chọn đã lưu trên giao dịch
        // (trường hợp thanh toán lại), cuối cùng mới tới mặc định.
        $requestType = $this->resolvePaymentOption($paymentOption ?? $transaction->payment_option);

        // MoMo chỉ nhận số tiền nguyên (VNĐ, không có phần thập phân)
        $amount = (int) round($order->total_price);
        $gatewayOrderId = $transaction->gateway_order_id ?: $this->orderId($order);
        $requestId = (string) Str::uuid();
        $orderInfo = 'Thanh toan don hang #' . $order->id . ' tai ToyLand';
        $extraData = '';

        // Chuỗi ký của MoMo BẮT BUỘC đúng thứ tự alphabet dưới đây, sai 1 ký tự là lỗi chữ ký
        $rawHash = 'accessKey=' . $this->accessKey
            . '&amount=' . $amount
            . '&extraData=' . $extraData
            . '&ipnUrl=' . $this->ipnUrl
            . '&orderId=' . $gatewayOrderId
            . '&orderInfo=' . $orderInfo
            . '&partnerCode=' . $this->partnerCode
            . '&redirectUrl=' . $this->redirectUrl
            . '&requestId=' . $requestId
            . '&requestType=' . $requestType;

        $payload = [
            'partnerCode' => $this->partnerCode,
            'partnerName' => $this->partnerName,
            'storeId' => $this->storeId,
            'requestId' => $requestId,
            'amount' => $amount,
            'orderId' => $gatewayOrderId,
            'orderInfo' => $orderInfo,
            'redirectUrl' => $this->redirectUrl,
            'ipnUrl' => $this->ipnUrl,
            'lang' => $this->lang,
            'extraData' => $extraData,
            'requestType' => $requestType,
            'signature' => $this->hash($rawHash),
        ];

        // payWithMethod = để khách tự chọn phương thức ngay trên cổng MoMo.
        // Kiểu này cần thêm autoCapture/orderGroupId trong body (KHÔNG nằm trong chuỗi ký).
        if ($requestType === 'payWithMethod') {
            $payload['autoCapture'] = true;
            $payload['orderGroupId'] = '';
        }

        $transaction->update([
            'gateway_order_id' => $gatewayOrderId,
            'amount' => $amount,
            'payment_option' => $requestType,
            'request_payload' => $payload,
        ]);

        try {
            $response = Http::withOptions(['verify' => $this->verifySsl])
                ->acceptJson()
                ->timeout(20)
                ->post($this->endpoint, $payload);
        } catch (Throwable $e) {
            Log::error('Không gọi được API tạo thanh toán MoMo', [
                'order_id' => $order->id,
                'gateway_order_id' => $gatewayOrderId,
                'message' => $e->getMessage(),
            ]);

            $this->markFailed($transaction, ['message' => $e->getMessage()]);

            throw new RuntimeException('Không kết nối được tới cổng thanh toán MoMo: ' . $e->getMessage(), 0, $e);
        }

        $data = $response->json() ?? [];

        $transaction->update(['response_payload' => $data]);

        if (!$response->successful() || !$this->isSuccessful($data) || empty($data['payUrl'])) {
            Log::error('MoMo từ chối yêu cầu tạo thanh toán', [
                'order_id' => $order->id,
                'gateway_order_id' => $gatewayOrderId,
                'request_type' => $requestType,
                'http_status' => $response->status(),
                'response' => $data,
            ]);

            $this->markFailed($transaction, $data);

            throw new RuntimeException($data['message'] ?? 'MoMo từ chối yêu cầu thanh toán, vui lòng thử lại.');
        }

        return $data;
    }

    /*
    |--------------------------------------------------------------------------
    | Kiểm tra dữ liệu MoMo trả về (callback GET & IPN POST)
    |--------------------------------------------------------------------------
    */

    // resultCode = 0 nghĩa là giao dịch thành công (mọi mã khác đều là thất bại/hủy)
    public function isSuccessful(array $data): bool
    {
        return isset($data['resultCode']) && (int) $data['resultCode'] === 0;
    }

    // Xác thực chữ ký HMAC SHA256 để chắc chắn dữ liệu đúng là do MoMo gửi,
    // chứ không phải khách tự sửa query string để "thanh toán" miễn phí.
    // Thứ tự các trường trong chuỗi ký là cố định theo tài liệu MoMo v2.
    public function isValidResponse(array $data): bool
    {
        if (empty($data['signature']) || empty($data['orderId'])) {
            return false;
        }

        // Chỉ chấp nhận dữ liệu đúng partnerCode của shop
        if (($data['partnerCode'] ?? '') !== $this->partnerCode) {
            return false;
        }

        $rawHash = 'accessKey=' . $this->accessKey
            . '&amount=' . ($data['amount'] ?? '')
            . '&extraData=' . ($data['extraData'] ?? '')
            . '&message=' . ($data['message'] ?? '')
            . '&orderId=' . ($data['orderId'] ?? '')
            . '&orderInfo=' . ($data['orderInfo'] ?? '')
            . '&orderType=' . ($data['orderType'] ?? '')
            . '&partnerCode=' . ($data['partnerCode'] ?? '')
            . '&payType=' . ($data['payType'] ?? '')
            . '&requestId=' . ($data['requestId'] ?? '')
            . '&responseTime=' . ($data['responseTime'] ?? '')
            . '&resultCode=' . ($data['resultCode'] ?? '')
            . '&transId=' . ($data['transId'] ?? '');

        // hash_equals: so sánh chống timing attack
        return hash_equals($this->hash($rawHash), (string) $data['signature']);
    }

    // Vừa đúng chữ ký, vừa báo thành công -> mới được phép ghi nhận là đã thu tiền
    public function isValidSuccessfulResponse(array $data): bool
    {
        return $this->isValidResponse($data) && $this->isSuccessful($data);
    }

    /*
    |--------------------------------------------------------------------------
    | Cập nhật kết quả giao dịch
    |--------------------------------------------------------------------------
    */

    // Ghi nhận thanh toán thành công: cập nhật giao dịch + payment_status của đơn hàng.
    // Hàm này IDEMPOTENT - cả callback (khách bấm quay về) lẫn IPN (MoMo bắn webhook)
    // đều gọi tới; nếu giao dịch đã 'paid' thì bỏ qua, không ghi đè paid_at.
    public function markPaid(PaymentTransaction $transaction, array $data = []): PaymentTransaction
    {
        if ($transaction->status === 'paid') {
            return $transaction;
        }

        $transaction->update([
            'status' => 'paid',
            'transaction_id' => isset($data['transId']) ? (string) $data['transId'] : $transaction->transaction_id,
            'result_code' => isset($data['resultCode']) ? (string) $data['resultCode'] : '0',
            'message' => $data['message'] ?? 'Thanh toán thành công',
            'response_payload' => $data ?: $transaction->response_payload,
            'paid_at' => now(),
        ]);

        $order = $transaction->order;

        // Chỉ đụng vào payment_status, KHÔNG đổi status của đơn hàng
        // để không phá luồng xử lý đơn/giao hàng hiện có của admin.
        if ($order && $order->payment_status !== 'paid') {
            $order->update(['payment_status' => 'paid']);
        }

        return $transaction;
    }

    // Ghi nhận thất bại/hủy. KHÔNG đụng vào giao dịch đã thành công trước đó
    // (tránh trường hợp 1 giao dịch cũ bị timeout bắn kết quả về muộn,
    // ghi đè lên đơn hàng vừa thanh toán thành công ở lần thử sau).
    public function markFailed(PaymentTransaction $transaction, array $data = []): PaymentTransaction
    {
        if ($transaction->status === 'paid') {
            return $transaction;
        }

        $transaction->update([
            'status' => 'failed',
            'transaction_id' => isset($data['transId']) ? (string) $data['transId'] : $transaction->transaction_id,
            'result_code' => isset($data['resultCode']) ? (string) $data['resultCode'] : $transaction->result_code,
            'message' => $data['message'] ?? 'Thanh toán không thành công',
            'response_payload' => $data ?: $transaction->response_payload,
        ]);

        $order = $transaction->order;

        if ($order && $order->payment_status !== 'paid') {
            $order->update(['payment_status' => 'failed']);
        }

        return $transaction;
    }

    /*
    |--------------------------------------------------------------------------
    | Helper
    |--------------------------------------------------------------------------
    */

    protected function hash(string $rawHash): string
    {
        return hash_hmac('sha256', $rawHash, $this->secretKey);
    }

    // Chặn sớm với thông báo rõ ràng nếu quên khai báo biến trong .env,
    // thay vì để MoMo trả về lỗi chữ ký khó hiểu.
    protected function ensureConfigured(): void
    {
        $required = [
            'partner_code' => $this->partnerCode,
            'access_key' => $this->accessKey,
            'secret_key' => $this->secretKey,
            'endpoint' => $this->endpoint,
            'redirect_url' => $this->redirectUrl,
            'ipn_url' => $this->ipnUrl,
        ];

        foreach ($required as $key => $value) {
            if ($value === '') {
                throw new RuntimeException("Thiếu cấu hình MoMo: services.momo.{$key} (kiểm tra lại file .env).");
            }
        }
    }
}
