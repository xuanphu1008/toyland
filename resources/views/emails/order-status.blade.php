<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Cập nhật đơn hàng #{{ $order->id }}</title>
</head>
<body style="margin:0;padding:0;background:#FFF8F2;font-family:Segoe UI,Tahoma,Arial,sans-serif;color:#4A2F2F;">
    <div style="max-width:620px;margin:24px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,.06);">

        @php
        $isCancelled = $order->status === 'cancelled';
        $headerColor = $isCancelled ? '#D32B2B' : ($order->status === 'completed' ? '#357A22' : '#E03131');
        @endphp

        <div style="background:{{ $headerColor }};color:#fff;padding:24px;text-align:center;">
            <div style="font-size:22px;font-weight:bold;">🧸 ToyLand</div>
            <div style="font-size:14px;opacity:.9;margin-top:4px;">Cập nhật đơn hàng #{{ $order->id }}</div>
        </div>

        <div style="padding:24px;">
            <p>Xin chào <strong>{{ $order->user->name ?? 'quý khách' }}</strong>,</p>

            <p style="font-size:16px;">
                Đơn hàng <strong>#{{ $order->id }}</strong> của bạn đã chuyển sang trạng thái:
                <span style="display:inline-block;background:{{ $headerColor }};color:#fff;padding:4px 14px;border-radius:999px;font-weight:bold;">
                    {{ \App\Models\Order::STATUSES[$order->status] ?? $order->status }}
                </span>
            </p>

            @if ($isCancelled && $order->cancel_reason)
            <div style="background:#FFECEC;border-radius:8px;padding:14px;font-size:14px;color:#B02525;">
                <strong>Lý do hủy:</strong> {{ $order->cancel_reason }}
                @if ($order->payment_status === 'refund_pending')
                <div style="margin-top:6px;">Đơn đã thanh toán sẽ được ToyLand liên hệ hoàn tiền trong 3-5 ngày làm việc.</div>
                @endif
            </div>
            @elseif ($order->status === 'completed')
            <div style="background:#EFFAE6;border-radius:8px;padding:14px;font-size:14px;color:#26601B;">
                Đơn hàng đã được giao thành công. Bạn có thể vào mục <strong>Đánh giá của tôi</strong> để chia sẻ cảm nhận
                về món đồ chơi vừa nhận nhé!
            </div>
            @endif

            <div style="background:#FFF9F1;border-radius:8px;padding:14px;font-size:14px;margin-top:16px;">
                <div><strong>Tổng tiền:</strong> {{ number_format($order->total_price, 0, ',', '.') }} đ</div>
                <div style="margin-top:6px;"><strong>Địa chỉ nhận hàng:</strong> {{ $order->shipping_address }}</div>
                @if ($order->ghn_order_code)
                <div style="margin-top:6px;"><strong>Mã vận đơn GHN:</strong> {{ $order->ghn_order_code }}</div>
                @endif
            </div>

            <p style="margin-top:20px;">
                <a href="{{ route('orders.show', $order->id) }}"
                   style="display:inline-block;background:{{ $headerColor }};color:#fff;text-decoration:none;padding:10px 22px;border-radius:999px;">
                    Xem chi tiết đơn hàng
                </a>
            </p>
        </div>

        <div style="background:#FFF1EA;padding:14px;text-align:center;color:#8A6A6A;font-size:12px;">
            © {{ date('Y') }} ToyLand - Thế giới đồ chơi cho bé
        </div>
    </div>
</body>
</html>
