@extends('layouts.admin')

@section('title', 'Bảng điều khiển')

@section('content')
<div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Xin chào, {{ \Illuminate\Support\Str::before(Auth::user()->name, ' ') ?: Auth::user()->name }} 👋</h1>
        <p class="text-muted mb-0">Đây là tình hình cửa hàng ToyLand hôm nay.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.products.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Thêm sản phẩm
        </a>
        <a href="{{ route('admin.reports.charts') }}" class="btn btn-outline-primary">
            <i class="bi bi-bar-chart me-1"></i> Biểu đồ
        </a>
    </div>
</div>

{{-- ===== Thẻ số liệu chính ===== --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3 tl-reveal">
        <a href="{{ route('admin.orders.index', ['status' => 'pending']) }}" class="text-decoration-none">
            <div class="tl-stat-card tl-accent-sun">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="tl-stat-label">Đơn chờ xác nhận</div>
                        <div class="tl-stat-value" style="color: var(--tl-warning)">{{ $pendingOrders }}</div>
                    </div>
                    <span class="tl-stat-icon" style="background: var(--tl-warning-bg)">⏳</span>
                </div>
                @if ($pendingOrders > 0)
                <div class="small mt-2" style="color: var(--tl-warning)">Cần xử lý ngay →</div>
                @else
                <div class="small text-muted mt-2">Không có đơn nào đang chờ</div>
                @endif
            </div>
        </a>
    </div>

    <div class="col-6 col-xl-3 tl-reveal" data-tl-delay="70">
        <a href="{{ route('admin.orders.index') }}" class="text-decoration-none">
            <div class="tl-stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="tl-stat-label">Đang chuẩn bị / giao</div>
                        <div class="tl-stat-value" style="color: var(--tl-primary)">{{ $shippingOrders }}</div>
                    </div>
                    <span class="tl-stat-icon">🚚</span>
                </div>
                <div class="small text-muted mt-2">Tổng {{ $totalOrders }} đơn từ trước tới nay</div>
            </div>
        </a>
    </div>

    <div class="col-6 col-xl-3 tl-reveal" data-tl-delay="140">
        <div class="tl-stat-card tl-accent-mint">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="tl-stat-label">Doanh thu hôm nay</div>
                    <div class="tl-stat-value" style="color: var(--tl-success); font-size: 1.4rem">
                        {{ number_format($revenueToday, 0, ',', '.') }} đ
                    </div>
                </div>
                <span class="tl-stat-icon" style="background: var(--tl-success-bg)">💰</span>
            </div>
            <div class="small text-muted mt-2">
                Tháng này: <strong>{{ number_format($revenueMonth, 0, ',', '.') }} đ</strong>
            </div>
        </div>
    </div>

    <div class="col-6 col-xl-3 tl-reveal" data-tl-delay="210">
        <a href="{{ route('admin.products.index', ['status' => 'out_of_stock']) }}" class="text-decoration-none">
            <div class="tl-stat-card tl-accent-pink">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="tl-stat-label">Sản phẩm hết hàng</div>
                        <div class="tl-stat-value" style="color: var(--tl-danger)">{{ $outOfStock }}</div>
                    </div>
                    <span class="tl-stat-icon" style="background: var(--tl-danger-bg)">📦</span>
                </div>
                <div class="small text-muted mt-2">Trên tổng {{ $totalProducts }} sản phẩm</div>
            </div>
        </a>
    </div>
</div>

{{-- ===== Thẻ số liệu phụ ===== --}}
<div class="row g-3 mb-4">
    @php
    $mini = [
        ['label' => 'Tổng sản phẩm', 'value' => $totalProducts, 'icon' => '🧸', 'url' => route('admin.products.index')],
        ['label' => 'Danh mục', 'value' => $totalCategories, 'icon' => '🗂️', 'url' => route('admin.categories.index')],
        ['label' => 'Khách hàng', 'value' => $totalCustomers, 'icon' => '👨‍👩‍👧', 'url' => route('admin.users.index')],
        ['label' => 'Đánh giá mới (7 ngày)', 'value' => $newReviews, 'icon' => '⭐', 'url' => route('admin.reviews.index')],
    ];
    @endphp

    @foreach ($mini as $i => $item)
    <div class="col-6 col-lg-3 tl-reveal" data-tl-delay="{{ $i * 60 }}">
        <a href="{{ $item['url'] }}" class="text-decoration-none">
            <div class="card h-100 p-3 d-flex flex-row align-items-center gap-3">
                <span class="tl-stat-icon">{{ $item['icon'] }}</span>
                <div>
                    <div class="tl-stat-label">{{ $item['label'] }}</div>
                    <div class="fw-bold fs-4" style="font-family: var(--tl-font-head); color: var(--tl-ink)">{{ $item['value'] }}</div>
                </div>
            </div>
        </a>
    </div>
    @endforeach
</div>

<div class="row g-4">
    {{-- ===== Đơn hàng mới nhất ===== --}}
    <div class="col-xl-7">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="tl-section-title"><span>🧾</span> Đơn hàng mới nhất</h2>
                    <a href="{{ route('admin.orders.index') }}" class="small text-decoration-none fw-semibold">Xem tất cả →</a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Mã</th>
                                <th>Khách hàng</th>
                                <th class="text-end">Tổng tiền</th>
                                <th>Trạng thái</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($latestOrders as $order)
                            <tr>
                                <td class="fw-bold" style="color: var(--tl-primary)">#{{ $order->id }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $order->user->name ?? 'Khách' }}</div>
                                    <div class="small text-muted">{{ $order->created_at->diffForHumans() }}</div>
                                </td>
                                <td class="text-end fw-bold" style="color: var(--tl-danger)">
                                    {{ number_format($order->total_price, 0, ',', '.') }} đ
                                </td>
                                <td><span class="badge {{ $order->status_color }}">{{ $order->status_label }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('admin.orders.show', $order->id) }}" class="btn btn-sm btn-outline-primary">Xử lý</a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5">
                                    <div class="tl-empty py-4">
                                        <span class="tl-empty-icon">📭</span>
                                        Chưa có đơn hàng nào.
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Top bán chạy ===== --}}
    <div class="col-xl-5">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="tl-section-title"><span>🏆</span> Top bán chạy</h2>
                    <a href="{{ route('admin.reports.index') }}" class="small text-decoration-none fw-semibold">Báo cáo →</a>
                </div>

                @forelse ($bestSellers as $index => $product)
                <div class="d-flex align-items-center gap-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                    <span class="badge {{ $index < 3 ? 'bg-warning' : 'bg-light' }}" style="min-width: 30px">#{{ $index + 1 }}</span>

                    @if ($product->image)
                    <img src="{{ $product->image_url }}" class="rounded-3" width="44" height="44"
                         style="object-fit: cover;" alt="{{ $product->name }}">
                    @else
                    <span class="tl-stat-icon" style="width:44px;height:44px;font-size:1.1rem">🧸</span>
                    @endif

                    <div class="flex-grow-1 small">
                        <div class="fw-semibold">{{ \Illuminate\Support\Str::limit($product->name, 32) }}</div>
                        <div class="text-muted">{{ $product->category->name ?? '' }}</div>
                    </div>

                    <span class="badge bg-success">{{ (int) $product->sold_quantity }}</span>
                </div>
                @empty
                <div class="tl-empty py-4">
                    <span class="tl-empty-icon">📊</span>
                    Chưa có dữ liệu bán hàng.
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
