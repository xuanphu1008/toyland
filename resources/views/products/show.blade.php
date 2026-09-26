@extends(Auth::check() && !Auth::user()->isAdmin() ? 'layouts.user' : 'layout')

@section('title', $product->name)

@section('content')
<style>
    .star-rating { color: var(--tl-amber, #F0A500); letter-spacing: 2px; }
    .star-input { display: flex; flex-direction: row-reverse; justify-content: flex-end; gap: 4px; }
    .star-input input { display: none; }
    .star-input label { font-size: 1.8rem; color: var(--tl-border-strong, #F4C7BB); cursor: pointer; line-height: 1; }
    .star-input input:checked ~ label,
    .star-input label:hover,
    .star-input label:hover ~ label { color: var(--tl-amber, #F0A500); }
</style>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb small mb-0">
        <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none">Trang chủ</a></li>
        @if ($product->category && $product->category->parent)
        <li class="breadcrumb-item"><a href="{{ route('home', ['category' => $product->category->parent_id]) }}" class="text-decoration-none">{{ $product->category->parent->name }}</a></li>
        @endif
        <li class="breadcrumb-item"><a href="{{ route('home', ['category' => $product->category_id]) }}" class="text-decoration-none">{{ $product->category->name ?? 'Sản phẩm' }}</a></li>
        <li class="breadcrumb-item active text-muted" aria-current="page">{{ $product->name }}</li>
    </ol>
</nav>

<div class="card border-0 shadow-sm rounded-4 p-4">
    <div class="row g-4">
        <div class="col-md-5">
            {{-- ============ THƯ VIỆN ẢNH ============
                 $product->gallery gộp ảnh đại diện + ảnh phụ, đã lọc trùng.
                 Chỉ có một ảnh thì không hiện dãy ảnh nhỏ, tránh thừa. --}}
            @php $gallery = $product->gallery; @endphp

            @if ($gallery)
            <div class="tl-gallery" data-tl-gallery>
                <div class="position-relative">
                    <img src="{{ $gallery[0] }}" class="tl-gallery-main" alt="{{ $product->name }}"
                         data-tl-gallery-main>

                    @if ($product->is_on_sale)
                    <span class="tl-gallery-sale">−{{ $product->discount_percent }}%</span>
                    @endif
                </div>

                @if (count($gallery) > 1)
                <div class="tl-gallery-thumbs">
                    @foreach ($gallery as $i => $url)
                    <button type="button" class="tl-gallery-thumb {{ $i === 0 ? 'tl-active' : '' }}"
                            data-tl-gallery-thumb data-src="{{ $url }}"
                            aria-label="Xem ảnh {{ $i + 1 }}">
                        <img src="{{ $url }}" alt="" loading="lazy">
                    </button>
                    @endforeach
                </div>
                <div class="text-muted text-center small mt-2">
                    {{ count($gallery) }} ảnh · bấm để xem
                </div>
                @endif
            </div>
            @else
            <div class="rounded-4 d-flex align-items-center justify-content-center"
                 style="height: 380px; background: linear-gradient(135deg, var(--tl-primary-100), var(--tl-surface-2));">
                <span style="font-size: 5rem">🧸</span>
            </div>
            @endif
        </div>

        <div class="col-md-7">
            <div class="d-flex flex-wrap gap-2 mb-2">
                <span class="badge bg-info text-dark">{{ $product->category->icon ?? '' }} {{ $product->category->name ?? 'Chưa phân loại' }}</span>
                @if ($product->character_name)
                <a href="{{ route('home', ['character' => $product->character_name]) }}" class="badge rounded-pill text-decoration-none tl-badge-cream">
                    <i class="bi bi-stars me-1"></i>{{ $product->character_name }}
                </a>
                @endif
                @if ($product->is_featured)
                <span class="badge bg-primary">Nổi bật</span>
                @endif
            </div>
            <h3 class="fw-bold text-dark">{{ $product->name }}</h3>
            <p class="text-muted mb-2">
                @if ($product->brand)
                <a href="{{ route('home', ['brand' => $product->brand]) }}" class="text-decoration-none fw-semibold">{{ $product->brand }}</a>
                @else
                Đang cập nhật thương hiệu
                @endif
                @if ($product->sku)<span class="ms-2 small">· SKU {{ $product->sku }}</span>@endif
            </p>

            <div class="mb-3">
                <span class="star-rating fs-5">{{ $product->stars }}</span>
                <span class="fw-semibold">{{ $ratingAvg > 0 ? $ratingAvg : '—' }}</span>
                <a href="#khu-danh-gia" class="text-decoration-none small ms-1">({{ $ratingCount }} đánh giá)</a>
            </div>

            <div class="d-flex align-items-center flex-wrap gap-3 mb-3">
                <span class="fs-3 fw-bold text-danger">
                    {{ number_format($product->effective_price, 0, ',', '.') }} đ
                </span>

                @if ($product->is_on_sale)
                <del class="text-muted fs-6">{{ number_format($product->price, 0, ',', '.') }} đ</del>
                <span class="badge bg-danger">−{{ $product->discount_percent }}%</span>
                @endif

                @if ($product->quantity > 0)
                <span class="badge bg-success">Còn hàng ({{ $product->quantity }})</span>
                @else
                <span class="badge bg-danger">Hết hàng</span>
                @endif
            </div>

            {{-- ============ ĐẾM NGƯỢC FLASH SALE ============
                 Server gửi SỐ GIÂY CÒN LẠI chứ không gửi mốc thời gian tuyệt đối:
                 máy khách đặt sai giờ thì đồng hồ đếm ngược sai theo, có khi âm. --}}
            @if ($product->is_on_sale)
            <div class="tl-flash-box mb-3">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="tl-flash-label">⚡ GIÁ SỐC</span>
                    <span class="fw-semibold">
                        Tiết kiệm {{ number_format($product->sale_saving, 0, ',', '.') }} đ
                    </span>
                </div>

                @if ($product->sale_seconds_left !== null)
                <div class="d-flex align-items-center gap-2 mt-2">
                    <span class="small">Kết thúc sau</span>
                    <span class="tl-countdown-clock" data-tl-countdown="{{ $product->sale_seconds_left }}">
                        --:--:--
                    </span>
                </div>
                <div class="small mt-1" style="opacity: .8;">
                    {{-- Không nhét chữ tiếng Việt vào chuỗi định dạng ngày:
                         dấu \ trong format() chỉ thoát được MỘT BYTE, mà "à" là
                         2 byte UTF-8 nên sẽ ra ký tự lỗi. Tách hẳn ra ngoài. --}}
                    Hết {{ $product->sale_ends_at->format('H:i') }} ngày {{ $product->sale_ends_at->format('d/m') }}
                </div>
                @endif
            </div>
            @endif

            <h6 class="fw-bold text-secondary">Thông tin sản phẩm</h6>
            <div style="max-width: 520px;">
                @include('partials._product-specs', ['product' => $product])
            </div>

            <h6 class="fw-bold text-secondary">Mô tả sản phẩm</h6>
            <p class="text-muted" style="white-space: pre-line;">{{ $product->description ?: 'Đang cập nhật thông tin.' }}</p>

            @auth
            @if (!Auth::user()->isAdmin())
            @if ($product->quantity > 0)
            <form action="{{ route('cart.add', $product->id) }}" method="POST" class="ajax-add-to-cart-form mt-4">
                @csrf
                <label for="quantity" class="form-label fw-semibold">Số lượng</label>
                <div class="tl-qty mb-3">
                    <button type="button" class="tl-qty-btn" data-tl-qty="-1" aria-label="Giảm số lượng">&minus;</button>
                    <input type="number" id="quantity" name="quantity" value="1" min="1" max="{{ $product->quantity }}"
                           class="tl-qty-input" inputmode="numeric" aria-label="Số lượng">
                    <button type="button" class="tl-qty-btn" data-tl-qty="1" aria-label="Tăng số lượng">+</button>
                </div>
                <div class="form-text mb-3">Còn {{ $product->quantity }} sản phẩm trong kho.</div>
                <div class="d-flex gap-2 flex-wrap">
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Thêm vào giỏ hàng</button>
                    <button type="submit" formaction="{{ route('cart.buyNow', $product->id) }}" class="btn btn-danger rounded-pill px-4 buy-now-btn">Mua ngay</button>
                </div>
            </form>
            @else
            <button class="btn btn-secondary rounded-pill px-4 mt-4" disabled>Hết hàng</button>
            @endif

            {{-- Nút thêm/bỏ danh sách yêu thích (tách riêng khỏi form giỏ hàng) --}}
            <form action="{{ route('wishlist.toggle', $product->id) }}" method="POST" class="tl-wishlist-form mt-3">
                @csrf
                <button type="submit" class="btn {{ $isWishlisted ? 'btn-danger' : 'btn-outline-danger' }} rounded-pill px-4">
                    {{ $isWishlisted ? '❤️ Đã yêu thích' : '🤍 Thêm vào yêu thích' }}
                </button>
            </form>
            @endif
            @else
            <a href="{{ route('login') }}" class="btn btn-primary rounded-pill px-4 mt-4">Đăng nhập để mua hàng</a>
            @endauth
        </div>
    </div>
</div>

{{-- ================== ĐÁNH GIÁ SẢN PHẨM ================== --}}
<div id="khu-danh-gia" class="card border-0 shadow-sm rounded-4 p-4 mt-4">
    <h2 class="tl-section-title mb-4"><span>⭐</span> Đánh giá từ khách hàng</h2>

    <div class="row g-4">
        {{-- Cột tổng quan điểm --}}
        <div class="col-md-4">
            <div class="text-center bg-light rounded-4 p-4">
                <div class="display-5 fw-bold text-warning">{{ $ratingAvg > 0 ? $ratingAvg : '—' }}</div>
                <div class="star-rating fs-4">{{ $product->stars }}</div>
                <div class="text-muted small mt-1">{{ $ratingCount }} lượt đánh giá</div>
            </div>

            <div class="mt-3">
                @foreach ($ratingBreakdown as $star => $count)
                @php $percent = $ratingCount > 0 ? round($count * 100 / $ratingCount) : 0; @endphp
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="small text-nowrap" style="width: 42px;">{{ $star }} ★</span>
                    <div class="progress flex-grow-1" style="height: 8px;">
                        <div class="progress-bar bg-warning" style="width: {{ $percent }}%"></div>
                    </div>
                    <span class="small text-muted text-end" style="width: 28px;">{{ $count }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Cột form + danh sách đánh giá --}}
        <div class="col-md-8">
            @auth
            @if (!Auth::user()->isAdmin())
            @if ($canReview)
            <div class="border rounded-4 p-3 mb-4 bg-light">
                <div class="fw-semibold mb-2">
                    {{ $myReview ? 'Sửa lại đánh giá của bạn' : 'Viết đánh giá của bạn' }}
                </div>
                <form action="{{ route('reviews.store', $product->id) }}" method="POST">
                    @csrf
                    <div class="star-input mb-2">
                        @for ($i = 5; $i >= 1; $i--)
                        <input type="radio" name="rating" id="star{{ $i }}" value="{{ $i }}"
                               {{ (int) old('rating', $myReview->rating ?? 5) === $i ? 'checked' : '' }}>
                        <label for="star{{ $i }}" title="{{ $i }} sao">★</label>
                        @endfor
                    </div>
                    <textarea name="comment" rows="3" class="form-control mb-2" maxlength="1000"
                              placeholder="Đồ chơi có bền không? Bé nhà bạn thích chứ? Chia sẻ để mọi người cùng biết nhé...">{{ old('comment', $myReview->comment ?? '') }}</textarea>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary rounded-pill px-4">
                            {{ $myReview ? 'Cập nhật đánh giá' : 'Gửi đánh giá' }}
                        </button>
                        @if ($myReview)
                        <button type="button" class="btn btn-outline-danger rounded-pill px-3"
                                onclick="document.getElementById('delete-my-review').submit()">Xóa đánh giá</button>
                        @endif
                    </div>
                </form>
                @if ($myReview)
                <form id="delete-my-review" action="{{ route('reviews.destroy', $myReview->id) }}" method="POST" class="d-none">
                    @csrf @method('DELETE')
                </form>
                @endif
            </div>
            @else
            <div class="alert alert-light border rounded-4 small mb-4">
                Bạn chỉ có thể đánh giá sản phẩm này sau khi đơn hàng chứa nó được <strong>giao thành công</strong>.
            </div>
            @endif
            @endif
            @else
            <div class="alert alert-light border rounded-4 small mb-4">
                <a href="{{ route('login') }}">Đăng nhập</a> và mua sản phẩm để có thể viết đánh giá.
            </div>
            @endauth

            @forelse ($reviews as $review)
            <div class="border-bottom pb-3 mb-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="fw-semibold">{{ $review->user->name ?? 'Khách hàng' }}</span>
                        <span class="star-rating ms-2">{{ $review->stars }}</span>
                    </div>
                    <span class="text-muted small">{{ $review->created_at->format('d/m/Y') }}</span>
                </div>
                @if ($review->comment)
                <p class="mb-1 mt-2" style="white-space: pre-line;">{{ $review->comment }}</p>
                @endif
                @if ($review->reply)
                <div class="bg-light rounded-3 p-2 mt-2 small">
                    <span class="fw-semibold text-primary">Phản hồi từ ToyLand:</span> {{ $review->reply }}
                </div>
                @endif
            </div>
            @empty
            <div class="tl-empty py-4">
                <span class="tl-empty-icon">💬</span>
                Sản phẩm chưa có đánh giá nào. Hãy là người đầu tiên!
            </div>
            @endforelse
        </div>
    </div>
</div>

{{-- ================== SẢN PHẨM LIÊN QUAN ================== --}}
@if ($related->isNotEmpty())
<div class="mt-4">
    <h2 class="tl-section-title mb-4"><span>🎁</span> Có thể bé cũng thích</h2>
    <div class="row g-3">
        @foreach ($related as $item)
        <div class="col-6 col-lg-3">
            <div class="product-card card h-100">
                <a href="{{ route('products.show', $item->id) }}" class="d-block overflow-hidden">
                    @if ($item->image)
                    <img src="{{ $item->image_url }}" class="card-img-top rounded-top-4" style="height: 150px; object-fit: cover;" alt="{{ $item->name }}">
                    @else
                    <div class="d-flex align-items-center justify-content-center" style="height: 150px; background: linear-gradient(135deg, var(--tl-primary-100), var(--tl-surface-2));"><span style="font-size:2.2rem">🧸</span></div>
                    @endif
                </a>
                <div class="card-body">
                    <a href="{{ route('products.show', $item->id) }}" class="text-decoration-none text-dark">
                        <h6 class="fw-bold small mb-1">{{ $item->name }}</h6>
                    </a>
                    <span class="text-danger fw-bold small">{{ number_format($item->price, 0, ',', '.') }} đ</span>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

@endsection
