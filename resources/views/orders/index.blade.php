@extends('layouts.user')

@section('title', 'Lịch sử đơn hàng')

@section('content')
<h4 class="fw-bold text-secondary mb-4">Lịch sử đơn hàng</h4>

@if ($orders->count())
@foreach ($orders as $order)
@php
$isMomo = $order->payment_method === 'momo';
$isPaid = $order->payment_status === 'paid';
$lastTransaction = $order->latestPaymentTransaction;

// Hình thức MoMo đã dùng ở lần giao dịch gần nhất, dùng để đánh dấu mặc định trong menu
// "Thanh toán lại" và để hiển thị đơn đã thanh toán bằng hình thức nào.
$lastOption = $isMomo ? ($lastTransaction->payment_option ?? null) : null;
$lastOptionLabel = $isMomo ? ($lastTransaction->payment_option_label ?? null) : null;

// Chỉ cho thanh toán lại với đơn MoMo chưa thu được tiền và đơn vẫn còn hiệu lực
// (đơn đang được giữ hàng, hoặc đơn cũ tạo trước khi có bước giữ hàng)
$canRetryMomo = $isMomo && !$isPaid && ($order->isAwaitingPayment() || $order->status === 'pending');

// Badge trạng thái thanh toán: ưu tiên báo "đã thanh toán", sau đó mới tới thất bại/chờ
if ($isPaid) {
$paymentInfo = [($isMomo ? 'Đã thanh toán qua MoMo' : 'Đã thanh toán'), 'bg-success'];
} elseif ($isMomo) {
$paymentInfo = $order->payment_status === 'failed'
? ['MoMo - Thanh toán thất bại', 'bg-danger']
: ['MoMo - Chờ thanh toán', 'bg-warning text-dark'];
} else {
$paymentInfo = ['COD - Thanh toán khi nhận hàng', 'bg-secondary'];
}
@endphp
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white rounded-top-4 d-flex flex-wrap justify-content-between align-items-center py-3">
        <div>
            <span class="fw-bold">Đơn hàng #{{ $order->id }}</span>
            <span class="text-muted small ms-2">{{ $order->created_at->format('d/m/Y H:i') }}</span>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="badge {{ $paymentInfo[1] }}">{{ $paymentInfo[0] }}</span>
            <span class="badge {{ $order->status_color }}">{{ $order->status_label }}</span>
            <span class="fw-bold text-danger">{{ number_format($order->total_price, 0, ',', '.') }} đ</span>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead>
                    <tr class="text-muted small">
                        <th>Sản phẩm</th>
                        <th>Đơn giá</th>
                        <th>Số lượng</th>
                        <th>Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $item)
                    <tr>
                        <td>{{ $item->product->name ?? 'Sản phẩm đã bị xóa' }}</td>
                        <td>{{ number_format($item->price, 0, ',', '.') }} đ</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ number_format($item->price * $item->quantity, 0, ',', '.') }} đ</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Khu vực thanh toán MoMo: nút thử lại khi chưa thu được tiền, hoặc mã giao dịch khi đã xong --}}
        @if ($canRetryMomo)
        <div class="alert alert-warning border-0 rounded-3 d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3 mb-0">
            <div>
                <div class="fw-semibold">Đơn hàng này chưa được thanh toán.</div>
                <div class="small text-muted">
                    @if ($lastTransaction && $lastTransaction->status === 'failed' && $lastTransaction->message)
                    Lý do lần trước: {{ $lastTransaction->message }}
                    @else
                    Giao dịch MoMo chưa hoàn tất.
                    @endif
                    @if ($order->payment_expires_at)
                    <span class="d-block text-danger fw-semibold">
                        Shop giữ hàng cho bạn tới {{ $order->payment_expires_at->format('H:i d/m') }} — quá hạn đơn tự hủy.
                    </span>
                    @endif
                    @if ($lastOptionLabel)
                    <span class="d-block">Hình thức lần trước: {{ $lastOptionLabel }}</span>
                    @endif
                </div>
            </div>
            {{-- Khách chọn lại hình thức thanh toán MoMo cho lần thử này (ví/QR, ATM, thẻ quốc tế...).
                 Form GET nên giá trị chọn được gửi lên dưới dạng ?option=... , không cần JS.
                 Lần trước dùng hình thức nào thì mặc định chọn sẵn hình thức đó. --}}
            <form method="GET" action="{{ route('orders.momo.pay', $order->id) }}"
                class="d-flex flex-wrap align-items-center gap-2">
                <label class="visually-hidden" for="momo-option-{{ $order->id }}">Hình thức thanh toán MoMo</label>
                <select name="option" id="momo-option-{{ $order->id }}" class="form-select form-select-sm rounded-pill w-auto">
                    @foreach ($momoOptions as $optionKey => $option)
                    <option value="{{ $optionKey }}" {{ $lastOption === $optionKey ? 'selected' : '' }}>
                        {{ $option['label'] }}
                    </option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-danger rounded-pill px-4 text-nowrap">
                    Thanh toán lại bằng MoMo
                </button>
            </form>
        </div>
        @elseif ($isPaid && $lastTransaction && $lastTransaction->transaction_id)
        <div class="small text-muted mt-3">
            Mã giao dịch {{ $isMomo ? 'MoMo' : '' }}: <span class="fw-semibold">{{ $lastTransaction->transaction_id }}</span>
            @if ($lastOptionLabel)
            - Hình thức: {{ $lastOptionLabel }}
            @endif
            @if ($lastTransaction->paid_at)
            - Thanh toán lúc {{ $lastTransaction->paid_at->format('d/m/Y H:i') }}
            @endif
        </div>
        @endif

        @if ($order->isCancelled() && $order->cancel_reason)
        <div class="alert alert-danger border-0 rounded-3 small mt-3 mb-0">
            Đơn đã hủy lúc {{ optional($order->cancelled_at)->format('d/m/Y H:i') }} — Lý do: {{ $order->cancel_reason }}
        </div>
        @endif

        {{-- Hành động: xem tiến trình giao hàng & tự hủy đơn khi còn được phép --}}
        <div class="d-flex flex-wrap gap-2 mt-3 pt-3 border-top">
            <a href="{{ route('orders.show', $order->id) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                Theo dõi đơn hàng
            </a>

            @if ($order->canBeCancelledByCustomer())
            <button class="btn btn-sm btn-outline-danger rounded-pill px-3" data-bs-toggle="collapse" data-bs-target="#cancel-{{ $order->id }}">
                Hủy đơn hàng
            </button>
            @endif

            @if ($order->isCompleted())
            <a href="{{ route('reviews.index') }}" class="btn btn-sm btn-outline-warning rounded-pill px-3">
                Đánh giá sản phẩm
            </a>
            @endif
        </div>

        @if ($order->canBeCancelledByCustomer())
        <div class="collapse mt-2" id="cancel-{{ $order->id }}">
            <form action="{{ route('orders.cancel', $order->id) }}" method="POST" class="bg-light rounded-3 p-3"
                  onsubmit="return confirm('Bạn chắc chắn muốn hủy đơn hàng #{{ $order->id }}?')">
                @csrf @method('PATCH')
                <label class="form-label small fw-semibold">Lý do hủy đơn <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="text" name="cancel_reason" class="form-control" maxlength="255" required
                           placeholder="VD: Đặt nhầm sản phẩm, muốn đổi sang mẫu khác...">
                    <button type="submit" class="btn btn-danger">Xác nhận hủy</button>
                </div>
                <div class="form-text">Sản phẩm sẽ được trả lại kho ngay sau khi hủy.</div>
            </form>
        </div>
        @endif
    </div>
</div>
@endforeach

<div class="d-flex justify-content-center mt-4">
    {{ $orders->links() }}
</div>
@else
<div class="tl-empty ">
    Bạn chưa có đơn hàng nào.
    <div class="mt-3">
        <a href="{{ route('home') }}" class="btn btn-outline-primary rounded-pill px-4">Mua sắm ngay</a>
    </div>
</div>
@endif
@endsection
