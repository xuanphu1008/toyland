@extends('layouts.user')

@section('title', 'Ví ToyLand')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h4 class="fw-bold text-secondary mb-0"><i class="bi bi-wallet2 me-2"></i>Ví ToyLand</h4>
    <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary rounded-pill px-4">← Đơn hàng của tôi</a>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
            <div class="text-muted small">Số dư hiện tại</div>
            <div class="display-6 fw-bold text-success">{{ number_format($balance, 0, ',', '.') }} đ</div>
            <p class="small text-muted mt-3 mb-0">
                Ví nhận tiền shop hoàn lại (đơn bị hủy, trả hàng, hoàn tiền thanh toán lỗi). Khi đặt đơn mới,
                nếu số dư đủ trả cả đơn, bạn chọn <strong>"Thanh toán bằng Ví ToyLand"</strong> ở bước thanh toán.
                Ví không rút ra tiền mặt.
            </p>
        </div>

        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h6 class="fw-bold text-secondary mb-3">Yêu cầu hoàn tiền / trả hàng</h6>
            @forelse ($requests as $r)
            <a href="{{ route('orders.show', $r->order_id) }}" class="text-decoration-none text-dark d-block border-bottom pb-2 mb-2">
                <div class="d-flex justify-content-between">
                    <span class="small fw-semibold">Đơn #{{ $r->order_id }}</span>
                    <span class="badge {{ $r->status_color }}">{{ $r->status_label }}</span>
                </div>
                <div class="small text-muted">{{ $r->type_label }} · {{ number_format($r->amount, 0, ',', '.') }} đ</div>
            </a>
            @empty
            <div class="text-muted small">Chưa có yêu cầu nào.</div>
            @endforelse
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h6 class="fw-bold text-secondary mb-3">Lịch sử biến động số dư</h6>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light"><tr class="small"><th>Thời gian</th><th>Nội dung</th><th class="text-end">Biến động</th><th class="text-end">Số dư sau</th></tr></thead>
                    <tbody>
                        @forelse ($transactions as $t)
                        <tr>
                            <td class="small text-muted">{{ $t->created_at->format('d/m/Y H:i') }}</td>
                            <td class="small">{{ $t->note ?: $t->type_label }}
                                @if ($t->order_id)<a href="{{ route('orders.show', $t->order_id) }}" class="ms-1">#{{ $t->order_id }}</a>@endif
                            </td>
                            <td class="text-end fw-semibold {{ $t->amount >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $t->amount >= 0 ? '+' : '' }}{{ number_format($t->amount, 0, ',', '.') }} đ
                            </td>
                            <td class="text-end small">{{ number_format($t->balance_after, 0, ',', '.') }} đ</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">Ví chưa có giao dịch nào.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $transactions->links() }}</div>
        </div>
    </div>
</div>
@endsection
