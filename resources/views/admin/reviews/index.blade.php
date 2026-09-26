@extends('layouts.admin')

@section('title', 'Kiểm duyệt đánh giá')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h5 class="text-secondary fw-bold mb-0">Đánh giá sản phẩm</h5>
    <div class="d-flex gap-2">
        <span class="badge bg-primary fs-6 px-3 py-2">Tổng: {{ $stats['total'] }}</span>
        <span class="badge bg-warning text-dark fs-6 px-3 py-2">Điểm TB: {{ $stats['average'] ?: '—' }} ★</span>
        <span class="badge bg-secondary fs-6 px-3 py-2">Đang ẩn: {{ $stats['hidden'] }}</span>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 p-3">
    <div class="card-body">
        <form action="{{ route('admin.reviews.index') }}" method="GET" class="row g-2 mb-4 bg-light p-3 rounded-3">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control" placeholder="Tìm theo nội dung, tên sản phẩm hoặc khách hàng..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="rating" class="form-select">
                    <option value="">-- Tất cả số sao --</option>
                    @for ($i = 5; $i >= 1; $i--)
                    <option value="{{ $i }}" {{ (string) request('rating') === (string) $i ? 'selected' : '' }}>{{ $i }} sao</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-2">
                <select name="visible" class="form-select">
                    <option value="">-- Hiển thị --</option>
                    <option value="1" {{ request('visible') === '1' ? 'selected' : '' }}>Đang hiện</option>
                    <option value="0" {{ request('visible') === '0' ? 'selected' : '' }}>Đang ẩn</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-secondary w-100">Lọc</button>
            </div>
        </form>

        @forelse ($reviews as $review)
        <div class="border rounded-4 p-3 mb-3 {{ $review->is_visible ? '' : 'bg-light border-secondary' }}">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <span class="fw-bold">{{ $review->user->name ?? 'Khách đã xóa' }}</span>
                    <span class="star-rating ms-2">{{ $review->stars }}</span>
                    @unless ($review->is_visible)
                    <span class="badge bg-secondary ms-2">Đang ẩn</span>
                    @endunless
                    <div class="small text-muted mt-1">
                        Sản phẩm:
                        <a href="{{ route('products.show', $review->product_id) }}" target="_blank" class="text-decoration-none">
                            {{ $review->product->name ?? 'Sản phẩm đã xóa' }}
                        </a>
                        · {{ $review->created_at->format('d/m/Y H:i') }}
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <form action="{{ route('admin.reviews.toggle', $review->id) }}" method="POST">
                        @csrf @method('PATCH')
                        <button class="btn btn-sm rounded-pill px-3 {{ $review->is_visible ? 'btn-outline-secondary' : 'btn-outline-success' }}">
                            {{ $review->is_visible ? 'Ẩn' : 'Hiện lại' }}
                        </button>
                    </form>
                    <button class="btn btn-sm btn-outline-primary rounded-pill px-3" data-bs-toggle="collapse" data-bs-target="#reply-{{ $review->id }}">
                        {{ $review->reply ? 'Sửa phản hồi' : 'Phản hồi' }}
                    </button>
                    <form action="{{ route('admin.reviews.destroy', $review->id) }}" method="POST" onsubmit="return confirm('Xóa vĩnh viễn đánh giá này?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger rounded-pill px-3">Xóa</button>
                    </form>
                </div>
            </div>

            @if ($review->comment)
            <p class="mb-0 mt-2" style="white-space: pre-line;">{{ $review->comment }}</p>
            @endif

            @if ($review->reply)
            <div class="bg-white border rounded-3 p-2 mt-2 small">
                <span class="fw-semibold text-primary">Phản hồi của shop</span>
                <span class="text-muted">({{ optional($review->replied_at)->format('d/m/Y H:i') }})</span>:
                {{ $review->reply }}
            </div>
            @endif

            <div class="collapse mt-2" id="reply-{{ $review->id }}">
                <form action="{{ route('admin.reviews.reply', $review->id) }}" method="POST">
                    @csrf
                    <div class="input-group">
                        <input type="text" name="reply" class="form-control" maxlength="1000" value="{{ $review->reply }}" placeholder="Cảm ơn bạn đã tin tưởng ToyLand..." required>
                        <button class="btn btn-primary">Gửi phản hồi</button>
                    </div>
                </form>
            </div>
        </div>
        @empty
        <div class="tl-empty ">Chưa có đánh giá nào khớp với bộ lọc.</div>
        @endforelse

        <div class="d-flex justify-content-center mt-3">
            {{ $reviews->links() }}
        </div>
    </div>
</div>
@endsection
