@extends('layouts.admin')

@section('title', 'Đơn vị vận chuyển')

@section('content')
@php
$shipColor = [
    'ready_to_pick' => 'bg-info text-dark', 'picking' => 'bg-info text-dark', 'picked' => 'bg-primary',
    'delivering' => 'bg-warning text-dark', 'delivery_fail' => 'bg-danger', 'delivered' => 'bg-success',
    'returning' => 'bg-danger-subtle text-danger', 'returned' => 'bg-dark', 'cancelled' => 'bg-secondary',
];
$btn = [
    'pick' => 'btn-primary', 'out_for_delivery' => 'btn-warning', 'deliver' => 'btn-success',
    'fail_delivery' => 'btn-outline-danger', 'start_return' => 'btn-danger', 'confirm_returned' => 'btn-dark',
];
// Thao tác cần ghi lý do -> hiện ô nhập
$needNote = ['fail_delivery', 'start_return'];
@endphp

<div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
    <div>
        <h1 class="tl-section-title"><span>🚚</span> Đơn vị vận chuyển</h1>
        <p class="text-muted small mb-0 mt-2">
            {{ $carrierName }} ·
            @if ($manual)
                Bạn đang <strong>đóng vai bên giao hàng</strong>: lấy hàng, đi giao, báo giao thành công/thất bại, chuyển hoàn.
            @else
                Trạng thái do đối tác báo về; chỉ cập nhật tay khi cần xử lý ngoại lệ.
            @endif
        </p>
    </div>
    <div class="text-end">
        <div class="small text-muted">Tiền COD đang cầm (đơn đang giao)</div>
        <div class="fs-5 fw-bold text-danger">{{ number_format($codToCollect, 0, ',', '.') }} đ</div>
    </div>
</div>

@if ($errors->any())
<div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $errors->first() }}</div>
@endif

<div class="tl-tabs mb-3">
    @foreach ($tabs as $key => $t)
    <a class="tl-tab {{ $tab === $key && $search === '' ? 'active' : '' }}" href="{{ route('admin.carrier.index', ['tab' => $key]) }}">
        {{ $t['label'] }}
        @if (! is_null($t['count']))<span class="badge {{ $tab === $key ? 'bg-light' : 'bg-secondary' }} ms-1">{{ $t['count'] }}</span>@endif
    </a>
    @endforeach
</div>

<form method="GET" action="{{ route('admin.carrier.index') }}" class="mb-3 d-flex gap-2" style="max-width: 520px">
    <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Quét / nhập mã vận đơn, mã đơn hoặc SĐT">
    <button class="btn btn-secondary text-nowrap">Tìm</button>
    @if ($search !== '')<a href="{{ route('admin.carrier.index') }}" class="btn btn-outline-secondary">Xóa</a>@endif
</form>

<form id="carrier-bulk" action="{{ route('admin.carrier.bulk') }}" method="POST"
      onsubmit="return confirm('Thực hiện cho các đơn đã chọn?')">
    @csrf
    <div class="d-flex flex-wrap align-items-center gap-2 mb-2 p-2 rounded-3 bg-light">
        <span class="small fw-semibold"><span id="c-count">0</span> đơn đã chọn</span>
        <select name="action" id="c-action" class="form-select form-select-sm w-auto" required>
            <option value="">— Thao tác hàng loạt —</option>
            @foreach ($bulkActions as $value => $label)
            <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
        <button class="btn btn-sm btn-primary" id="c-submit" disabled>Thực hiện</button>
        <span class="small text-muted ms-auto">Giao thất bại / chuyển hoàn làm từng đơn vì cần ghi lý do.</span>
    </div>
</form>

<div class="card border-0 shadow-sm rounded-4">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:36px"><input type="checkbox" class="form-check-input" id="c-all"></th>
                    <th>Mã vận đơn</th>
                    <th>Người nhận</th>
                    <th class="text-end">Thu hộ</th>
                    <th>Trạng thái</th>
                    <th style="min-width: 330px">Thao tác của bên vận chuyển</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                @php
                    $parts = array_map('trim', explode('|', (string) $order->shipping_address));
                    $acts = $actions[$order->id] ?? [];
                @endphp
                <tr>
                    <td><input type="checkbox" class="form-check-input c-item" form="carrier-bulk" name="ids[]" value="{{ $order->id }}"></td>
                    <td>
                        <div class="fw-bold font-monospace">{{ $order->ghn_order_code }}</div>
                        <a href="{{ route('admin.orders.show', $order->id) }}" class="small text-decoration-none">Đơn #{{ $order->id }}</a>
                        · <a href="{{ route('admin.orders.labels', ['ids' => $order->id]) }}" target="_blank" class="small">phiếu</a>
                    </td>
                    <td class="small">
                        <div class="fw-semibold">{{ $parts[0] ?? '' }} · {{ $order->customer_phone }}</div>
                        <div class="text-muted">{{ \Illuminate\Support\Str::limit($parts[2] ?? $order->shipping_address, 70) }}</div>
                    </td>
                    <td class="text-end fw-semibold {{ $order->cod_amount > 0 ? 'text-danger' : 'text-success' }}">
                        {{ $order->cod_amount > 0 ? number_format($order->cod_amount, 0, ',', '.') . ' đ' : 'Đã trả trước' }}
                    </td>
                    <td>
                        <span class="badge {{ $shipColor[$order->shipping_status] ?? 'bg-secondary' }}">{{ $order->shipping_label }}</span>
                        @if ($order->delivery_attempts > 0)
                        <div class="small text-muted mt-1">Đã giao {{ $order->delivery_attempts }}/{{ $maxAttempts }} lần</div>
                        @endif
                        @if ($order->last_delivery_note)
                        <div class="small text-muted">“{{ \Illuminate\Support\Str::limit($order->last_delivery_note, 60) }}”</div>
                        @endif
                    </td>
                    <td>
                        @if ($acts)
                        <form action="{{ route('admin.carrier.act', $order->id) }}" method="POST" class="d-flex flex-wrap gap-1 align-items-center">
                            @csrf
                            @if (array_intersect(array_keys($acts), $needNote))
                            <input type="text" name="note" maxlength="255" class="form-control form-control-sm mb-1"
                                   placeholder="Ghi chú / lý do (bắt buộc khi giao thất bại, chuyển hoàn)">
                            @endif
                            @foreach ($acts as $action => $label)
                            <button type="submit" name="action" value="{{ $action }}" class="btn btn-sm {{ $btn[$action] ?? 'btn-secondary' }}">{{ $label }}</button>
                            @endforeach
                        </form>
                        @else
                        <span class="small text-muted">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-5">Không có đơn nào ở mục này.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($orders->hasPages())
    <div class="card-footer bg-white border-0 d-flex justify-content-center py-3">{{ $orders->links() }}</div>
    @endif
</div>

<script>
(function () {
    const all = document.getElementById('c-all');
    const items = () => Array.from(document.querySelectorAll('.c-item'));
    const action = document.getElementById('c-action');
    const submit = document.getElementById('c-submit');
    const count = document.getElementById('c-count');
    function refresh() {
        const n = items().filter(i => i.checked).length;
        count.textContent = n;
        submit.disabled = n === 0 || !action.value;
    }
    all && all.addEventListener('change', () => { items().forEach(i => i.checked = all.checked); refresh(); });
    items().forEach(i => i.addEventListener('change', refresh));
    action.addEventListener('change', refresh);
})();
</script>
@endsection
