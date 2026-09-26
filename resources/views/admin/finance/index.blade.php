@extends('layouts.admin')

@section('title', 'Thống kê tài chính')

@section('content')
@php
    // Màu của từng thẻ chỉ số: xanh lá cho tiền đã thu, đỏ cho tiền hỏng,
    // vàng cho tiền đang treo - nhìn bảng là biết ngay chỗ nào cần xử lý.
    $tone = [
        'pending' => 'text-warning',
        'initiated' => 'text-info',
        'paid' => 'text-success',
        'failed' => 'text-danger',
        'cancelled' => 'text-secondary',
        'refund_pending' => 'text-warning',
        'refunded' => 'text-secondary',
    ];

    // Giữ nguyên bộ lọc hiện tại khi bấm sang tab kia hoặc khi xuất dữ liệu.
    $current = request()->only(['search', 'date_from', 'date_to', 'min_amount', 'max_amount', 'gateway', 'payment_status']);
@endphp

<div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-4">
    <div>
        <h1 class="tl-section-title"><span>💰</span> Thống kê tài chính</h1>
        <p class="text-muted small mb-0 mt-2">Tổng hợp giá trị thanh toán theo trạng thái và phương thức.</p>
    </div>
    {{-- Mang theo đủ bộ lọc đang áp dụng: file tải về khớp đúng những gì đang thấy --}}
    <a href="{{ route('admin.finance.export', $current) }}" class="btn btn-outline-success">
        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Xuất dữ liệu
    </a>
</div>

{{-- Hai trang tài chính dùng chung bộ lọc nên để cạnh nhau cho dễ chuyển qua lại --}}
<div class="tl-tabs mb-4">
    <a class="tl-tab active" href="{{ route('admin.finance.index', $current) }}">Thống kê chỉ số</a>
    <a class="tl-tab" href="{{ route('admin.finance.transactions', $current) }}">Giao dịch thanh toán</a>
</div>

@if ($errors->any())
<div class="alert alert-danger">
    <i class="bi bi-exclamation-triangle-fill me-1"></i>
    {{ $errors->first() }}
</div>
@endif

@include('admin.finance._filters', [
    'action' => route('admin.finance.index'),
    'filters' => $filters,
    'statuses' => $statuses,
    'methods' => $methods,
    'withSort' => false,
])

<p class="text-muted small">
    Có {{ number_format($summary->order_count ?? 0) }} đơn phù hợp.
    Số tiền bao gồm phí vận chuyển; thống kê theo ngày tạo đơn trên kết quả bộ lọc.
</p>

{{-- ============ TÁM THẺ CHỈ SỐ ============ --}}
<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body">
                <div class="text-muted small fw-semibold">Tổng giá trị đơn hàng</div>
                <div class="fs-4 fw-bold mt-1">{{ number_format($summary->total_amount ?? 0, 0, ',', '.') }} đ</div>
                <div class="small text-muted">{{ number_format($summary->order_count ?? 0) }} đơn, bao gồm đơn đã hủy</div>
            </div>
        </div>
    </div>

    @foreach ($statuses as $key => $label)
    @php $row = $statusTotals[$key] ?? null; @endphp
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body">
                <div class="text-muted small fw-semibold">{{ $label }}</div>
                <div class="fs-4 fw-bold mt-1 {{ $tone[$key] ?? '' }}">
                    {{ number_format($row->total_amount ?? 0, 0, ',', '.') }} đ
                </div>
                <div class="small text-muted">{{ number_format($row->order_count ?? 0) }} đơn</div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- ============ THỐNG KÊ THEO PHƯƠNG THỨC ============ --}}
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-0 pt-3">
        <h6 class="fw-bold mb-0">Thống kê theo phương thức</h6>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Phương thức</th>
                    <th class="text-end">Số đơn</th>
                    <th class="text-end">Tổng giá trị</th>
                    <th class="text-end">Đã thanh toán</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($methods as $key => $label)
                @php $row = $methodTotals[$key] ?? null; @endphp
                <tr>
                    <td class="fw-semibold">{{ $label }}</td>
                    <td class="text-end">{{ number_format($row->order_count ?? 0) }}</td>
                    <td class="text-end">{{ number_format($row->total_amount ?? 0, 0, ',', '.') }} đ</td>
                    <td class="text-end text-success fw-semibold">
                        {{ number_format($row->paid_amount ?? 0, 0, ',', '.') }} đ
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="table-light fw-bold">
                <tr>
                    <td>Tổng cộng</td>
                    <td class="text-end">{{ number_format($methodTotals->sum('order_count')) }}</td>
                    <td class="text-end">{{ number_format($methodTotals->sum('total_amount'), 0, ',', '.') }} đ</td>
                    <td class="text-end text-success">
                        {{ number_format($methodTotals->sum('paid_amount'), 0, ',', '.') }} đ
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
