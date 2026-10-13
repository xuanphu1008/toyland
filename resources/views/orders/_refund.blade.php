{{-- Khối hoàn tiền / trả hàng trong chi tiết đơn của khách --}}
@php
    $refunds = app(\App\Services\RefundService::class);
    $myRequests = \App\Models\RefundRequest::where('order_id', $order->id)->orderByDesc('id')->get();
    [$canReturn, $whyNot] = $refunds->canRequestReturn($order);
    $canReport = $order->payment_method === 'momo'
        && in_array($order->status, ['awaiting_payment', 'cancelled'], true)
        && ! $order->customerHasPaid()
        && ! in_array($order->payment_status, ['refund_pending', 'refunded'], true)
        && ! $myRequests->where('type', 'payment_not_received')->whereIn('status', \App\Models\RefundRequest::OPEN)->count();
    $reasons = \App\Models\RefundRequest::RETURN_REASONS;
@endphp

@foreach ($myRequests as $r)
<div class="card border-0 shadow-sm rounded-4 p-4 mt-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <h6 class="fw-bold text-secondary mb-0">{{ $r->type_label }} <span class="text-muted fw-normal">· {{ number_format($r->amount, 0, ',', '.') }} đ</span></h6>
        <span class="badge {{ $r->status_color }}">{{ $r->status_label }}</span>
    </div>
    <div class="small text-muted mt-1">Gửi lúc {{ $r->created_at->format('d/m/Y H:i') }}@if ($r->reason_label) · Lý do: {{ $r->reason_label }}@endif</div>
    @if ($r->reason)<div class="small mt-1">{{ $r->reason }}</div>@endif

    @if ($r->isReturn())
    {{-- Các bước trả hàng --}}
    @php
        $steps = ['requested' => 'Gửi yêu cầu', 'approved' => 'Shop duyệt', 'in_transit' => 'Gửi hàng về', 'received' => 'Shop kiểm hàng', 'refunded' => 'Hoàn tiền vào ví'];
        $order_ = array_keys($steps);
        $idx = array_search($r->status, $order_, true);
    @endphp
    @if ($idx !== false)
    <div class="d-flex flex-wrap gap-2 my-3">
        @foreach ($steps as $k => $label)
        @php $i = array_search($k, $order_, true); @endphp
        <span class="badge rounded-pill {{ $i < $idx ? 'bg-success' : ($i === $idx ? 'bg-primary' : 'bg-light text-muted border') }}">{{ $i + 1 }}. {{ $label }}</span>
        @endforeach
    </div>
    @endif

    @if ($r->status === 'requested')
    <div class="alert alert-warning border-0 small mb-0">Đang chờ shop duyệt. <strong>Chưa gửi hàng</strong> khi shop chưa duyệt.</div>
    @elseif ($r->status === 'approved')
    <div class="alert alert-info border-0 small">
        Shop đã duyệt. Hãy đóng gói đủ sản phẩm, phụ kiện và gửi về:<br>
        <strong>{{ config('toyland.refunds.return_address') }}</strong><br>
        Hạn gửi: <strong>{{ optional($r->ship_back_deadline)->format('d/m/Y') }}</strong> (quá hạn yêu cầu tự hủy).
        @if ($r->reason_code === 'change_of_mind') <br>Do đổi ý, bạn tự chịu phí gửi hàng về và phí vận chuyển chiều đi không được hoàn. @else <br>Lỗi từ shop: bạn được hoàn đủ tiền; giữ biên lai cước gửi hàng để đối chiếu. @endif
        @if ($r->admin_note)<br>Ghi chú của shop: {{ $r->admin_note }}@endif
    </div>
    <form method="POST" action="{{ route('refunds.ship', $r->id) }}" class="row g-2">
        @csrf @method('PATCH')
        <div class="col-md-4"><input name="carrier" class="form-control form-control-sm" placeholder="Đơn vị vận chuyển (GHN, VNPost...)" required maxlength="60"></div>
        <div class="col-md-5"><input name="tracking" class="form-control form-control-sm" placeholder="Mã vận đơn của kiện hàng trả" required maxlength="60"></div>
        <div class="col-md-3"><button class="btn btn-sm btn-primary rounded-pill w-100">Tôi đã gửi hàng</button></div>
    </form>
    @elseif ($r->status === 'in_transit')
    <div class="alert alert-primary border-0 small mb-0">Bạn đã gửi hàng ({{ $r->return_carrier }} - <span class="font-monospace">{{ $r->return_tracking_code }}</span>). Shop sẽ kiểm tra khi nhận được.</div>
    @elseif ($r->status === 'received')
    <div class="alert alert-primary border-0 small mb-0">Shop đã nhận hàng và đang kiểm tra. Tiền hoàn vào ví ngay sau khi kiểm xong.</div>
    @elseif ($r->status === 'refunded')
    <div class="alert alert-success border-0 small mb-0">Đã hoàn {{ number_format($r->amount, 0, ',', '.') }} đ vào <a href="{{ route('wallet.index') }}">Ví ToyLand</a>.</div>
    @endif
    @elseif ($r->status === 'refunded')
    <div class="alert alert-success border-0 small mt-3 mb-0">Đã hoàn {{ number_format($r->amount, 0, ',', '.') }} đ vào <a href="{{ route('wallet.index') }}">Ví ToyLand</a>. Bạn dùng số dư này để thanh toán đơn sau.</div>
    @elseif ($r->status === 'requested')
    <div class="alert alert-warning border-0 small mt-3 mb-0">Shop đang xử lý. {{ $r->type === 'payment_not_received' ? 'Shop sẽ đối chiếu mã giao dịch ' . $r->momo_trans_id . ' trên MoMo.' : 'Tiền sẽ được hoàn vào Ví ToyLand.' }}</div>
    @endif

    @if ($r->status === 'rejected')
    <div class="alert alert-danger border-0 small mt-3 mb-0">Không được chấp nhận. Lý do: {{ $r->admin_note }}</div>
    @endif

    @if ($r->source === 'customer' && (($r->isReturn() && in_array($r->status, ['requested', 'approved'], true)) || (! $r->isReturn() && $r->status === 'requested')))
    <form method="POST" action="{{ route('refunds.cancel', $r->id) }}" class="mt-2" onsubmit="return confirm('Hủy yêu cầu này?')">
        @csrf @method('DELETE')
        <button class="btn btn-sm btn-outline-secondary rounded-pill">Hủy yêu cầu</button>
    </form>
    @endif
