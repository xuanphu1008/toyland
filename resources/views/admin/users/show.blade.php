@extends('layouts.admin')

@section('title', 'Chi tiết người dùng')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <h5 class="fw-bold mb-0">Hồ sơ khách hàng</h5>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-primary rounded-pill px-4">
            <i class="bi bi-pencil-square me-1"></i> Sửa
        </a>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary rounded-pill px-4">
            <i class="bi bi-arrow-left me-1"></i> Quay lại
        </a>
    </div>
</div>

<div class="row g-4">
    {{-- ===== Thẻ thông tin tài khoản ===== --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body text-center p-4">
                <div class="rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center"
                     style="width: 84px; height: 84px; background: var(--tl-grad-primary); color: #fff; font-size: 2rem; font-weight: 700;">
                    {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                </div>

                <h6 class="fw-bold mb-1">{{ $user->name }}</h6>
                <div class="text-muted small mb-3">{{ $user->email }}</div>

                <span class="badge rounded-pill {{ $user->isAdmin() ? 'bg-danger' : 'bg-success' }}">
                    {{ $user->isAdmin() ? 'Quản trị viên' : 'Khách hàng' }}
                </span>

                @if (! $user->isAdmin())
                <span class="badge rounded-pill ms-1 tl-badge-cream">
                    Hạng {{ $user->tier_label }}
                </span>
                @endif

                <hr class="my-4">

                <div class="row text-center g-2">
                    <div class="col-6">
                        <div class="text-muted small">Số đơn</div>
                        <div class="fw-bold fs-5">{{ $user->orders_count }}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted small">Điểm thưởng</div>
                        <div class="fw-bold fs-5" style="color: var(--tl-amber)">{{ number_format($user->points ?? 0, 0, ',', '.') }}</div>
                    </div>
                </div>

                <hr class="my-4">

                <div class="text-start small">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Tham gia</span>
                        <span class="fw-semibold">{{ optional($user->created_at)->format('d/m/Y') }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Xác thực email</span>
                        <span class="fw-semibold">{{ $user->email_verified_at ? 'Đã xác thực' : 'Chưa xác thực' }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Tổng chi tiêu</span>
                        <span class="fw-semibold" style="color: var(--tl-danger)">
                            {{ number_format($user->lifetime_spent ?? 0, 0, ',', '.') }} đ
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Lịch sử đơn hàng ===== --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Lịch sử đơn hàng</h6>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Mã đơn</th>
                                <th>Ngày đặt</th>
                                <th class="text-center">Số món</th>
                                <th class="text-end">Tổng tiền</th>
                                <th class="text-center">Trạng thái</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($orders as $order)
                            <tr>
                                <td class="fw-bold">#{{ $order->id }}</td>
                                <td class="small text-muted">{{ optional($order->created_at)->format('d/m/Y H:i') }}</td>
                                <td class="text-center">{{ $order->items->count() }}</td>
                                <td class="text-end fw-semibold" style="color: var(--tl-danger)">
                                    {{ number_format($order->total_price, 0, ',', '.') }} đ
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $order->status_color }}">{{ $order->status_label }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.orders.show', $order->id) }}" class="btn btn-sm btn-outline-primary rounded-pill">Xem</a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Khách hàng này chưa đặt đơn nào.</td>
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
    </div>
</div>
@endsection
