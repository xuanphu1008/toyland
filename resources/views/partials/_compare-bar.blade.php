{{--
    Thanh so sánh nổi ở đáy màn hình.

    Chỉ hiện khi khách đã chọn ít nhất một món ($compareCount do View Composer
    '*' trong AppServiceProvider chia sẻ, đọc từ session nên không tốn truy vấn).

    Đặt ở đáy thay vì trên navbar vì khách vừa lướt danh sách sản phẩm vừa chọn -
    thanh ở đáy luôn trong tầm mắt mà không che mất hàng hóa.
--}}
@if (($compareCount ?? 0) > 0)
<div class="tl-compare-bar" data-tl-compare-bar>
    <span class="tl-compare-bar-icon">⚖️</span>

    <span class="flex-grow-1">
        Đang so sánh
        <strong data-tl-compare-count>{{ $compareCount }}</strong>/{{ \App\Http\Controllers\CompareController::MAX_ITEMS }}
        sản phẩm
    </span>

    <a href="{{ route('compare.index') }}" class="btn btn-sm btn-primary rounded-pill px-3">
        Xem bảng so sánh
    </a>

    <form action="{{ route('compare.clear') }}" method="POST" class="m-0">
        @csrf @method('DELETE')
        <button class="btn btn-sm btn-link text-muted p-1" title="Xóa danh sách so sánh">
            <i class="bi bi-x-lg"></i>
        </button>
    </form>
</div>
@endif
