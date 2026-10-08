@extends('layouts.admin')

@section('title', 'Chi tiết Đơn hàng #'.$order->id)

@section('content')
@php
$paymentLabels = \App\Models\Order::PAYMENT_LABELS;
$shippingLabels = \App\Models\Order::SHIPPING_LABELS;
$txStatusColor = [
    'pending' => 'bg-warning text-dark', 'paid' => 'bg-success', 'failed' => 'bg-danger',
    'cancelled' => 'bg-secondary', 'refund_pending' => 'bg-warning text-dark', 'refunded' => 'bg-secondary',
];
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="text-secondary fw-bold mb-0">Đơn hàng #{{ $order->id }}</h5>
        <div class="small text-muted">Đặt lúc {{ \Carbon\Carbon::parse($order->created_at)->format('d/m/Y H:i') }}</div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="badge {{ $order->status_color }} fs-6 px-3 py-2">{{ $order->status_label }}</span>
        <a href="{{ route('admin.orders.invoice', $order->id) }}" target="_blank" rel="noopener"
           class="btn btn-outline-primary rounded-pill px-4 me-2">
            <i class="bi bi-printer me-1"></i> In hóa đơn
        </a>
        <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary rounded-pill px-4">
            <i class="bi bi-arrow-left"></i> Quay lại danh sách
        </a>
    </div>
</div>

{{-- ============ KHU VỰC XỬ LÝ ĐƠN HÀNG CỦA SHOP ============ --}}
@php
    $nextStepHint = [
        'awaiting_payment' => 'Đang giữ hàng chờ khách thanh toán MoMo' . ($order->payment_expires_at ? ' tới ' . $order->payment_expires_at->format('H:i d/m') : '') . '. Quá hạn hệ thống tự hủy và trả kho.',
        'pending' => 'Kiểm tra đơn rồi bấm "Xác nhận đơn".',
        'confirmed' => 'Chuẩn bị hàng, đóng gói xong bấm "Đóng gói & tạo vận đơn" để lấy mã vận đơn và gọi bên vận chuyển tới lấy.',
        'packed' => 'Đã có mã vận đơn, chờ bên vận chuyển tới lấy hàng (cập nhật ở màn hình Đơn vị vận chuyển).',
        'shipping' => 'Bên vận chuyển đang giao. Shop không cần thao tác, trạng thái do bên vận chuyển báo về.',
        'returning' => 'Hàng đang được hoàn về. Khi nhận lại đủ hàng, bấm "Đã nhận lại hàng hoàn" để cộng lại kho.',
        'completed' => 'Đơn đã hoàn tất.',
        'returned' => 'Hàng đã về kho.' . ($order->payment_status === 'refund_pending' ? ' Còn việc: hoàn tiền cho khách (trang Tài chính).' : ''),
        'cancelled' => 'Đơn đã hủy.',
    ][$order->status] ?? '';
    $btnClass = ['confirm' => 'btn-primary', 'pack' => 'btn-success', 'confirm_returned' => 'btn-dark'];
