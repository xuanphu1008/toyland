@extends('layouts.admin')

@section('title', 'Giao dịch thanh toán')

@section('content')
@php
    $badge = [
        'pending' => 'bg-warning text-dark',
        'initiated' => 'bg-info text-dark',
        'paid' => 'bg-success',
        'failed' => 'bg-danger',
        'cancelled' => 'bg-secondary',
        'cod_collected' => 'bg-info text-dark',
        'refund_pending' => 'bg-warning text-dark',
        'refunded' => 'bg-secondary',
    ];

    $current = request()->only(['search', 'date_from', 'date_to', 'min_amount', 'max_amount', 'gateway', 'payment_status']);
@endphp

<div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-4">
    <div>
        <h1 class="tl-section-title"><span>🧾</span> Giao dịch thanh toán</h1>
        <p class="text-muted small mb-0 mt-2">Tra cứu thanh toán theo đơn hàng và cập nhật trạng thái COD.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.finance.export', request()->only(['search', 'date_from', 'date_to', 'min_amount', 'max_amount', 'gateway', 'payment_status', 'sort'])) }}"
           class="btn btn-outline-success">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Xuất dữ liệu
        </a>
        <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-primary">
            <i class="bi bi-box-seam me-1"></i> Quản lý đơn hàng
        </a>
    </div>
</div>

<div class="tl-tabs mb-4">
    <a class="tl-tab" href="{{ route('admin.finance.index', $current) }}">Thống kê chỉ số</a>
    <a class="tl-tab active" href="{{ route('admin.finance.transactions', $current) }}">Giao dịch thanh toán</a>
</div>

@if ($errors->any())
<div class="alert alert-danger">
    <i class="bi bi-exclamation-triangle-fill me-1"></i>
    {{ $errors->first() }}
</div>
@endif

@include('admin.finance._filters', [
    'action' => route('admin.finance.transactions'),
    'filters' => $filters,
    'statuses' => $statuses,
    'methods' => $methods,
    'withSort' => true,
])

<p class="text-muted small">
    Có {{ number_format($orders->total()) }} đơn phù hợp bộ lọc.
    Số tiền bao gồm phí vận chuyển; ngày lọc là ngày tạo đơn.
</p>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-0 pt-3">
        <h6 class="fw-bold mb-1">Danh sách giao dịch ({{ number_format($orders->total()) }} đơn)</h6>
        <p class="text-muted small mb-0">
            COD: shipper giao xong thì đơn tự vào "Đã thu hộ - chờ đối soát"; khi bên vận chuyển chuyển tiền về shop,
            bạn đối soát khớp để chuyển sang "Đã thanh toán" (hoặc báo lệch). Hoàn tiền không làm ở đây mà ở mục "Hoàn tiền".
        </p>
    </div>

    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Đơn hàng</th>
                    <th>Khách hàng</th>
                    <th>Phương thức</th>
                    <th class="text-end">Số tiền</th>
                    <th>Thanh toán</th>
                    <th style="min-width: 260px;">Cập nhật COD</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                @php
                    $isCod = $order->gateway === 'cod';
                    // Chỉ những bước có trong bảng chuyển trạng thái mới được hiện ra,
                    // nên người dùng không chọn được thao tác mà máy chủ sẽ từ chối.
                    $allowed = $codTransitions[$order->payment_status] ?? [];
                    $canUpdate = $isCod && count($allowed) > 1;
                @endphp
                <tr>
                    <td>
                        <a href="{{ route('admin.orders.show', $order->id) }}" class="fw-semibold text-decoration-none">
                            #{{ $order->id }}
                        </a>
                        <div class="small text-muted">
                            {{ \Illuminate\Support\Carbon::parse($order->created_at)->format('d/m/Y H:i') }}
                        </div>
                    </td>
                    <td>
                        <div>{{ $order->name ?? '(tài khoản đã xóa)' }}</div>
                        <div class="small text-muted">{{ $order->customer_phone ?: '—' }}</div>
                    </td>
                    <td>
                        {{ $methods[$order->gateway] ?? $order->gateway }}
                        @if ($order->paid_at)
                        <div class="small text-muted">
                            Thu lúc {{ \Illuminate\Support\Carbon::parse($order->paid_at)->format('d/m/Y H:i') }}
                        </div>
                        @endif
                    </td>
                    <td class="text-end fw-semibold">{{ number_format($order->total_price, 0, ',', '.') }} đ</td>
                    <td>
                        <span class="badge {{ $badge[$order->payment_status] ?? 'bg-secondary' }}">
                            {{ $statuses[$order->payment_status] ?? $order->payment_status }}
                        </span>
                    </td>
                    <td>
                        @if ($canUpdate)
                        <form action="{{ route('admin.finance.update-status', $order->id) }}" method="POST"
                              class="d-flex gap-2">
                            @csrf
                            @method('PATCH')
                            {{-- Ảnh chụp trạng thái lúc tải trang: nếu ai đó sửa đơn trước
                                 khi biểu mẫu này được gửi đi, máy chủ sẽ phát hiện và chặn lại --}}
                            <input type="hidden" name="current_payment_status" value="{{ $order->payment_status }}">
                            <input type="hidden" name="current_order_status" value="{{ $order->status }}">
                            <input type="hidden" name="current_payment_id" value="{{ (int) ($order->payment_id ?? 0) }}">

                            <select name="payment_status" class="form-select form-select-sm">
                                @foreach ($allowed as $key)
                                <option value="{{ $key }}" {{ $key === $order->payment_status ? 'selected' : '' }}>
                                    {{ $statuses[$key] ?? $key }}
                                </option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-sm btn-primary text-nowrap">Lưu</button>
                        </form>
                        @elseif (in_array($order->payment_status, ['refund_pending', 'refunded'], true))
                        <span class="small text-muted">Xử lý ở mục
                            @if (\Illuminate\Support\Facades\Route::has('admin.refunds.index'))
                            <a href="{{ route('admin.refunds.index', ['order' => $order->id]) }}">Hoàn tiền</a>
                            @else Hoàn tiền @endif
                        </span>
                        @elseif ($isCod && $order->payment_status === 'pending')
                        <span class="small text-muted">Chờ giao hàng - tự cập nhật khi shipper giao xong</span>
                        @elseif ($isCod)
                        <span class="small text-muted">Không còn bước chuyển tiếp</span>
                        @else
                        <span class="small text-muted">Do hệ thống / cổng thanh toán quyết định</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-5">Không có đơn hàng phù hợp với bộ lọc.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($orders->hasPages())
    <div class="card-footer bg-white border-0 d-flex justify-content-center py-3">
        {{ $orders->links() }}
    </div>
    @endif
</div>
@endsection
