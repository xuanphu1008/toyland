<?php

namespace App\Services\Shipping;

use App\Models\Order;

/**
 * Đơn vị vận chuyển. Mọi nơi trong hệ thống chỉ nói chuyện với interface này,
 * nên đổi từ bản mô phỏng sang GHN thật chỉ là đổi SHIPPING_CARRIER trong .env.
 */
interface ShippingCarrier
{
    /** Mã ngắn lưu vào orders.carrier */
    public function code(): string;

    /** Tên hiển thị */
    public function name(): string;

    /**
     * Tạo vận đơn cho đơn đã đóng gói, trả về MÃ VẬN ĐƠN.
     * Ném RuntimeException kèm lời giải thích nếu không tạo được.
     */
    public function createWaybill(Order $order): string;

    /** Hủy vận đơn (shop hủy đơn khi bên vận chuyển chưa lấy hàng) */
    public function cancelWaybill(Order $order): void;

    /**
     * Trạng thái giao nhận có do CHÍNH màn hình "Đơn vị vận chuyển" trong trang
     * quản trị cập nhật không. Bản mô phỏng: có. GHN thật: không - GHN tự báo về.
     */
    public function isManuallyOperated(): bool;
}