@endphp
<div class="card border-0 shadow-sm rounded-4 mb-4 border-start border-4 border-primary">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <div>
                <h6 class="fw-bold text-secondary mb-1">Xử lý đơn hàng</h6>
                <div class="small text-muted">{{ $nextStepHint }}</div>
            </div>
            @if ($carrierActions)
            <a href="{{ route('admin.carrier.index', ['q' => $order->ghn_order_code ?: $order->id]) }}" class="btn btn-sm btn-outline-warning">
                <i class="bi bi-truck me-1"></i> Mở ở màn hình Đơn vị vận chuyển
            </a>
            @endif
        </div>

        @if ($order->isCancelled())
        <div class="alert alert-danger border-0 rounded-3 mb-0">
            Đơn đã bị hủy lúc {{ optional($order->cancelled_at)->format('d/m/Y H:i') }}.
            @if ($order->cancel_reason) <br>Lý do: <strong>{{ $order->cancel_reason }}</strong> @endif
            @if ($order->restocked_at) <br><span class="small">Tồn kho đã được hoàn lại lúc {{ $order->restocked_at->format('d/m/Y H:i') }}.</span> @endif
        </div>
        @else
        <div class="row g-3">
            <div class="col-lg-7">
                @php $shopActions = collect($adminActions)->except('cancel'); @endphp
                @if ($shopActions->isNotEmpty())
                <form action="{{ route('admin.orders.status', $order->id) }}" method="POST">
                    @csrf @method('PATCH')
                    <label class="form-label small fw-semibold">Ghi chú nội bộ (tùy chọn)</label>
                    <input type="text" name="admin_note" maxlength="500" class="form-control mb-2" value="{{ $order->admin_note }}" placeholder="VD: Đã gọi xác nhận với khách">
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($shopActions as $action => $label)
                        <button type="submit" name="action" value="{{ $action }}" class="btn {{ $btnClass[$action] ?? 'btn-primary' }} rounded-pill px-4">
                            {{ $label }}
                        </button>
                        @endforeach
                    </div>
                </form>
                @else
                <div class="small text-muted">Không có thao tác nào của shop ở bước này.</div>
                @endif
            </div>

            <div class="col-lg-5 border-start-lg">
                @if (isset($adminActions['cancel']))
                <form action="{{ route('admin.orders.cancel', $order->id) }}" method="POST"
                      onsubmit="return confirm('Hủy đơn #{{ $order->id }} và hoàn lại toàn bộ tồn kho?')">
                    @csrf @method('PATCH')
                    <label class="form-label small fw-semibold text-danger">Hủy đơn hàng</label>
                    <div class="input-group">
                        <input type="text" name="cancel_reason" class="form-control" maxlength="255" required
                               placeholder="Lý do hủy (bắt buộc)">
                        <button type="submit" class="btn btn-danger">Hủy đơn</button>
                    </div>
                    <div class="form-text">
                        Trả hàng về kho{{ $order->ghn_order_code ? ', hủy vận đơn ' . $order->ghn_order_code : '' }}{{ $order->isPaid() ? ', chuyển tiền sang "Chờ hoàn tiền"' : '' }}.
                    </div>
                </form>
                @else
                <div class="alert alert-light border rounded-3 mb-0 small">
                    Bên vận chuyển đã nhận hàng nên không hủy được nữa. Nếu khách không nhận, bên vận chuyển sẽ chuyển hoàn về shop.
                </div>
                @endif
            </div>
        </div>
        @endif

        @if ($order->admin_note)
        <div class="alert alert-light border rounded-3 small mt-3 mb-0">
            <span class="fw-semibold">Ghi chú nội bộ:</span> {{ $order->admin_note }}
        </div>
        @endif
    </div>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body">
                <h6 class="fw-bold text-secondary mb-3">Thông tin khách hàng</h6>
                <div class="mb-2"><span class="text-muted small">Tên tài khoản:</span><br><span class="fw-semibold">{{ $order->user->name ?? 'Khách vãng lai' }}</span></div>
                <div class="mb-2"><span class="text-muted small">Email:</span><br>{{ $order->user->email ?? '--' }}</div>
                <div class="mb-2"><span class="text-muted small">Số điện thoại:</span><br>{{ $order->customer_phone ?? '--' }}</div>
                <div class="mb-0"><span class="text-muted small">Địa chỉ giao hàng:</span><br>{{ $order->shipping_address ?? '--' }}</div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body">
                <h6 class="fw-bold text-secondary mb-3">Vận chuyển</h6>
                <div class="mb-2">
                    <span class="text-muted small">Trạng thái:</span><br>
                    <span class="badge bg-info text-dark">{{ $shippingLabels[$order->shipping_status] ?? $order->shipping_status }}</span>
                </div>
                <div class="mb-2"><span class="text-muted small">Mã vận đơn:</span><br>
                    @if ($order->ghn_order_code)
                    <span class="fw-bold font-monospace">{{ $order->ghn_order_code }}</span>
                    <span class="small text-muted">· {{ $order->carrier === 'ghn' ? 'Giao Hàng Nhanh' : 'ToyLand Express (mô phỏng)' }}</span>
                    <a href="{{ route('admin.orders.labels', ['ids' => $order->id]) }}" target="_blank" class="small ms-1">In phiếu</a>
                    @else
                    Chưa tạo <span class="small text-muted">(tạo khi đóng gói)</span>
                    @endif
                </div>
                @if ($order->delivery_attempts > 0)
                <div class="mb-2"><span class="text-muted small">Số lần đi giao:</span> {{ $order->delivery_attempts }}
                    @if ($order->last_delivery_note)<div class="small text-muted">Ghi chú: {{ $order->last_delivery_note }}</div>@endif
                </div>
                @endif
                @if ($order->cod_amount > 0 && in_array($order->status, ['packed', 'shipping'], true))
                <div class="mb-2"><span class="text-muted small">Tiền thu hộ (COD):</span> <span class="fw-bold text-danger">{{ number_format($order->cod_amount, 0, ',', '.') }} đ</span></div>
                @endif
                <div class="mb-2"><span class="text-muted small">Phí vận chuyển:</span><br>{{ number_format($order->ghn_total_fee, 0, ',', '.') }} đ</div>
                <div class="mb-0 small text-muted">
                    Mã quận/huyện: {{ $order->to_district_id ?? '--' }} · Mã phường/xã: {{ $order->to_ward_code ?? '--' }}
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body">
                <h6 class="fw-bold text-secondary mb-3">Thanh toán</h6>
                <div class="mb-2"><span class="text-muted small">Hình thức:</span><br><span class="text-uppercase fw-semibold">{{ $order->payment_method }}</span></div>
                <div class="mb-0">
                    <span class="text-muted small">Trạng thái:</span><br>
                    <span class="badge {{ $txStatusColor[$order->payment_status] ?? 'bg-secondary' }}">
                        {{ $paymentLabels[$order->payment_status] ?? $order->payment_status }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body">
                <h6 class="fw-bold text-secondary mb-3">Sản phẩm trong đơn</h6>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Sản phẩm</th>
                                <th class="text-end">Đơn giá</th>
                                <th class="text-center">Số lượng</th>
                                <th class="text-end">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->items as $item)
                            <tr>
                                <td class="d-flex align-items-center gap-2">
                                    @if ($item->product && $item->product->image)
                                    <img src="{{ $item->product->image_url }}" width="40" height="40" class="rounded border" style="object-fit: cover;">
                                    @endif
                                    {{ $item->display_name ?? 'Sản phẩm đã bị xóa' }}
                                </td>
                                <td class="text-end">{{ number_format($item->price, 0, ',', '.') }} đ</td>
                                <td class="text-center">{{ $item->quantity }}</td>
                                <td class="text-end fw-semibold">{{ number_format($item->price * $item->quantity, 0, ',', '.') }} đ</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end fw-bold">Tổng cộng</td>
                                <td class="text-end fw-bold text-danger fs-5">{{ number_format($order->total_price, 0, ',', '.') }} đ</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body">
                <h6 class="fw-bold text-secondary mb-3">Lịch sử giao dịch thanh toán</h6>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Thời gian</th>
                                <th>Cổng</th>
                                <th>Hình thức</th>
                                <th class="text-end">Số tiền</th>
                                <th>Trạng thái</th>
                                <th>Mã GD</th>
                                <th>Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($order->paymentTransactions as $tx)
                            <tr>
                                <td class="text-nowrap">{{ $tx->created_at->format('d/m/Y H:i') }}</td>
                                <td class="text-uppercase">{{ $tx->gateway }}</td>
                                <td>{{ $tx->payment_option_label ?? '--' }}</td>
                                <td class="text-end">{{ number_format($tx->amount, 0, ',', '.') }} đ</td>
                                <td><span class="badge {{ $txStatusColor[$tx->status] ?? 'bg-secondary' }}">{{ $paymentLabels[$tx->status] ?? $tx->status }}</span></td>
                                <td>{{ $tx->transaction_id ?? '--' }}</td>
                                <td class="small text-muted">{{ $tx->message ?? '--' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-muted text-center py-3">Chưa có giao dịch thanh toán nào.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ============ NHẬT KÝ TRẠNG THÁI ĐƠN HÀNG ============ --}}
        <div class="card border-0 shadow-sm rounded-4 mt-4">
            <div class="card-body">
                <h6 class="fw-bold text-secondary mb-3">Nhật ký trạng thái</h6>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Thời gian</th>
                                <th>Từ</th>
                                <th>Sang</th>
                                <th>Người thực hiện</th>
                                <th>Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-nowrap">{{ \Carbon\Carbon::parse($order->created_at)->format('d/m/Y H:i') }}</td>
                                <td class="text-muted">--</td>
                                <td><span class="badge bg-secondary">Khách đặt hàng</span></td>
                                <td>{{ $order->user->name ?? 'Khách' }}</td>
                                <td class="small text-muted">Đơn hàng được tạo</td>
                            </tr>
                            @foreach ($statusLogs as $log)
                            <tr>
                                <td class="text-nowrap">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                                <td class="text-muted small">{{ $log->from_status_label ?? '--' }}</td>
                                <td><span class="badge {{ \App\Models\Order::STATUS_COLORS[$log->to_status] ?? 'bg-secondary' }}">{{ $log->to_status_label }}</span></td>
                                <td>{{ $log->changedBy->name ?? 'Hệ thống' }}</td>
                                <td class="small text-muted">{{ $log->note ?? '--' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
