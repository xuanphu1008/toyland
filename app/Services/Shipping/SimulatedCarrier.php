<?php

namespace App\Services\Shipping;

use App\Models\Order;

/**
 * ĐƠN VỊ VẬN CHUYỂN MÔ PHỎNG
 *
 * Dùng khi làm đồ án/demo: không gọi ra ngoài, mã vận đơn do hệ thống tự cấp,
 * và người quản trị ĐÓNG VAI bên giao hàng trên màn hình "Đơn vị vận chuyển"
 * (lấy hàng -> đi giao -> giao thành công / thất bại -> hoàn hàng).
 *
 * Mã vận đơn: TL + yymmdd + mã đơn 6 chữ số, ví dụ TL261001000123.
 * Gắn mã đơn vào nên không bao giờ trùng, và nhìn mã là biết ngày tạo.
 */
class SimulatedCarrier implements ShippingCarrier
{
    public function code(): string
    {
        return 'simulated';
    }

    public function name(): string
    {
        return 'ToyLand Express (mô phỏng)';
    }

    public function createWaybill(Order $order): string
    {
        return 'TL' . now()->format('ymd') . str_pad((string) $order->id, 6, '0', STR_PAD_LEFT);
    }

    public function cancelWaybill(Order $order): void
    {
        // Không có hệ thống bên ngoài nào cần báo hủy
    }

    public function isManuallyOperated(): bool
    {
        return true;
    }
}
