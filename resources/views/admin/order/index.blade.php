@extends('layouts.admin')

@section('title', 'Quản lý Đơn hàng')

@push('styles')
<style>
    .badge-orange { background-color: var(--tl-warning, #F2921D); color: #3D2600; }
</style>
@endpush

@section('content')
@php
$colorClass = [
    'blue' => 'bg-primary',
    'slate' => 'bg-secondary',
    'cyan' => 'bg-info text-dark',
    'amber' => 'bg-warning text-dark',
    'green' => 'bg-success',
    'orange' => 'badge-orange',
    'red' => 'bg-danger',
];

$paymentColor = [
    'pending' => 'bg-warning text-dark',
    'initiated' => 'bg-info text-dark',
    'paid' => 'bg-success',
    'failed' => 'bg-danger',
    'cancelled' => 'bg-secondary',
    'refund_pending' => 'bg-warning text-dark',
    'refunded' => 'bg-secondary',
];

$shippingColor = [];
foreach ($tabs as $tabKey => $tabInfo) {
    foreach ($tabInfo['statuses'] as $st) {
        $shippingColor[$st] = $colorClass[$tabInfo['color']] ?? 'bg-secondary';
    }
}
@endphp

<div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-4">
    <div>
        <h1 class="tl-section-title"><span>🧾</span> Quản lý đơn hàng</h1>
        <p class="text-muted small mb-0 mt-2">Xác nhận, đóng gói, giao hàng và hủy đơn — tất cả ở một nơi.</p>
    </div>
    <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-primary">
        <i class="bi bi-graph-up-arrow me-1"></i> Báo cáo doanh thu
    </a>
</div>

{{-- Tabs theo trạng thái vận chuyển --}}
<div class="tl-tabs mb-4">
    @foreach ($tabs as $tabKey => $tabInfo)
    <a class="tl-tab {{ $activeTab === $tabKey ? 'active' : '' }}"
       href="{{ route('admin.orders.index', array_merge(request()->except(['tab', 'page']), ['tab' => $tabKey])) }}">
        {{ $tabInfo['label'] }}
        <span class="badge {{ $activeTab === $tabKey ? 'bg-light' : 'bg-secondary' }} ms-1">{{ $tabInfo['count'] }}</span>
    </a>
    @endforeach
</div>

{{-- Bộ lọc --}}
<form action="{{ route('admin.orders.index') }}" method="GET" class="row g-2 mb-4 bg-light p-3 rounded-3">
    <input type="hidden" name="tab" value="{{ $activeTab }}">

    <div class="col-md-3">
        <input type="text" name="search" class="form-control" placeholder="Tên KH, SĐT, mã vận đơn, sản phẩm, #ID..." value="{{ $filters['search'] ?? '' }}">
    </div>
    <div class="col-md-2">
        <select name="status" class="form-select">
            <option value="">-- Trạng thái đơn --</option>
            @foreach ($orderStatusLabels as $key => $label)
            <option value="{{ $key }}" {{ ($filters['status'] ?? '') == $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="payment_status" class="form-select">
            <option value="">-- Thanh toán --</option>
            @foreach ($paymentLabels as $key => $label)
            <option value="{{ $key }}" {{ ($filters['payment_status'] ?? '') == $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="shipping_status" class="form-select">
            <option value="">-- Vận chuyển --</option>
            @foreach ($shippingLabels as $key => $label)
            <option value="{{ $key }}" {{ ($filters['shipping_status'] ?? '') == $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="gateway" class="form-select">
            <option value="">-- Hình thức TT --</option>
            <option value="cod" {{ ($filters['gateway'] ?? '') == 'cod' ? 'selected' : '' }}>COD</option>
            <option value="momo" {{ ($filters['gateway'] ?? '') == 'momo' ? 'selected' : '' }}>MoMo</option>
            <option value="unknown" {{ ($filters['gateway'] ?? '') == 'unknown' ? 'selected' : '' }}>Khác</option>
        </select>
    </div>
    <div class="col-md-3">
        <div class="input-group">
            <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
            <span class="input-group-text">-</span>
            <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
        </div>
    </div>

    <div class="col-md-3">
        <select name="sort" class="form-select">
            <option value="newest" {{ ($filters['sort'] ?? 'newest') == 'newest' ? 'selected' : '' }}>Mới nhất</option>
            <option value="oldest" {{ ($filters['sort'] ?? '') == 'oldest' ? 'selected' : '' }}>Cũ nhất</option>
            <option value="amount_desc" {{ ($filters['sort'] ?? '') == 'amount_desc' ? 'selected' : '' }}>Giá trị giảm dần</option>
            <option value="amount_asc" {{ ($filters['sort'] ?? '') == 'amount_asc' ? 'selected' : '' }}>Giá trị tăng dần</option>
        </select>
    </div>
    <div class="col-md-2">
        <select name="per_page" class="form-select">
            @foreach ([25, 50, 100] as $pp)
            <option value="{{ $pp }}" {{ (int) ($filters['per_page'] ?? 25) === $pp ? 'selected' : '' }}>{{ $pp }} / trang</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4 d-flex gap-2">
        <button type="submit" class="btn btn-secondary flex-fill">Lọc & Tìm kiếm</button>
        <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary">Xóa lọc</a>
    </div>
</form>

<div class="card border-0 shadow-sm rounded-4 p-3">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle text-center">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th class="text-start">Khách hàng</th>
                        <th class="text-start">Sản phẩm</th>
                        <th>Tổng tiền</th>
                        <th>Trạng thái đơn</th>
                        <th>Thanh toán</th>
                        <th>Vận chuyển</th>
                        <th>Ngày đặt</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                    <tr>
                        <td class="fw-bold">#{{ $order->id }}</td>
                        <td class="text-start">
                            <div class="fw-semibold text-dark">{{ $order->user->name ?? 'Khách vãng lai' }}</div>
                            <div class="small text-muted">{{ $order->customer_phone }}</div>
                        </td>
                        <td class="text-start">
                            @php $firstItem = $order->items->first(); @endphp
                            @if ($firstItem)
                            <div>{{ $firstItem->product->name ?? 'Sản phẩm đã bị xóa' }}</div>
                            @if ($order->items->count() > 1)
                            <div class="small text-muted">và {{ $order->items->count() - 1 }} sản phẩm khác</div>
                            @endif
                            @else
                            <span class="text-muted">--</span>
                            @endif
                        </td>
                        <td class="text-danger fw-bold text-nowrap">{{ number_format($order->total_price, 0, ',', '.') }} đ</td>
                        <td>
                            <span class="badge {{ $order->status_color }}">{{ $order->status_label }}</span>
                        </td>
                        <td>
                            <span class="badge {{ $paymentColor[$order->payment_status] ?? 'bg-secondary' }}">
                                {{ $paymentLabels[$order->payment_status] ?? $order->payment_status }}
                            </span>
                            <div class="small text-muted text-uppercase mt-1">{{ $order->gateway }}</div>
                        </td>
                        <td>
                            <span class="badge {{ $shippingColor[$order->shipping_status] ?? 'bg-secondary' }}">
                                {{ $shippingLabels[$order->shipping_status] ?? $order->shipping_status }}
                            </span>
                        </td>
                        <td class="text-nowrap">{{ \Carbon\Carbon::parse($order->created_at)->format('d/m/Y H:i') }}</td>
                        <td>
                            <a href="{{ route('admin.orders.show', $order->id) }}" class="btn btn-outline-info btn-sm rounded-pill">
                                Xem
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-muted py-4">Không tìm thấy đơn hàng nào phù hợp.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center mt-3">
            {{ $orders->links() }}
        </div>
    </div>
</div>
@endsection
