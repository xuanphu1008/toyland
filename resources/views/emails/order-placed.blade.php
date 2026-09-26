<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Xác nhận đơn hàng #{{ $order->id }}</title>
</head>
<body style="margin:0;padding:0;background:#FFF8F2;font-family:Segoe UI,Tahoma,Arial,sans-serif;color:#4A2F2F;">
    <div style="max-width:620px;margin:24px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,.06);">

        <div style="background:#E03131;color:#fff;padding:24px;text-align:center;">
            <div style="font-size:22px;font-weight:bold;">🧸 ToyLand</div>
            <div style="font-size:14px;opacity:.9;margin-top:4px;">Cảm ơn bạn đã đặt hàng!</div>
        </div>

        <div style="padding:24px;">
            <p>Xin chào <strong>{{ $order->user->name ?? 'quý khách' }}</strong>,</p>
            <p>
                ToyLand đã nhận được đơn hàng <strong>#{{ $order->id }}</strong>
                đặt lúc {{ $order->created_at->format('H:i d/m/Y') }}.
                Chúng tôi sẽ xác nhận và chuẩn bị hàng trong thời gian sớm nhất.
            </p>

            <table style="width:100%;border-collapse:collapse;margin:18px 0;font-size:14px;">
                <thead>
                    <tr style="background:#FFF1EA;">
                        <th style="text-align:left;padding:10px;border-bottom:1px solid #FBE1D8;">Sản phẩm</th>
                        <th style="text-align:center;padding:10px;border-bottom:1px solid #FBE1D8;">SL</th>
                        <th style="text-align:right;padding:10px;border-bottom:1px solid #FBE1D8;">Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $item)
                    <tr>
                        <td style="padding:10px;border-bottom:1px solid #FFF1EA;">{{ $item->product->name ?? 'Sản phẩm' }}</td>
                        <td style="padding:10px;border-bottom:1px solid #FFF1EA;text-align:center;">{{ $item->quantity }}</td>
                        <td style="padding:10px;border-bottom:1px solid #FFF1EA;text-align:right;">{{ number_format($item->price * $item->quantity, 0, ',', '.') }} đ</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    @if ($order->ghn_total_fee)
                    <tr>
                        <td colspan="2" style="padding:8px 10px;text-align:right;color:#8A6A6A;">Phí vận chuyển</td>
                        <td style="padding:8px 10px;text-align:right;">{{ number_format($order->ghn_total_fee, 0, ',', '.') }} đ</td>
                    </tr>
                    @endif
                    <tr>
                        <td colspan="2" style="padding:10px;text-align:right;font-weight:bold;">Tổng cộng</td>
                        <td style="padding:10px;text-align:right;font-weight:bold;color:#D32B2B;font-size:17px;">
                            {{ number_format($order->total_price, 0, ',', '.') }} đ
                        </td>
                    </tr>
                </tfoot>
            </table>

            <div style="background:#FFF9F1;border-radius:8px;padding:14px;font-size:14px;">
                <div><strong>Địa chỉ nhận hàng:</strong> {{ $order->shipping_address }}</div>
                <div style="margin-top:6px;">
                    <strong>Hình thức thanh toán:</strong>
                    {{ $order->payment_method === 'momo' ? 'Ví MoMo' : 'COD - Thanh toán khi nhận hàng' }}
                </div>
            </div>

            <p style="margin-top:20px;">
                <a href="{{ route('orders.show', $order->id) }}"
                   style="display:inline-block;background:#E03131;color:#fff;text-decoration:none;padding:10px 22px;border-radius:999px;">
                    Theo dõi đơn hàng
                </a>
            </p>

            <p style="color:#8A6A6A;font-size:13px;margin-top:22px;">
                Nếu bạn cần hỗ trợ, hãy trả lời email này hoặc nhắn tin trực tiếp cho ToyLand trên website.
            </p>
        </div>

        <div style="background:#FFF1EA;padding:14px;text-align:center;color:#8A6A6A;font-size:12px;">
            © {{ date('Y') }} ToyLand - Thế giới đồ chơi cho bé
        </div>
    </div>
</body>
</html>
