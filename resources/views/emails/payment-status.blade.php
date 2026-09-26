<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Cập nhật thanh toán đơn #{{ $order->id }}</title>
</head>
<body style="margin:0;padding:0;background:#FFF8F2;font-family:Segoe UI,Tahoma,Arial,sans-serif;color:#4A2F2F;">
    <div style="max-width:620px;margin:24px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,.06);">

        @php
        // Xanh cho tin tốt (đã nhận tiền, đã hoàn tiền), đỏ cho tin cần khách chú ý,
        // cam cho trạng thái đang chờ - khách nhìn màu là đoán được nội dung.
        $headerColor = match ($newStatus) {
            'paid', 'refunded' => '#357A22',
            'failed' => '#D32B2B',
            default => '#E07B12',
        };
        @endphp

        <div style="background:{{ $headerColor }};color:#fff;padding:24px;text-align:center;">
            <div style="font-size:22px;font-weight:bold;">🧸 ToyLand</div>
            <div style="font-size:14px;opacity:.9;margin-top:4px;">Cập nhật thanh toán đơn hàng #{{ $order->id }}</div>
        </div>

        <div style="padding:24px;">
            <p>Xin chào <strong>{{ $order->user->name ?? 'quý khách' }}</strong>,</p>

            <p style="font-size:16px;">
                Khoản thanh toán của đơn hàng <strong>#{{ $order->id }}</strong> đã chuyển sang trạng thái:
                <span style="display:inline-block;background:{{ $headerColor }};color:#fff;padding:4px 14px;border-radius:999px;font-weight:bold;">
                    {{ $statusLabel }}
                </span>
            </p>

            @if ($customerMessage !== '')
            <div style="background:#FFF9F1;border-radius:8px;padding:14px;font-size:14px;">
                {{ $customerMessage }}
            </div>
            @endif

            <div style="background:#FFF9F1;border-radius:8px;padding:14px;font-size:14px;margin-top:16px;">
                <div><strong>Hình thức thanh toán:</strong> Thanh toán khi nhận hàng (COD)</div>
                <div style="margin-top:6px;"><strong>Số tiền:</strong> {{ number_format($order->total_price, 0, ',', '.') }} đ</div>
            </div>

            <p style="margin-top:20px;">
                <a href="{{ route('orders.show', $order->id) }}"
                   style="display:inline-block;background:{{ $headerColor }};color:#fff;text-decoration:none;padding:10px 22px;border-radius:999px;">
                    Xem chi tiết đơn hàng
                </a>
            </p>

            <p style="font-size:13px;color:#8A6A6A;margin-top:20px;">
                Nếu thông tin trên chưa đúng, bạn vui lòng gọi 1900 6868 hoặc trả lời email này để ToyLand kiểm tra lại.
            </p>
        </div>

        <div style="background:#FFF1EA;padding:14px;text-align:center;color:#8A6A6A;font-size:12px;">
            © {{ date('Y') }} ToyLand - Thế giới đồ chơi cho bé
        </div>
    </div>
</body>
</html>
