@extends('layouts.admin')

@section('title', 'Báo cáo Doanh thu')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="text-secondary fw-bold mb-0">Báo Cáo Doanh Thu</h5>
    <a href="{{ route('admin.reports.charts') }}" class="btn btn-primary rounded-pill px-4 shadow-sm">
        <i class="bi bi-bar-chart-line me-1"></i> Xem biểu đồ
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body">
                <div class="text-muted small fw-semibold">TỔNG DOANH THU (đơn đã thanh toán)</div>
                <div class="fs-3 fw-bold text-danger mt-2">{{ number_format($totalRevenue, 0, ',', '.') }} đ</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body">
                <div class="text-muted small fw-semibold">TỔNG SỐ ĐƠN HÀNG</div>
                <div class="fs-3 fw-bold text-primary mt-2">{{ number_format($totalOrders) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body">
                <div class="text-muted small fw-semibold">TỔNG KHÁCH HÀNG</div>
                <div class="fs-3 fw-bold text-success mt-2">{{ number_format($totalCustomers) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-5">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body">
                <h6 class="fw-bold text-secondary mb-3">Doanh thu theo Danh mục</h6>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="text-start">Danh mục</th>
                                <th class="text-center">SL bán</th>
                                <th class="text-end">Doanh thu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($categoryRevenue as $row)
                            <tr>
                                <td class="text-start">{{ $row->category_name ?? 'Danh mục #'.$row->category_id }}</td>
                                <td class="text-center">{{ number_format($row->total_qty) }}</td>
                                <td class="text-end fw-semibold text-danger">{{ number_format($row->total_revenue, 0, ',', '.') }} đ</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center text-muted py-3">Chưa có dữ liệu.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body">
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-date" type="button">Theo ngày</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-month" type="button">Theo tháng</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-year" type="button">Theo năm</button></li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="tab-date">
                        <div class="table-responsive" style="max-height: 360px; overflow-y: auto;">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light"><tr><th>Ngày</th><th class="text-center">Số đơn</th><th class="text-end">Doanh thu</th></tr></thead>
                                <tbody>
                                    @forelse ($revenueByDate->sortByDesc('date') as $row)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($row->date)->format('d/m/Y') }}</td>
                                        <td class="text-center">{{ $row->order_count }}</td>
                                        <td class="text-end fw-semibold text-danger">{{ number_format($row->total_revenue, 0, ',', '.') }} đ</td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="3" class="text-center text-muted py-3">Chưa có dữ liệu.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="tab-month">
                        <div class="table-responsive" style="max-height: 360px; overflow-y: auto;">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light"><tr><th>Tháng</th><th class="text-center">Số đơn</th><th class="text-end">Doanh thu</th></tr></thead>
                                <tbody>
                                    @forelse ($revenueByMonth->sortByDesc('month') as $row)
                                    <tr>
                                        <td>{{ \Illuminate\Support\Str::of($row->month)->explode('-')->reverse()->implode('/') }}</td>
                                        <td class="text-center">{{ $row->order_count }}</td>
                                        <td class="text-end fw-semibold text-danger">{{ number_format($row->total_revenue, 0, ',', '.') }} đ</td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="3" class="text-center text-muted py-3">Chưa có dữ liệu.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="tab-year">
                        <div class="table-responsive" style="max-height: 360px; overflow-y: auto;">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light"><tr><th>Năm</th><th class="text-center">Số đơn</th><th class="text-end">Doanh thu</th></tr></thead>
                                <tbody>
                                    @forelse ($revenueByYear->sortByDesc('year') as $row)
                                    <tr>
                                        <td>{{ $row->year }}</td>
                                        <td class="text-center">{{ $row->order_count }}</td>
                                        <td class="text-end fw-semibold text-danger">{{ number_format($row->total_revenue, 0, ',', '.') }} đ</td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="3" class="text-center text-muted py-3">Chưa có dữ liệu.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body">
                <h6 class="fw-bold text-secondary mb-3">Top 10 Sản Phẩm Bán Chạy</h6>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 60px;">Hạng</th>
                                <th style="width: 70px;">Ảnh</th>
                                <th class="text-start">Sản phẩm</th>
                                <th class="text-start">Danh mục</th>
                                <th class="text-center">Đã bán</th>
                                <th class="text-end">Doanh thu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($topProducts as $index => $row)
                            <tr>
                                <td>
                                    @if ($index < 3)
                                    <span class="badge bg-warning text-dark">#{{ $index + 1 }}</span>
                                    @else
                                    <span class="text-muted">#{{ $index + 1 }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($row->image)
                                    {{-- $row ở đây là stdClass do DB::table()->get() trả về, KHÔNG phải model Product
                                         nên không có accessor image_url. Dùng bản static của Product để quy đổi đường dẫn. --}}
                                    <img src="{{ \App\Models\Product::imageUrl($row->image) }}" class="rounded border" width="46" height="46" style="object-fit: cover;" alt="{{ $row->name }}">
                                    @else
                                    <span class="badge bg-secondary">--</span>
                                    @endif
                                </td>
                                <td class="text-start fw-semibold">
                                    <a href="{{ route('admin.products.show', $row->id) }}" class="text-decoration-none text-dark">{{ $row->name }}</a>
                                </td>
                                <td class="text-start"><span class="badge bg-info text-dark">{{ $row->category_name ?? 'Chưa phân loại' }}</span></td>
                                <td class="text-center fw-bold">{{ number_format($row->total_qty, 0, ',', '.') }}</td>
                                <td class="text-end fw-bold text-danger">{{ number_format($row->total_revenue, 0, ',', '.') }} đ</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-muted text-center py-4">Chưa có sản phẩm nào được bán trong các đơn đã thanh toán.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
