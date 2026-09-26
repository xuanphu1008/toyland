@extends('layouts.user')

@section('title', 'Theo dõi đơn hàng #' . $order->id)

@section('content')
<style>
    .timeline { list-style: none; padding-left: 0; margin: 0; }
    .timeline li { position: relative; padding-left: 34px; padding-bottom: 22px; }
    .timeline li:last-child { padding-bottom: 0; }
    .timeline li::before {
        content: ''; position: absolute; left: 9px; top: 20px; bottom: 0; width: 2px; background: var(--tl-border-strong, #F4C7BB);
    }
    .timeline li:last-child::before { display: none; }
    .timeline .dot {
        position: absolute; left: 0; top: 2px; width: 20px; height: 20px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center; font-size: 11px; color: #fff;
    }
    .timeline .dot.done { background: var(--tl-success, #357A22); }
    .timeline .dot.current { background: var(--tl-primary-700, #E03131); }
    .timeline .dot.todo { background: var(--tl-border-strong, #F4C7BB); }
    .timeline .dot.cancel { background: var(--tl-danger, #D32B2B); }
</style>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold text-secondary mb-0">Đơn hàng #{{ $order->id }}</h4>
        <div class="text-muted small">Đặt lúc {{ $order->created_at->format('d/m/Y H:i') }}</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        {{-- Mở tab mới để khách không mất trang đơn hàng đang xem --}}
        <a href="{{ route('orders.invoice', $order->id) }}" target="_blank" rel="noopener"
           class="btn btn-outline-primary rounded-pill px-4">
            <i class="bi bi-printer me-1"></i> In hóa đơn
        </a>
        <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary rounded-pill px-4">← Về lịch sử đơn</a>
    </div>
</div>

<div class="row g-4">
    {{-- ===== Tiến trình đơn hàng ===== --}}
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
            <h6 class="fw-bold text-secondary mb-4">Tiến trình đơn hàng</h6>

            @php
            // Các mốc chuẩn của 1 đơn hàng; nếu đơn bị hủy thì hiển thị riêng
            $flow = ['pending', 'confirmed', 'packed', 'shipping', 'completed'];
            $currentIndex = array_search($order->status, $flow, true);
            @endphp

            @if ($order->isCancelled())
            <ul class="timeline">
                <li>
                    <span class="dot done">✓</span>
                    <div class="fw-semibold">Đã đặt hàng</div>
                    <div class="text-muted small">{{ $order->created_at->format('d/m/Y H:i') }}</div>
                </li>
                <li>
                    <span class="dot cancel">✕</span>
                    <div class="fw-semibold text-danger">Đơn hàng đã hủy</div>
                    <div class="text-muted small">{{ optional($order->cancelled_at)->format('d/m/Y H:i') }}</div>
                    @if ($order->cancel_reason)
                    <div class="small mt-1">Lý do: {{ $order->cancel_reason }}</div>
                    @endif
                </li>
            </ul>
            @else
            <ul class="timeline">
                @foreach ($flow as $index => $step)
                @php
                $state = $currentIndex === false ? 'todo' : ($index < $currentIndex ? 'done' : ($index === $currentIndex ? 'current' : 'todo'));
                $log = $statusLogs->firstWhere('to_status', $step);
                @endphp
                <li>
                    <span class="dot {{ $state }}">{{ $state === 'todo' ? '' : ($state === 'current' ? '●' : '✓') }}</span>
                    <div class="fw-semibold {{ $state === 'todo' ? 'text-muted' : '' }}">
                        {{ \App\Models\Order::STATUSES[$step] }}
                    </div>
                    @if ($step === 'pending')
                    <div class="text-muted small">{{ $order->created_at->format('d/m/Y H:i') }}</div>
                    @elseif ($log)
                    <div class="text-muted small">{{ $log->created_at->format('d/m/Y H:i') }}</div>
                    @endif
                </li>
                @endforeach
            </ul>
            @endif

            @if ($order->ghn_order_code)
            <div class="alert alert-info border-0 rounded-3 small mt-3 mb-0">
                Mã vận đơn GHN: <span class="fw-bold">{{ $order->ghn_order_code }}</span>
            </div>
            @endif
        </div>
    </div>

    {{-- ===== Thông tin & sản phẩm ===== --}}
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
            <h6 class="fw-bold text-secondary mb-3">Thông tin nhận hàng</h6>
            <div class="row g-3 small">
                <div class="col-md-6">
                    <div class="text-muted">Người nhận & địa chỉ</div>
                    <div class="fw-semibold">{{ $order->shipping_address ?: 'Chưa có' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted">Điện thoại</div>
                    <div class="fw-semibold">{{ $order->customer_phone ?: '—' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted">Thanh toán</div>
                    <div class="fw-semibold">
                        {{ $order->payment_method === 'momo' ? 'MoMo' : 'COD' }}
                        <span class="badge {{ $order->isPaid() ? 'bg-success' : 'bg-warning text-dark' }}">
                            {{ $order->isPaid() ? 'Đã thanh toán' : 'Chưa thanh toán' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h6 class="fw-bold text-secondary mb-3">Sản phẩm trong đơn</h6>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr class="small">
                            <th>Sản phẩm</th>
                            <th class="text-end">Đơn giá</th>
                            <th class="text-center">SL</th>
                            <th class="text-end">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->items as $item)
                        <tr>
                            <td>
                                @if ($item->product)
                                <a href="{{ route('products.show', $item->product_id) }}" class="text-decoration-none text-dark fw-semibold">
                                    {{ $item->product->name }}
                                </a>
                                @else
                                <span class="text-muted">Sản phẩm đã bị xóa</span>
                                @endif
                            </td>
                            <td class="text-end">{{ number_format($item->price, 0, ',', '.') }} đ</td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-end fw-semibold">{{ number_format($item->price * $item->quantity, 0, ',', '.') }} đ</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        @if ($order->discount_amount > 0)
                        <tr>
                            <td colspan="3" class="text-end text-success">Giảm giá (hạng thành viên / mã giảm giá)</td>
                            <td class="text-end text-success">-{{ number_format($order->discount_amount, 0, ',', '.') }} đ</td>
                        </tr>
                        @endif
                        @if ($order->ghn_total_fee)
                        <tr>
                            <td colspan="3" class="text-end text-muted">Phí vận chuyển</td>
                            <td class="text-end">{{ number_format($order->ghn_total_fee, 0, ',', '.') }} đ</td>
                        </tr>
                        @endif
                        <tr>
                            <td colspan="3" class="text-end fw-bold">Tổng cộng</td>
                            <td class="text-end fw-bold text-danger fs-5">{{ number_format($order->total_price, 0, ',', '.') }} đ</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if ($order->points_earned > 0)
            <div class="alert alert-warning border-0 rounded-3 small mt-3 mb-0">
                🎁 Đơn hàng này đã cộng <strong>{{ $order->points_earned }} điểm</strong> vào tài khoản của bạn.
            </div>
            @endif

            @if ($order->isCompleted())
            <div class="alert alert-success border-0 rounded-3 small mt-3 mb-0 d-flex justify-content-between align-items-center">
                <span>Đơn đã giao thành công — bạn có thể đánh giá các sản phẩm trong đơn.</span>
                <a href="{{ route('reviews.index') }}" class="btn btn-sm btn-success rounded-pill px-3">Viết đánh giá</a>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
