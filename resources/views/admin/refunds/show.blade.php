@extends('layouts.admin')

@section('title', 'Yêu cầu #' . $r->id)

@section('content')
@php $o = $r->order; $money = fn ($v) => number_format($v, 0, ',', '.') . ' đ'; @endphp
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4 class="fw-bold mb-0">{{ $r->type_label }} · đơn #{{ $r->order_id }} <span class="badge {{ $r->status_color }}">{{ $r->status_label }}</span></h4>
    <a href="{{ route('admin.refunds.index', ['status' => 'open']) }}" class="btn btn-outline-secondary btn-sm">← Danh sách</a>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm p-4 mb-4">
            <h6 class="fw-bold mb-3">Thông tin yêu cầu</h6>
            <dl class="row small mb-0">
                <dt class="col-sm-4">Khách</dt><dd class="col-sm-8">{{ optional($r->user)->name }} · {{ optional($r->user)->email }}</dd>
                <dt class="col-sm-4">Đơn hàng</dt><dd class="col-sm-8"><a href="{{ route('admin.orders.show', $o->id) }}">#{{ $o->id }}</a> · {{ $o->status_label }} · tổng {{ $money($o->total_price) }} · {{ $o->payment_method === 'momo' ? 'MoMo' : ($o->payment_method === 'wallet' ? 'Ví ToyLand' : 'COD') }} ({{ \App\Models\Order::PAYMENT_LABELS[$o->payment_status] ?? $o->payment_status }})</dd>
                <dt class="col-sm-4">Số tiền hoàn</dt><dd class="col-sm-8 fw-bold text-danger">{{ $money($r->amount) }} <span class="text-muted fw-normal">→ Ví ToyLand của khách</span></dd>
                @if ($r->momo_trans_id)<dt class="col-sm-4">Mã giao dịch MoMo khách báo</dt><dd class="col-sm-8 font-monospace">{{ $r->momo_trans_id }}</dd>@endif
                @if ($r->reason_label)<dt class="col-sm-4">Lý do trả hàng</dt><dd class="col-sm-8">{{ $r->reason_label }}</dd>@endif
                @if ($r->reason)<dt class="col-sm-4">Mô tả / lý do</dt><dd class="col-sm-8">{{ $r->reason }}</dd>@endif
                @if ($r->isReturn())
                    @if ($r->ship_back_deadline)<dt class="col-sm-4">Hạn khách gửi hàng</dt><dd class="col-sm-8">{{ $r->ship_back_deadline->format('d/m/Y H:i') }}</dd>@endif
                    @if ($r->return_tracking_code)<dt class="col-sm-4">Kiện hàng trả</dt><dd class="col-sm-8">{{ $r->return_carrier }} · <span class="font-monospace">{{ $r->return_tracking_code }}</span></dd>@endif
                @endif
                @if ($r->admin_note)<dt class="col-sm-4">Ghi chú của shop</dt><dd class="col-sm-8">{{ $r->admin_note }}</dd>@endif
            </dl>
        </div>

        <div class="card border-0 shadow-sm p-4">
            <h6 class="fw-bold mb-3">Sản phẩm trong đơn</h6>
            <ul class="small mb-0">
                @foreach ($o->items as $it)<li>{{ $it->display_name }} × {{ $it->quantity }} — {{ $money($it->price * $it->quantity) }}</li>@endforeach
            </ul>
            <div class="small text-muted mt-2">Phí vận chuyển chiều đi: {{ $money($o->ghn_total_fee ?? 0) }}</div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm p-4 mb-4">
            <h6 class="fw-bold mb-3">Xử lý</h6>

            @if ($r->type === 'return_goods')
                @if ($r->status === 'requested')
                <p class="small text-muted">Xem lý do và mô tả của khách. Duyệt thì khách có {{ config('toyland.refunds.ship_back_days') }} ngày gửi hàng về <em>{{ config('toyland.refunds.return_address') }}</em>. Chưa hoàn tiền ở bước này.</p>
                <form method="POST" action="{{ route('admin.refunds.approve-return', $r->id) }}" class="mb-3">@csrf
                    <input name="note" class="form-control form-control-sm mb-2" placeholder="Ghi chú cho khách (không bắt buộc)">
                    <button class="btn btn-success btn-sm w-100">Duyệt cho trả hàng</button>
                </form>
                @elseif (in_array($r->status, ['approved', 'in_transit'], true))
                <p class="small text-muted">{{ $r->status === 'approved' ? 'Chờ khách gửi hàng về.' : 'Hàng đang trên đường về.' }} Khi kiện hàng đến kho, bấm "Đã nhận hàng" để bắt đầu kiểm tra.</p>
                <form method="POST" action="{{ route('admin.refunds.receive', $r->id) }}" class="mb-3">@csrf
                    <button class="btn btn-primary btn-sm w-100">Đã nhận được hàng trả</button>
                </form>
                @elseif ($r->status === 'received')
                <p class="small text-muted">Kiểm tra hàng. <strong>Đạt</strong>: hoàn {{ $money($r->amount) }} vào ví khách, đơn chuyển "Đã hoàn hàng", thu hồi điểm thưởng. <strong>Không đạt</strong>: không hoàn tiền, ghi rõ lý do.</p>
                <form method="POST" action="{{ route('admin.refunds.inspect', $r->id) }}">@csrf
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="result" value="accept" id="ra" checked>
                        <label class="form-check-label" for="ra">Hàng đạt - hoàn tiền</label>
                    </div>
                    <div class="form-check ms-4 mb-2">
                        <input type="hidden" name="restock" value="0">
                        <input class="form-check-input" type="checkbox" name="restock" value="1" id="rs" @checked(\App\Models\RefundRequest::RETURN_REASONS[$r->reason_code][2] ?? false)>
                        <label class="form-check-label small" for="rs">Hàng còn bán được - nhập lại kho</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="result" value="reject" id="rr">
                        <label class="form-check-label" for="rr">Hàng không đạt - từ chối hoàn tiền</label>
                    </div>
                    <textarea name="note" class="form-control form-control-sm mb-2" rows="2" placeholder="Ghi chú / lý do (bắt buộc nếu không đạt)"></textarea>
                    <button class="btn btn-danger btn-sm w-100" onclick="return confirm('Chốt kết quả kiểm hàng? Không hoàn tác được.')">Chốt kết quả</button>
                </form>
                @endif

                @if ($r->status === 'requested')
                <hr>
                <form method="POST" action="{{ route('admin.refunds.reject', $r->id) }}">@csrf
                    <textarea name="note" class="form-control form-control-sm mb-2" rows="2" placeholder="Lý do từ chối (bắt buộc)" required></textarea>
                    <button class="btn btn-outline-danger btn-sm w-100">Từ chối yêu cầu</button>
                </form>
                @endif

            @elseif ($r->status === 'requested')
                @if ($r->type === 'payment_not_received')
                <p class="small text-muted">Khách báo đã bị trừ tiền MoMo. Đối chiếu mã giao dịch <strong class="font-monospace">{{ $r->momo_trans_id }}</strong> trong cổng quản trị MoMo.
                    @if ($o->status === 'awaiting_payment') Đơn <strong>đang giữ hàng</strong>: nếu tiền đã về shop, ghi nhận thanh toán để đơn đi tiếp. @else Đơn <strong>đã hủy</strong>: nếu tiền đã về shop, hoàn vào ví khách. @endif
                    Nếu MoMo không có giao dịch, từ chối kèm lý do.</p>
                @if ($o->status === 'awaiting_payment')
                <form method="POST" action="{{ route('admin.refunds.confirm-payment', $r->id) }}" class="mb-3">@csrf
                    <button class="btn btn-success btn-sm w-100" onclick="return confirm('Xác nhận shop đã nhận tiền MoMo cho đơn này?')">Ghi nhận đã nhận tiền → đơn đi tiếp</button>
                </form>
                @endif
                @else
                <p class="small text-muted">Tiền đã thu của khách cho đơn không thành. Hoàn vào ví ngay, khách dùng cho đơn sau.</p>
                @endif

                @if ($r->type !== 'payment_not_received' || $o->status === 'cancelled')
                <form method="POST" action="{{ route('admin.refunds.payout', $r->id) }}" class="mb-3">@csrf
                    <input name="note" class="form-control form-control-sm mb-2" placeholder="Ghi chú (không bắt buộc)">
                    <button class="btn btn-success btn-sm w-100" onclick="return confirm('Hoàn {{ $money($r->amount) }} vào ví khách?')">Hoàn {{ $money($r->amount) }} vào ví khách</button>
                </form>
                @endif

                @if ($r->source === 'customer')
                <hr>
                <form method="POST" action="{{ route('admin.refunds.reject', $r->id) }}">@csrf
                    <textarea name="note" class="form-control form-control-sm mb-2" rows="2" placeholder="Lý do từ chối (bắt buộc)" required></textarea>
                    <button class="btn btn-outline-danger btn-sm w-100">Từ chối</button>
                </form>
                @endif
            @else
                <div class="text-muted small">Yêu cầu đã khép lại ({{ $r->status_label }}){{ $r->handled_at ? ' lúc ' . $r->handled_at->format('d/m/Y H:i') : '' }}.</div>
            @endif
        </div>

        <div class="card border-0 shadow-sm p-4">
            <h6 class="fw-bold mb-3">Nhật ký đơn</h6>
            @foreach ($logs as $l)<div class="small border-bottom py-1"><span class="text-muted">{{ $l->created_at->format('d/m H:i') }}</span> {{ $l->note }}</div>@endforeach
        </div>
    </div>
</div>
@endsection
