<?php

namespace App\Services;

use App\Models\Order;
use RuntimeException;

// Ánh xạ dữ liệu từ Model Order/giỏ hàng session sang payload chuẩn của GHNService
class GHNOrderService
{
    // Sản phẩm chưa có cột "weight" trong bảng products nên tạm dùng khối lượng
    // mặc định (gram) cho mỗi đơn vị sản phẩm để ước tính cân nặng kiện hàng.
    protected const DEFAULT_ITEM_WEIGHT_GRAM = 200;
    protected const MIN_PACKAGE_WEIGHT_GRAM = 200;

    protected GHNService $ghnService;

    public function __construct(GHNService $ghnService)
    {
        $this->ghnService = $ghnService;
    }

    // Tính tổng khối lượng (gram) từ giỏ hàng session, dùng để tính phí ship trước khi tạo Order
    public function calculateWeightFromCart(array $cart): int
    {
        // Dùng cân nặng THẬT của từng sản phẩm (cột weight_grams) để phí ship sát thực tế.
        // Sản phẩm nào chưa khai cân nặng thì tạm tính theo mức mặc định.
        $productIds = array_column($cart, 'product_id');
        $weights = \App\Models\Product::whereIn('id', $productIds)->pluck('weight_grams', 'id');

        $total = 0;

        foreach ($cart as $item) {
            $quantity = (int) ($item['quantity'] ?? 0);
            $unitWeight = (int) ($weights[$item['product_id'] ?? 0] ?? 0);

            $total += $quantity * ($unitWeight > 0 ? $unitWeight : self::DEFAULT_ITEM_WEIGHT_GRAM);
        }

        return max(self::MIN_PACKAGE_WEIGHT_GRAM, $total);
    }

    // Tính tổng khối lượng (gram) từ các OrderItem đã lưu của 1 đơn hàng
    public function calculateWeight(Order $order): int
    {
        $order->loadMissing('items.product');

        $total = $order->items->sum(function ($item) {
            $unitWeight = (int) ($item->product->weight_grams ?? 0);

            return $item->quantity * ($unitWeight > 0 ? $unitWeight : self::DEFAULT_ITEM_WEIGHT_GRAM);
        });

        return max(self::MIN_PACKAGE_WEIGHT_GRAM, (int) $total);
    }

    // Dựng payload gửi cho API "tạo đơn" của GHN từ 1 Order đã lưu trong DB.
    // $allowViewGoods: có cho phép shipper mở gói cho khách xem hàng hay không (mặc định: xem nhưng không thử).
    public function buildOrderPayload(Order $order, bool $allowViewGoods = false): array
    {
        if (!$order->to_district_id || !$order->to_ward_code) {
            throw new RuntimeException('Đơn hàng thiếu thông tin Quận/Huyện, Phường/Xã để tạo vận đơn GHN.');
        }

        $order->loadMissing('items.product');

        [$name, $phone, $address] = $this->parseShippingAddress($order->shipping_address, $order->customer_phone);

        $items = $order->items->map(function ($item) {
            return [
                'name' => $item->display_name ?? 'Sản phẩm',
                'quantity' => (int) $item->quantity,
                'price' => (int) $item->price,
            ];
        })->values()->all();

        return [
            'payment_type_id' => 2, // Người nhận (khách hàng) trả phí ship
            'note' => 'ToyLand giao hàng',
            'required_note' => $this->resolveRequireNote($order, $allowViewGoods),
            'to_name' => $name,
            'to_phone' => $phone,
            'to_address' => $address,
            'to_ward_code' => (string) $order->to_ward_code,
            'to_district_id' => (int) $order->to_district_id,
            'cod_amount' => $order->payment_method === 'cod' ? (int) $order->total_price : 0,
            'weight' => $this->calculateWeight($order),
            'length' => 20,
            'width' => 20,
            'height' => 20,
            'service_type_id' => 2,
            'items' => $items,
        ];
    }

    // "require_note" GHN: đơn COD luôn cho xem hàng (có/không cho thử tuỳ $allowViewGoods),
    // đơn đã thanh toán trước (momo) thì không cho xem để tránh bom hàng đổi ý.
    protected function resolveRequireNote(Order $order, bool $allowViewGoods = false): string
    {
        if ($order->payment_method !== 'cod') {
            return 'KHONGCHOXEMHANG';
        }

        return $allowViewGoods ? 'CHOTHUHANG' : 'CHOXEMHANGKHONGTHU';
    }

    // shipping_address được lưu dạng "Tên | SĐT | Địa chỉ chi tiết" (xem CheckoutController::process)
    protected function parseShippingAddress(?string $shippingAddress, ?string $phone): array
    {
        $parts = array_map('trim', explode('|', (string) $shippingAddress, 3));

        $name = $parts[0] !== '' ? $parts[0] : 'Khách hàng';
        $resolvedPhone = $phone ?: ($parts[1] ?? '');
        $address = $parts[2] ?? (string) $shippingAddress;

        return [$name, $resolvedPhone, $address];
    }

    // Tạo vận đơn thật trên GHN cho 1 Order đã lưu trong DB.
    // $allowViewGoods: cho shipper mở gói cho khách xem hàng khi giao (chỉ áp dụng đơn COD).
    // Lưu ý: hàm này KHÔNG tự ý cập nhật Order - việc lưu ghn_order_code/shipping_status
    // do phía gọi (CheckoutController) đảm nhiệm, sau khi tự kiểm tra kết quả trả về.
    public function create(Order $order, bool $allowViewGoods = false): array
    {
        return $this->ghnService->createOrder(
            $this->buildOrderPayload($order, $allowViewGoods)
        );
    }

    public function cancelOrder(Order $order): array
    {
        if (!$order->ghn_order_code) {
            throw new RuntimeException('Đơn hàng chưa có mã vận đơn GHN để hủy.');
        }

        $result = $this->ghnService->cancelOrder($order->ghn_order_code);

        $order->update(['shipping_status' => 'cancelled']);

        return $result;
    }
}
