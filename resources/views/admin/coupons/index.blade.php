@extends('layouts.admin')

@section('title', 'Mã giảm giá')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="text-secondary fw-bold mb-0">Mã Giảm Giá & Khuyến Mãi</h5>
    <a href="{{ route('admin.coupons.create') }}" class="btn btn-primary rounded-pill px-4 shadow-sm">+ Tạo mã mới</a>
</div>

<div class="card border-0 shadow-sm rounded-4 p-3">
    <div class="card-body">
        <form action="{{ route('admin.coupons.index') }}" method="GET" class="row g-2 mb-4 bg-light p-3 rounded-3">
            <div class="col-md-6">
                <input type="text" name="search" class="form-control" placeholder="Tìm theo mã hoặc mô tả..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">-- Tất cả trạng thái --</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Đang chạy</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Đã ngưng</option>
                </select>
            </div>
            <div class="col-md-3"><button class="btn btn-secondary w-100">Lọc</button></div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle text-center">
                <thead class="table-light">
                    <tr>
                        <th class="text-start">Mã</th>
                        <th>Giá trị</th>
                        <th>Đơn tối thiểu</th>
                        <th>Đã dùng</th>
                        <th>Hiệu lực</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($coupons as $coupon)
                    <tr>
                        <td class="text-start">
                            <span class="badge bg-dark font-monospace">{{ $coupon->code }}</span>
                            <div class="small text-muted mt-1">{{ $coupon->description }}</div>
                        </td>
                        <td class="fw-bold text-danger">{{ $coupon->value_label }}</td>
                        <td>{{ number_format($coupon->min_order_amount, 0, ',', '.') }} đ</td>
                        <td>
                            {{ $coupon->used_count }}{{ $coupon->usage_limit ? ' / ' . $coupon->usage_limit : '' }}
                            <div class="small text-muted">{{ $coupon->usage_limit_per_user }} lượt/khách</div>
                        </td>
                        <td class="small">
                            @if ($coupon->starts_at) Từ {{ $coupon->starts_at->format('d/m/Y') }}<br>@endif
                            @if ($coupon->expires_at) Đến {{ $coupon->expires_at->format('d/m/Y') }} @else Không giới hạn @endif
                        </td>
                        <td>
                            @if ($coupon->isAvailable())
                            <span class="badge bg-success">Đang chạy</span>
                            @elseif (!$coupon->is_active)
                            <span class="badge bg-secondary">Đã ngưng</span>
                            @else
                            <span class="badge bg-warning text-dark">Hết hạn/hết lượt</span>
                            @endif
                        </td>
                        <td class="text-nowrap">
                            <a href="{{ route('admin.coupons.edit', $coupon->id) }}" class="btn btn-outline-secondary btn-sm rounded-pill">Sửa</a>
                            <form action="{{ route('admin.coupons.destroy', $coupon->id) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Xóa/ngưng mã {{ $coupon->code }}?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm rounded-pill">Xóa</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-muted py-4">Chưa có mã giảm giá nào.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center mt-3">{{ $coupons->links() }}</div>
    </div>
</div>
@endsection
