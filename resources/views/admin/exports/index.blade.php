@extends('layouts.admin')

@section('title', 'Xuất dữ liệu')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1"><i class="bi bi-file-earmark-spreadsheet me-1"></i> Xuất dữ liệu ra Excel</h4>
    <p class="text-muted small mb-0">
        File tải về dạng CSV, mở trực tiếp bằng Excel hoặc Google Sheets — đã xử lý sẵn phông chữ tiếng Việt.
    </p>
</div>

<div class="row g-3 mb-4">
    @php
    $cards = [
        ['label' => 'Đơn hàng', 'value' => number_format($stats['orders'], 0, ',', '.'), 'icon' => '🧾'],
        ['label' => 'Sản phẩm', 'value' => number_format($stats['products'], 0, ',', '.'), 'icon' => '📦'],
        ['label' => 'Khách hàng', 'value' => number_format($stats['customers'], 0, ',', '.'), 'icon' => '👥'],
    ];
    @endphp

    @foreach ($cards as $card)
    <div class="col-6 col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <span style="font-size: 1.8rem;">{{ $card['icon'] }}</span>
                <div>
                    <div class="text-muted small">{{ $card['label'] }}</div>
                    <div class="fw-bold" style="font-size: 1.4rem; line-height: 1.2;">{{ $card['value'] }}</div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="row g-4">

    {{-- ============ ĐƠN HÀNG ============ --}}
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-1">🧾 Đơn hàng</h6>
                <p class="text-muted small mb-3">
                    Mỗi dòng một đơn: khách, trạng thái, tiền hàng, giảm giá, phí ship, địa chỉ nhận.
                </p>

                <form action="{{ route('admin.exports.orders') }}" method="GET" class="row g-2">
                    <div class="col-12">
                        <label class="form-label small mb-1">Trạng thái</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">Tất cả trạng thái</option>
                            @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6">
                        <label class="form-label small mb-1">Từ ngày</label>
                        <input type="date" name="from" class="form-control form-control-sm">
                    </div>

                    <div class="col-6">
                        <label class="form-label small mb-1">Đến ngày</label>
                        <input type="date" name="to" class="form-control form-control-sm">
                    </div>

                    <div class="col-12 mt-3">
                        <button class="btn btn-primary rounded-pill px-4 w-100">
                            <i class="bi bi-download me-1"></i> Tải file đơn hàng
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ============ DOANH THU ============ --}}
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-1">📈 Doanh thu theo ngày</h6>
                <p class="text-muted small mb-3">
                    Gộp theo ngày: số đơn, doanh thu, tổng giảm giá và giá trị trung bình mỗi đơn.
                    Đơn đã hủy không được tính.
                </p>

                <form action="{{ route('admin.exports.revenue') }}" method="GET" class="row g-2">
                    <div class="col-6">
                        <label class="form-label small mb-1">Từ ngày</label>
                        <input type="date" name="from" class="form-control form-control-sm">
                    </div>

                    <div class="col-6">
                        <label class="form-label small mb-1">Đến ngày</label>
                        <input type="date" name="to" class="form-control form-control-sm">
                    </div>

                    <div class="col-12 mt-3">
                        <button class="btn btn-primary rounded-pill px-4 w-100">
                            <i class="bi bi-download me-1"></i> Tải file doanh thu
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ============ TỒN KHO ============ --}}
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body d-flex flex-column">
                <h6 class="fw-bold mb-1">📦 Tồn kho</h6>
                <p class="text-muted small mb-3 flex-grow-1">
                    Toàn bộ sản phẩm kèm số lượng còn, số đã bán và nhãn
                    <strong>HẾT HÀNG</strong> / <strong>Sắp hết</strong> (từ 5 cái trở xuống) —
                    lọc cột đó trong Excel là ra ngay danh sách cần nhập thêm.
                </p>

                <a href="{{ route('admin.exports.inventory') }}" class="btn btn-primary rounded-pill px-4">
                    <i class="bi bi-download me-1"></i> Tải file tồn kho
                </a>
            </div>
        </div>
    </div>

    {{-- ============ KHÁCH HÀNG ============ --}}
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body d-flex flex-column">
                <h6 class="fw-bold mb-1">👥 Khách hàng</h6>
                <p class="text-muted small mb-3 flex-grow-1">
                    Danh sách tài khoản kèm số đơn, tổng chi tiêu, điểm thưởng và hạng thành viên.
                    Sắp xếp theo cột tổng chi tiêu là có ngay nhóm khách VIP.
                </p>

                <a href="{{ route('admin.exports.customers') }}" class="btn btn-primary rounded-pill px-4">
                    <i class="bi bi-download me-1"></i> Tải file khách hàng
                </a>
            </div>
        </div>
    </div>
</div>

{{-- ============ GHI CHÚ KỸ THUẬT ============ --}}
<div class="card border-0 shadow-sm rounded-4 mt-4">
    <div class="card-body">
        <h6 class="fw-bold mb-2"><i class="bi bi-info-circle me-1"></i> Vài điều về file tải về</h6>
        <ul class="text-muted small mb-0" style="line-height: 1.8;">
            <li>
                File có <strong>BOM UTF-8</strong> ở đầu nên Excel hiển thị đúng tiếng Việt,
                không còn cảnh "Ä?á»" chÆ¡i".
            </li>
            <li>
                Dòng đầu là <code>sep=,</code> — chỉ dẫn cho Excel biết dấu phân cách.
                Không có dòng này thì Excel bản tiếng Việt dồn hết dữ liệu vào một cột.
            </li>
            <li>
                Ô bắt đầu bằng <code>=</code> <code>+</code> <code>-</code> <code>@</code>
                được thêm dấu nháy đơn ở đầu để Excel hiểu là chữ, không phải công thức
                (chống <em>CSV injection</em>).
            </li>
            <li>
                Dữ liệu được đẩy xuống theo từng dòng, không dựng cả file trong bộ nhớ —
                xuất bao nhiêu bản ghi cũng không lỗi hết RAM.
            </li>
        </ul>
    </div>
</div>
@endsection
