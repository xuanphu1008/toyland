{{--
    Bộ lọc dùng chung cho cả hai trang tài chính.
    Hai trang lọc trên cùng một tập dữ liệu nên phải dùng chung đúng một bộ ô nhập,
    nếu không người dùng lọc ở trang này rồi chuyển sang trang kia sẽ thấy số khác nhau.

    Biến cần truyền vào: $action, $filters, $statuses, $methods, $withSort
--}}
<form action="{{ $action }}" method="GET" class="row g-3 mb-4 bg-light p-3 rounded-3">
    <div class="col-md-4">
        <label class="form-label small text-muted mb-1">Tìm đơn hàng</label>
        <input type="text" name="search" class="form-control"
               placeholder="Mã đơn, tên hoặc số điện thoại"
               value="{{ $filters['search'] ?? '' }}">
    </div>
    <div class="col-md-4">
        <label class="form-label small text-muted mb-1">Từ ngày tạo đơn</label>
        <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
    </div>
    <div class="col-md-4">
        <label class="form-label small text-muted mb-1">Đến ngày tạo đơn</label>
        <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
    </div>

    <div class="col-md-3">
        <label class="form-label small text-muted mb-1">Số tiền từ (đ)</label>
        <input type="number" name="min_amount" class="form-control" min="0" step="1000"
               placeholder="Không giới hạn" value="{{ $filters['min_amount'] ?? '' }}">
    </div>
    <div class="col-md-3">
        <label class="form-label small text-muted mb-1">Số tiền đến (đ)</label>
        <input type="number" name="max_amount" class="form-control" min="0" step="1000"
               placeholder="Không giới hạn" value="{{ $filters['max_amount'] ?? '' }}">
    </div>
    <div class="col-md-3">
        <label class="form-label small text-muted mb-1">Phương thức</label>
        <select name="gateway" class="form-select">
            <option value="">Tất cả</option>
            @foreach ($methods as $key => $label)
            <option value="{{ $key }}" {{ ($filters['gateway'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label small text-muted mb-1">Trạng thái thanh toán</label>
        <select name="payment_status" class="form-select">
            <option value="">Tất cả</option>
            @foreach ($statuses as $key => $label)
            <option value="{{ $key }}" {{ ($filters['payment_status'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    @if ($withSort)
    <div class="col-md-3">
        <label class="form-label small text-muted mb-1">Sắp xếp</label>
        <select name="sort" class="form-select">
            @foreach ([
                'newest' => 'Mới nhất',
                'oldest' => 'Cũ nhất',
                'amount_desc' => 'Số tiền giảm dần',
                'amount_asc' => 'Số tiền tăng dần',
            ] as $key => $label)
            <option value="{{ $key }}" {{ ($filters['sort'] ?? 'newest') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    @endif

    <div class="col-12 d-flex gap-2">
        <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-funnel me-1"></i> Áp dụng bộ lọc
        </button>
        <a href="{{ $action }}" class="btn btn-outline-secondary">Xóa bộ lọc</a>
    </div>
</form>
