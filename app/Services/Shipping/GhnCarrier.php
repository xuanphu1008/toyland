<?php

namespace App\Services\Shipping;

use App\Models\Order;
use App\Services\GHNOrderService;
use RuntimeException;
use Throwable;

/**
 * GIAO HÀNG NHANH (GHN) THẬT - bật bằng SHIPPING_CARRIER=ghn.
 *
 * Mã vận đơn do GHN cấp khi gọi API tạo đơn (/v2/shipping-order/create).
 * Trạng thái giao nhận về sau do GHN cập nhật (cần khai webhook GHN trỏ về web);
 * màn hình "Đơn vị vận chuyển" vẫn mở cho quản trị viên cập nhật tay khi cần.
 */
class GhnCarrier implements ShippingCarrier
{
    public function __construct(private GHNOrderService $ghn)
    {
    }

    public function code(): string
    {
        return 'ghn';
    }

    public function name(): string
    {
        return 'Giao Hàng Nhanh';
    }

    public function createWaybill(Order $order): string
    {
        try {
            $result = $this->ghn->create($order->loadMissing('items.product'), $order->payment_method === 'cod');
        } catch (Throwable $e) {
            throw new RuntimeException('GHN không tạo được vận đơn: ' . $e->getMessage(), 0, $e);
        }

        $code = $result['order_code'] ?? ($result['data']['order_code'] ?? null);

        if (! $code) {
            throw new RuntimeException('GHN không trả về mã vận đơn.');
        }

        return (string) $code;
    }

    public function cancelWaybill(Order $order): void
    {
        if (! $order->ghn_order_code) {
            return;
        }

        try {
            $this->ghn->cancelOrder($order);
        } catch (Throwable $e) {
            throw new RuntimeException('GHN không hủy được vận đơn ' . $order->ghn_order_code . ': ' . $e->getMessage(), 0, $e);
        }
    }

    public function isManuallyOperated(): bool
    {
        return false;
    }
}
