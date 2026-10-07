<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Phiếu giao hàng ({{ $orders->count() }} đơn)</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; margin: 0; padding: 16px; color: #111; background: #f3f3f3; }
        .toolbar { max-width: 900px; margin: 0 auto 16px; display: flex; gap: 8px; align-items: center; }
        .toolbar button, .toolbar a { padding: 8px 16px; border-radius: 6px; border: 1px solid #333; background: #fff; cursor: pointer; text-decoration: none; color: #111; font-size: 14px; }
        .toolbar button { background: #111; color: #fff; }
        .sheet { max-width: 900px; margin: 0 auto; display: grid; grid-template-columns: repeat(auto-fill, minmax(420px, 1fr)); gap: 12px; }
        .label { background: #fff; border: 2px dashed #333; padding: 12px 14px; page-break-inside: avoid; break-inside: avoid; }
        .head { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #111; padding-bottom: 6px; }
        .brand { font-weight: bold; font-size: 15px; }
        .code { font-family: "Courier New", monospace; font-size: 22px; font-weight: bold; letter-spacing: 2px; text-align: center; margin: 8px 0 2px; }
        .bars { height: 34px; background: repeating-linear-gradient(90deg, #111 0 2px, #fff 2px 4px, #111 4px 5px, #fff 5px 8px); margin: 4px 0; }
        .row { margin-top: 6px; font-size: 13px; }
        .row b { display: inline-block; min-width: 72px; }
        .cod { margin-top: 8px; padding: 6px; border: 2px solid #111; text-align: center; font-size: 16px; font-weight: bold; }
        .items { margin-top: 6px; font-size: 12px; color: #333; }
        .foot { margin-top: 6px; font-size: 11px; color: #555; display: flex; justify-content: space-between; }
        @media print {
            body { background: #fff; padding: 0; }
            .toolbar { display: none; }
            .sheet { gap: 6mm; }
        }
    </style>
</head>
<body>
<div class="toolbar">
    <button onclick="window.print()">🖨 In {{ $orders->count() }} phiếu</button>
    <a href="{{ route('admin.orders.index') }}">← Về danh sách đơn</a>
    @if ($skipped > 0)
    <span style="font-size:13px;color:#a00">Bỏ qua {{ $skipped }} đơn chưa có mã vận đơn.</span>
    @endif
</div>

<div class="sheet">
    @foreach ($orders as $order)
    @php
        // shipping_address lưu dạng "Tên | SĐT | Địa chỉ"
        $parts = array_map('trim', explode('|', (string) $order->shipping_address));
        $receiver = $parts[0] ?? '';
        $address = $parts[2] ?? ($order->shipping_address ?? '');
        $cod = $order->cod_amount;
    @endphp
    <div class="label">
        <div class="head">
            <span class="brand">🧸 ToyLand</span>
            <span style="font-size:12px">{{ $order->carrier === 'ghn' ? 'Giao Hàng Nhanh' : $carrierName }}</span>
        </div>
        <div class="code">{{ $order->ghn_order_code }}</div>
        <div class="bars" aria-hidden="true"></div>
        <div class="row"><b>Người nhận:</b> {{ $receiver }}</div>
        <div class="row"><b>Điện thoại:</b> {{ $order->customer_phone }}</div>
        <div class="row"><b>Địa chỉ:</b> {{ $address }}</div>
        <div class="items">
            {{ $order->items->map(fn ($i) => ($i->product->name ?? 'Sản phẩm') . ' x' . $i->quantity)->implode('; ') }}
        </div>
        <div class="cod">
            @if ($cod > 0)
                THU HỘ (COD): {{ number_format($cod, 0, ',', '.') }} đ
            @else
                ĐÃ THANH TOÁN - KHÔNG THU TIỀN
            @endif
        </div>
        <div class="foot">
            <span>Đơn #{{ $order->id }} · {{ $order->items->sum('quantity') }} món</span>
            <span>In lúc {{ now()->format('H:i d/m/Y') }}</span>
        </div>
    </div>
    @endforeach
</div>
</body>
</html>
