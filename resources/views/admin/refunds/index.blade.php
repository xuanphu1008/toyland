@extends('layouts.admin')

@section('title', 'Hoàn tiền & trả hàng')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4 class="fw-bold mb-0"><i class="bi bi-arrow-return-left me-2"></i>Hoàn tiền & trả hàng
        @if ($openCount)<span class="badge bg-danger">{{ $openCount }} đang xử lý</span>@endif</h4>
</div>

<form method="GET" class="row g-2 mb-3">
    <div class="col-md-3">
        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">Mọi trạng thái</option>
            <option value="open" @selected(request('status') === 'open')>Đang xử lý</option>
            @foreach (\App\Models\RefundRequest::STATUSES as $k => $v)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-3">
        <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">Mọi loại</option>
            @foreach (\App\Models\RefundRequest::TYPES as $k => $v)<option value="{{ $k }}" @selected(request('type') === $k)>{{ $v }}</option>@endforeach
        </select>
    </div>
    @if (request('order'))<div class="col-md-3"><a href="{{ route('admin.refunds.index') }}" class="btn btn-sm btn-outline-secondary">Đơn #{{ request('order') }} ✕</a></div>@endif
</form>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light"><tr class="small"><th>#</th><th>Đơn</th><th>Khách</th><th>Loại</th><th class="text-end">Số tiền</th><th>Trạng thái</th><th>Gửi lúc</th><th></th></tr></thead>
            <tbody>
                @forelse ($requests as $r)
                <tr>
                    <td>{{ $r->id }}</td>
                    <td><a href="{{ route('admin.orders.show', $r->order_id) }}">#{{ $r->order_id }}</a></td>
                    <td class="small">{{ optional($r->user)->name }}</td>
                    <td class="small">{{ $r->type_label }}@if ($r->reason_label)<div class="text-muted">{{ $r->reason_label }}</div>@endif</td>
                    <td class="text-end fw-semibold">{{ number_format($r->amount, 0, ',', '.') }} đ</td>
                    <td><span class="badge {{ $r->status_color }}">{{ $r->status_label }}</span></td>
                    <td class="small text-muted">{{ $r->created_at->format('d/m H:i') }}</td>
                    <td><a href="{{ route('admin.refunds.show', $r->id) }}" class="btn btn-sm btn-outline-primary">Xử lý</a></td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-4">Không có yêu cầu nào.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $requests->links() }}</div>
@endsection
