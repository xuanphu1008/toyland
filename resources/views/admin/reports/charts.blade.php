@extends('layouts.admin')

@section('title', 'Biểu đồ Doanh thu')

@push('styles')
<style>
    .chart-card canvas { max-height: 320px; }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="text-secondary fw-bold mb-0">Biểu Đồ Doanh Thu</h5>
    <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-secondary rounded-pill px-4">
        <i class="bi bi-arrow-left"></i> Quay lại báo cáo
    </a>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 chart-card h-100">
            <div class="card-body">
                <h6 class="fw-bold text-secondary mb-3">Doanh thu 30 ngày gần nhất</h6>
                <canvas id="chartDaily"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 chart-card h-100">
            <div class="card-body">
                <h6 class="fw-bold text-secondary mb-3">Tỷ trọng theo phương thức thanh toán</h6>
                <canvas id="chartPaymentMethod"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 chart-card h-100">
            <div class="card-body">
                <h6 class="fw-bold text-secondary mb-3">Doanh thu theo danh mục</h6>
                <canvas id="chartCategory"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 chart-card h-100">
            <div class="card-body">
                <h6 class="fw-bold text-secondary mb-3">Doanh thu 12 tháng gần nhất</h6>
                <canvas id="chartMonthly"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 chart-card h-100">
            <div class="card-body">
                <h6 class="fw-bold text-secondary mb-3">Doanh thu theo năm</h6>
                <canvas id="chartYearly"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 chart-card h-100">
            <div class="card-body">
                <h6 class="fw-bold text-secondary mb-3">Top 10 sản phẩm bán chạy (số lượng)</h6>
                <canvas id="chartTopProducts"></canvas>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const currency = (value) => new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value);

    // Bảng màu biểu đồ lấy quanh dải màu thương hiệu: đỏ -> đào -> cam mơ -> vàng -> lá
    const palette = ['#E03131', '#FF8080', '#F2921D', '#FFD24C', '#9BDE79', '#4E9E36', '#C97B5A', '#8A6A6A'];

    new Chart(document.getElementById('chartDaily'), {
        type: 'line',
        data: {
            labels: @json(collect($revDateLabels)->map(fn ($d) => \Carbon\Carbon::parse($d)->format('d/m'))),
            datasets: [{
                label: 'Doanh thu',
                data: @json($revDateData),
                borderColor: '#E03131',
                backgroundColor: 'rgba(13, 110, 253, 0.15)',
                fill: true,
                tension: 0.3,
                pointRadius: 2,
            }],
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: (ctx) => currency(ctx.parsed.y) } },
            },
            scales: {
                y: { ticks: { callback: (value) => currency(value) } },
            },
        },
    });

    new Chart(document.getElementById('chartPaymentMethod'), {
        type: 'doughnut',
        data: {
            labels: @json($paymentMethodLabels),
            datasets: [{
                data: @json($paymentMethodRevenue),
                backgroundColor: ['#A50064', '#4E9E36'],
            }],
        },
        options: {
            responsive: true,
            plugins: {
                tooltip: { callbacks: { label: (ctx) => `${ctx.label}: ${currency(ctx.parsed)}` } },
            },
        },
    });

    new Chart(document.getElementById('chartTopProducts'), {
        type: 'bar',
        data: {
            labels: @json($topProductLabels),
            datasets: [{
                label: 'Số lượng đã bán',
                data: @json($topProductQty),
                backgroundColor: palette,
            }],
        },
        options: {
            responsive: true,
            indexAxis: 'y',
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: (ctx) => ctx.parsed.x + ' sản phẩm' } },
            },
            scales: { x: { beginAtZero: true, ticks: { precision: 0 } } },
        },
    });

    new Chart(document.getElementById('chartCategory'), {
        type: 'bar',
        data: {
            labels: @json($catLabels),
            datasets: [{
                label: 'Doanh thu',
                data: @json($catRevenue),
                backgroundColor: palette,
            }],
        },
        options: {
            responsive: true,
            indexAxis: 'y',
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: (ctx) => currency(ctx.parsed.x) } },
            },
            scales: {
                x: { ticks: { callback: (value) => currency(value) } },
            },
        },
    });

    new Chart(document.getElementById('chartMonthly'), {
        type: 'bar',
        data: {
            labels: @json($revMonthLabels),
            datasets: [{
                label: 'Doanh thu',
                data: @json($revMonthData),
                backgroundColor: '#FD5D5D',
                borderRadius: 4,
            }],
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: (ctx) => currency(ctx.parsed.y) } },
            },
            scales: {
                y: { ticks: { callback: (value) => currency(value) } },
            },
        },
    });

    new Chart(document.getElementById('chartYearly'), {
        type: 'bar',
        data: {
            labels: @json($revYearLabels),
            datasets: [{
                label: 'Doanh thu',
                data: @json($revYearData),
                backgroundColor: '#4E9E36',
                borderRadius: 4,
            }],
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: (ctx) => currency(ctx.parsed.y) } },
            },
            scales: {
                y: { ticks: { callback: (value) => currency(value) } },
            },
        },
    });
});
</script>
@endpush
