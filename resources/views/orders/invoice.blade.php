{{--
    HÓA ĐƠN IN / XUẤT PDF

    Cố ý KHÔNG dùng layout chung: trang này không có thanh điều hướng, chân
    trang hay khung chat - in ra chỉ còn nội dung hóa đơn.

    VÌ SAO KHÔNG DÙNG THƯ VIỆN PDF (dompdf, mPDF...):
    Cài thư viện là phải `composer install`, mà máy đang chạy Laragon offline
    thì bước đó hay hỏng nhất. Trong khi mọi trình duyệt đều có sẵn chức năng
    "In → Lưu thành PDF" cho ra file đúng chuẩn, chữ Việt không lỗi phông,
    lại chọn được khổ giấy. Làm HTML tối ưu cho in là đủ và bền hơn.
--}}
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hóa đơn #{{ $order->id }} — ToyLand</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 24px;
            background: #F4F4F5;
            font-family: "Segoe UI", Tahoma, Arial, sans-serif;
            color: #2E2121;
            font-size: 14px;
            line-height: 1.55;
        }

        .sheet {
            max-width: 820px;
            margin: 0 auto;
            background: #fff;
            padding: 36px 40px;
            border-radius: 8px;
            box-shadow: 0 2px 14px rgba(0, 0, 0, .08);
        }

        .head { display: flex; justify-content: space-between; gap: 24px; flex-wrap: wrap; }
        .brand { font-size: 24px; font-weight: 700; color: #D62B2B; }
        .muted { color: #6B5555; font-size: 12.5px; }

        .title {
            text-align: right;
            font-size: 26px;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .divider { height: 2px; background: #D62B2B; margin: 20px 0 24px; }

        .cols { display: flex; gap: 32px; flex-wrap: wrap; margin-bottom: 26px; }
        .col { flex: 1 1 240px; min-width: 0; }

        .label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #8B6F6F;
            font-weight: 700;
            margin-bottom: 5px;
        }

        table { width: 100%; border-collapse: collapse; margin-bottom: 22px; }
        th { background: #FFF1EA; text-align: left; font-size: 12px; text-transform: uppercase; letter-spacing: .05em; }
        th, td { padding: 9px 10px; border-bottom: 1px solid #EFE2DC; vertical-align: top; }
        .num { text-align: right; white-space: nowrap; }
        .ctr { text-align: center; }

        .totals { margin-left: auto; width: 100%; max-width: 330px; }
        .totals td { border: none; padding: 5px 10px; }
        .totals .grand td {
            border-top: 2px solid #D62B2B;
            padding-top: 10px;
            font-size: 17px;
            font-weight: 700;
            color: #D62B2B;
        }

        .badge {
            display: inline-block;
            padding: 3px 11px;
            border-radius: 999px;
            font-size: 11.5px;
            font-weight: 700;
            background: #FFF1EA;
            color: #D62B2B;
        }

        .foot { margin-top: 30px; padding-top: 18px; border-top: 1px solid #EFE2DC; font-size: 12px; color: #6B5555; }
        .sign { display: flex; justify-content: space-between; gap: 24px; margin-top: 36px; text-align: center; }
        .sign > div { flex: 1; }
        .sign .line { margin-top: 58px; border-top: 1px dotted #A89090; padding-top: 6px; font-size: 12px; color: #8B6F6F; }

        .bar { max-width: 820px; margin: 0 auto 16px; display: flex; gap: 10px; flex-wrap: wrap; }

        .btn {
            border: none;
            border-radius: 999px;
            padding: 9px 22px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .btn-main { background: #D62B2B; color: #fff; }
        .btn-ghost { background: #fff; color: #2E2121; border: 1px solid #D9C9C2; }

        /* ---- Khi IN: bỏ hết phần thừa, ép về khổ A4 trắng ---- */
        @media print {
            body { background: #fff; padding: 0; font-size: 12.5px; }
            .sheet { max-width: none; box-shadow: none; border-radius: 0; padding: 0; }
            .bar { display: none; }         /* nút bấm không được lên giấy */
            th { background: #FFF1EA !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            tr { page-break-inside: avoid; }
        }

        @page { size: A4; margin: 14mm; }
    </style>
</head>
<body>

<div class="bar">
    <button class="btn btn-main" onclick="window.print()">🖨️ In / Lưu thành PDF</button>
    <a class="btn btn-ghost" href="{{ $backUrl }}">← Quay lại</a>
</div>

<div class="sheet">

    {{-- ============ ĐẦU HÓA ĐƠN ============ --}}
    <div class="head">
        <div>
            <div class="brand">🧸 ToyLand</div>
            <div class="muted">
                Thế giới đồ chơi cho bé<br>
                Hotline: 1900 6868 · hotro@toyland.vn<br>
                toyland.vn
            </div>
        </div>

        <div>
            <div class="title">Hóa đơn</div>
            <div class="muted" style="text-align: right;">
                Số: <strong>#{{ $order->id }}</strong><br>
                Ngày: {{ optional($order->created_at)->format('d/m/Y H:i') }}<br>
                <span class="badge">{{ $order->status_label }}</span>
            </div>
        </div>
    </div>

    <div class="divider"></div>

    {{-- ============ THÔNG TIN HAI BÊN ============ --}}
    <div class="cols">
        <div class="col">
            <div class="label">Khách hàng</div>
            <strong>{{ $order->user->name ?? 'Khách lẻ' }}</strong><br>
            @if ($order->customer_phone) {{ $order->customer_phone }}<br> @endif
            @if ($order->user && $order->user->email) <span class="muted">{{ $order->user->email }}</span><br> @endif
            @if ($order->shipping_address) {{ $order->shipping_address }} @endif
        </div>

        <div class="col">
            <div class="label">Thanh toán & vận chuyển</div>
            Hình thức: <strong>{{ $order->payment_method === 'momo' ? 'MoMo' : 'COD khi nhận hàng' }}</strong><br>
            Tình trạng: <strong>{{ $order->isPaid() ? 'Đã thanh toán' : 'Chưa thanh toán' }}</strong><br>
            Đơn vị giao: Giao Hàng Nhanh (GHN)
            @if (! empty($order->ghn_order_code))
            <br>Mã vận đơn GHN: <strong>{{ $order->ghn_order_code }}</strong>
            @endif
        </div>
    </div>

    {{-- ============ BẢNG HÀNG ============ --}}
    <table>
        <thead>
            <tr>
                <th style="width: 34px;" class="ctr">#</th>
                <th>Sản phẩm</th>
                <th class="num" style="width: 110px;">Đơn giá</th>
                <th class="ctr" style="width: 56px;">SL</th>
                <th class="num" style="width: 120px;">Thành tiền</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($order->items as $i => $item)
            <tr>
                <td class="ctr">{{ $i + 1 }}</td>
                <td>
                    {{ $item->product->name ?? 'Sản phẩm đã bị gỡ' }}
                    @if ($item->product && $item->product->sku)
                    <div class="muted">Mã: {{ $item->product->sku }}</div>
                    @endif
                </td>
                <td class="num">{{ number_format($item->price, 0, ',', '.') }}</td>
                <td class="ctr">{{ $item->quantity }}</td>
                <td class="num">{{ number_format($item->price * $item->quantity, 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="ctr muted">Đơn hàng không có sản phẩm nào.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- ============ TỔNG TIỀN ============ --}}
    <table class="totals">
        <tr>
            <td>Tạm tính</td>
            <td class="num">{{ number_format($subtotal, 0, ',', '.') }} đ</td>
        </tr>

        @if ($discount > 0)
        <tr>
            <td>Giảm giá@if ($order->coupon) ({{ $order->coupon->code }})@endif</td>
            <td class="num">− {{ number_format($discount, 0, ',', '.') }} đ</td>
        </tr>
        @endif

        <tr>
            <td>Phí vận chuyển</td>
            <td class="num">{{ number_format($shippingFee, 0, ',', '.') }} đ</td>
        </tr>

        <tr class="grand">
            <td>Tổng cộng</td>
            <td class="num">{{ number_format($order->total_price, 0, ',', '.') }} đ</td>
        </tr>
    </table>

    {{-- ============ CHỮ KÝ ============ --}}
    <div class="sign">
        <div>
            <strong>Người mua hàng</strong>
            <div class="line">(Ký, ghi rõ họ tên)</div>
        </div>
        <div>
            <strong>Người bán hàng</strong>
            <div class="line">(Ký, ghi rõ họ tên)</div>
        </div>
    </div>

    <div class="foot">
        Cảm ơn bạn đã mua hàng tại ToyLand! Hóa đơn được tạo tự động từ hệ thống lúc
        {{ now()->format('H:i d/m/Y') }}, có giá trị tra cứu nội bộ.
        Đổi trả trong 7 ngày kể từ khi nhận hàng — giữ nguyên hộp và phụ kiện giúp shop nhé.
    </div>
</div>

</body>
</html>
