{{--
    Thẻ sản phẩm rút gọn cho các khối gợi ý (bán chạy, gợi ý cho bạn, đã xem).
    Biến nhận vào:
      $product  — bắt buộc
      $ribbon   — tùy chọn, mảng ['text' => '...', 'class' => 'tl-ribbon-mint'|'tl-ribbon-sun'|'']
--}}
<div class="col-6 col-lg-3 tl-reveal">
    <div class="product-card card h-100 position-relative">

        {{-- Nhãn giảm giá luôn được ưu tiên hơn ribbon truyền vào:
             con số "-30%" là thứ khách nhìn đầu tiên khi lướt danh sách. --}}
        @if ($product->is_on_sale)
        <span class="tl-ribbon tl-ribbon-sale">−{{ $product->discount_percent }}%</span>
        @elseif (!empty($ribbon))
        <span class="tl-ribbon {{ $ribbon['class'] ?? '' }}">{{ $ribbon['text'] }}</span>
        @endif

        @auth
        @if (!Auth::user()->isAdmin())
        <form action="{{ route('wishlist.toggle', $product->id) }}" method="POST"
              class="tl-wishlist-form position-absolute top-0 end-0 m-2" style="z-index: 3;">
            @csrf
            <button type="submit" class="tl-heart-btn" title="Thêm vào yêu thích">
                {{ in_array($product->id, $wishlistIds ?? []) ? '❤️' : '🤍' }}
            </button>
        </form>
        @endif
        @endauth

        {{-- Nút so sánh: không cần đăng nhập vì danh sách nằm trong session --}}
        <form action="{{ route('compare.toggle', $product->id) }}" method="POST"
              class="tl-compare-form position-absolute top-0 start-0 m-2" style="z-index: 3;">
            @csrf
            <button type="submit"
                    class="tl-compare-btn {{ in_array($product->id, $compareIds ?? [], true) ? 'tl-compare-on' : '' }}"
                    title="So sánh sản phẩm này">
                <i class="bi bi-bar-chart-steps"></i>
            </button>
        </form>

        <a href="{{ route('products.show', $product->id) }}" class="d-block overflow-hidden">
            @if ($product->image)
            <img src="{{ $product->image_url }}" class="card-img-top"
                 style="height: 165px; object-fit: cover;" alt="{{ $product->name }}" loading="lazy">
            @else
            <div class="d-flex align-items-center justify-content-center"
                 style="height: 165px; background: linear-gradient(135deg, var(--tl-primary-100), var(--tl-surface-2));">
                <span style="font-size: 2.4rem">🧸</span>
            </div>
            @endif
        </a>

        <div class="card-body p-3">
            <a href="{{ route('products.show', $product->id) }}" class="text-decoration-none text-dark">
                <h3 class="fw-bold mb-1" style="font-size: .92rem; line-height: 1.35;">
                    {{ \Illuminate\Support\Str::limit($product->name, 46) }}
                </h3>
            </a>

            <div class="small star-rating mb-1">{{ $product->stars }}</div>

            <span class="fw-bold" style="color: var(--tl-danger)">
                {{ number_format($product->effective_price, 0, ',', '.') }} đ
            </span>

            @if ($product->is_on_sale)
            <del class="text-muted ms-1" style="font-size: .8rem;">
                {{ number_format($product->price, 0, ',', '.') }} đ
            </del>
            @endif
        </div>
    </div>
</div>