</div>
@endforeach

@if ($canReport)
<div class="card border-0 shadow-sm rounded-4 p-4 mt-4">
    <h6 class="fw-bold text-secondary">Đã bị trừ tiền MoMo nhưng đơn chưa ghi nhận?</h6>
    <p class="small text-muted">Nhập mã giao dịch MoMo (trong lịch sử giao dịch của ví MoMo). Shop sẽ đối chiếu: nếu tiền đã về shop, đơn tiếp tục xử lý; nếu đơn đã hủy, tiền được hoàn vào Ví ToyLand.</p>
    <form method="POST" action="{{ route('refunds.report', $order->id) }}" class="row g-2">
        @csrf
        <div class="col-md-4"><input name="momo_trans_id" class="form-control form-control-sm" placeholder="Mã giao dịch MoMo" required maxlength="60"></div>
        <div class="col-md-5"><input name="note" class="form-control form-control-sm" placeholder="Ghi chú (không bắt buộc)" maxlength="500"></div>
        <div class="col-md-3"><button class="btn btn-sm btn-outline-danger rounded-pill w-100">Báo bị trừ tiền</button></div>
    </form>
</div>
@endif

@if ($order->status === 'completed')
<div class="card border-0 shadow-sm rounded-4 p-4 mt-4">
    <h6 class="fw-bold text-secondary">Trả hàng</h6>
    @if ($canReturn)
    <p class="small text-muted">
        Trả cả đơn trong {{ config('toyland.refunds.return_window_days') }} ngày kể từ lúc nhận hàng. Hàng cần còn nguyên, đủ phụ kiện.
        Lỗi từ shop (hỏng, giao sai, thiếu, không giống mô tả): hoàn 100%, không mất phí. Đổi ý: bạn chịu phí gửi trả và không được hoàn phí vận chuyển chiều đi.
        Tiền hoàn vào Ví ToyLand sau khi shop nhận và kiểm tra hàng.
    </p>
    <form method="POST" action="{{ route('refunds.return', $order->id) }}" class="row g-2">
        @csrf
        <div class="col-md-4">
            <select name="reason_code" class="form-select form-select-sm" required>
                <option value="">— Chọn lý do —</option>
                @foreach ($reasons as $code => $info)<option value="{{ $code }}">{{ $info[0] }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-6"><input name="description" class="form-control form-control-sm" placeholder="Mô tả tình trạng hàng (ít nhất 10 ký tự)" required minlength="10" maxlength="1000"></div>
        <div class="col-md-2"><button class="btn btn-sm btn-primary rounded-pill w-100">Gửi yêu cầu</button></div>
    </form>
    @else
    <div class="small text-muted">{{ $whyNot }}</div>
    @endif
</div>
@endif
