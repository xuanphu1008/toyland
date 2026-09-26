@extends('layouts.user')

@section('title', 'Đánh giá của tôi')

@section('content')
<style>
    .star-rating { color: var(--tl-amber, #F0A500); letter-spacing: 2px; }
    .star-input { display: flex; flex-direction: row-reverse; justify-content: flex-end; gap: 4px; }
    .star-input input { display: none; }
    .star-input label { font-size: 1.6rem; color: var(--tl-border-strong, #F4C7BB); cursor: pointer; line-height: 1; }
    .star-input input:checked ~ label,
    .star-input label:hover,
    .star-input label:hover ~ label { color: var(--tl-amber, #F0A500); }
</style>

<h4 class="fw-bold text-secondary mb-4">Đánh giá của tôi</h4>

{{-- ===== Chờ đánh giá ===== --}}
<div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
    <h6 class="fw-bold text-secondary mb-3">
        Chờ bạn đánh giá
        <span class="badge bg-warning text-dark ms-1">{{ $pending->count() }}</span>
    </h6>

    @forelse ($pending as $product)
    <div class="border rounded-4 p-3 mb-3">
        <div class="d-flex gap-3 align-items-start">
            @if ($product->image)
            <img src="{{ $product->image_url }}" class="rounded-3" width="70" height="70" style="object-fit: cover;" alt="{{ $product->name }}">
            @else
            <div class="rounded-3 d-flex align-items-center justify-content-center" style="width:70px;height:70px;background:var(--tl-primary-100)"><span style="font-size:1.6rem">🧸</span></div>
            @endif

            <div class="flex-grow-1">
                <a href="{{ route('products.show', $product->id) }}" class="text-decoration-none text-dark fw-semibold">{{ $product->name }}</a>
                <div class="text-muted small mb-2">{{ $product->brand ?: '' }}</div>

                <form action="{{ route('reviews.store', $product->id) }}" method="POST">
                    @csrf
                    <div class="star-input mb-2">
                        @for ($i = 5; $i >= 1; $i--)
                        <input type="radio" name="rating" id="p{{ $product->id }}-star{{ $i }}" value="{{ $i }}" {{ $i === 5 ? 'checked' : '' }}>
                        <label for="p{{ $product->id }}-star{{ $i }}">★</label>
                        @endfor
                    </div>
                    <div class="input-group">
                        <input type="text" name="comment" class="form-control" maxlength="1000" placeholder="Bé nhà bạn có thích món đồ chơi này không?">
                        <button type="submit" class="btn btn-primary">Gửi đánh giá</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @empty
    <div class="text-muted text-center py-3">
        Không còn sản phẩm nào chờ đánh giá. Bạn chỉ đánh giá được sản phẩm thuộc đơn hàng đã giao thành công.
    </div>
    @endforelse
</div>

{{-- ===== Đã đánh giá ===== --}}
<div class="card border-0 shadow-sm rounded-4 p-4">
    <h6 class="fw-bold text-secondary mb-3">
        Đã đánh giá
        <span class="badge bg-success ms-1">{{ $myReviews->count() }}</span>
    </h6>

    @forelse ($myReviews as $review)
    <div class="border-bottom pb-3 mb-3">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <a href="{{ route('products.show', $review->product_id) }}" class="text-decoration-none text-dark fw-semibold">
                    {{ $review->product->name ?? 'Sản phẩm đã bị xóa' }}
                </a>
                <div class="star-rating">{{ $review->stars }}</div>
            </div>
            <div class="text-end">
                <div class="text-muted small">{{ $review->created_at->format('d/m/Y') }}</div>
                <form action="{{ route('reviews.destroy', $review->id) }}" method="POST" onsubmit="return confirm('Xóa đánh giá này?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger rounded-pill mt-1">Xóa</button>
                </form>
            </div>
        </div>

        @if ($review->comment)
        <p class="mb-1 mt-2" style="white-space: pre-line;">{{ $review->comment }}</p>
        @endif

        @unless ($review->is_visible)
        <div class="badge bg-secondary">Đang bị ẩn bởi quản trị viên</div>
        @endunless

        @if ($review->reply)
        <div class="bg-light rounded-3 p-2 mt-2 small">
            <span class="fw-semibold text-primary">Phản hồi từ ToyLand:</span> {{ $review->reply }}
        </div>
        @endif
    </div>
    @empty
    <div class="text-muted text-center py-3">Bạn chưa viết đánh giá nào.</div>
    @endforelse
</div>
@endsection
