@extends(Auth::check() && !Auth::user()->isAdmin() ? 'layouts.user' : 'layout')

@section('title', 'ToyLand - Thế giới đồ chơi cho bé')

@section('content')

@php
/**
 * Chọn biểu tượng cho từng danh mục theo từ khóa trong tên.
 * Không khớp từ khóa nào thì lấy theo thứ tự trong danh sách dự phòng.
 */
$catIcon = function ($name, $index) {
    $map = [
        'lắp ráp' => '🧩', 'mô hình' => '🧩',
        'búp bê' => '🎀', 'nhà búp bê' => '🎀',
        'stem' => '🔬', 'giáo dục' => '🔬', 'khoa học' => '🔬',
        'xe' => '🏎️', 'điều khiển' => '🏎️',
        'sơ sinh' => '🍼', 'mầm non' => '🍼',
        'vận động' => '⚽', 'ngoài trời' => '⚽',
        'thú bông' => '🧸', 'nhồi bông' => '🧸',
        'âm nhạc' => '🎹', 'nghệ thuật' => '🎨',
    ];

    $lower = mb_strtolower($name);
    foreach ($map as $key => $icon) {
        if (mb_strpos($lower, $key) !== false) return $icon;
    }

    $fallback = ['🧸', '🚂', '🪁', '🎠', '🪀', '🎲'];
    return $fallback[$index % count($fallback)];
};
@endphp

{{-- ============================================================
     HERO
     ============================================================ --}}
@unless ($isFiltering)
<section class="tl-hero mb-5 tl-reveal">
    <div class="row align-items-center g-4">
        <div class="col-lg-6">
            <span class="tl-hero-badge mb-3">
                <span>🎉</span> Hơn 500 món đồ chơi chính hãng
            </span>

            <h1 class="mb-3">
                Mỗi món đồ chơi<br>
                là một <span style="color:#FFF7BC">thế giới</span> mới<br>
                cho bé khám phá
            </h1>

            <p class="mb-4" style="max-width: 460px;">
                Đồ chơi được chọn theo đúng độ tuổi và kỹ năng bé đang phát triển.
                An toàn cho bé, yên tâm cho bố mẹ.
            </p>

            <div class="d-flex flex-wrap gap-2 mb-4">
                <a href="#tl-danh-sach" class="btn btn-light btn-lg">
                    <i class="bi bi-bag-heart me-1"></i> Khám phá ngay
                </a>
                <a href="{{ route('home', ['sort' => 'best_selling']) }}" class="btn btn-outline-light btn-lg">
                    <i class="bi bi-fire me-1"></i> Xem hàng bán chạy
                </a>
            </div>

            <div class="row g-2" style="max-width: 440px;">
                <div class="col-4">
                    <div class="tl-hero-stat">
                        <div class="tl-num">0-14</div>
                        <div class="tl-lbl">Tuổi phù hợp</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="tl-hero-stat">
                        <div class="tl-num">100%</div>
                        <div class="tl-lbl">Chính hãng</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="tl-hero-stat">
                        <div class="tl-num">24h</div>
                        <div class="tl-lbl">Giao nội thành</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Minh họa vẽ bằng SVG: khối xếp hình, tên lửa, bóng, ngôi sao --}}
        <div class="col-lg-6 d-none d-lg-block">
            <svg viewBox="0 0 520 400" width="100%" height="auto" role="img"
                 aria-label="Minh họa các món đồ chơi: khối xếp hình, tên lửa, quả bóng và xe đồ chơi">

                <!-- vòng tròn nền -->
                <circle cx="280" cy="200" r="165" fill="rgba(255,255,255,.14)"/>
                <circle cx="280" cy="200" r="130" fill="rgba(255,255,255,.11)"/>

                <!-- ngôi sao nhỏ rải rác -->
                <g fill="#FFF7BC" class="tl-float">
                    <path d="M70 70 l7 15 16 2 -12 11 3 16 -14 -8 -14 8 3 -16 -12 -11 16 -2z"/>
                </g>
                <g fill="#FFF7BC" class="tl-float-3" opacity=".9">
                    <path d="M460 300 l6 12 13 2 -10 9 2 13 -11 -6 -11 6 2 -13 -10 -9 13 -2z"/>
                </g>
                <circle cx="440" cy="90" r="7" fill="#FFFFFF" opacity=".75" class="tl-float-2"/>
                <circle cx="95" cy="320" r="9" fill="#C0EDA6" opacity=".8" class="tl-float"/>

                <!-- tháp khối xếp hình -->
                <g class="tl-float">
                    <rect x="180" y="250" width="86" height="60" rx="16" fill="#FFF7BC"/>
                    <circle cx="205" cy="280" r="9" fill="rgba(224,49,49,.28)"/>
                    <circle cx="241" cy="280" r="9" fill="rgba(224,49,49,.28)"/>

                    <rect x="194" y="192" width="70" height="56" rx="15" fill="#C0EDA6"/>
                    <circle cx="229" cy="220" r="10" fill="rgba(53,122,34,.30)"/>

                    <rect x="206" y="140" width="52" height="50" rx="14" fill="#FFFFFF"/>
                    <rect x="222" y="158" width="20" height="14" rx="5" fill="rgba(224,49,49,.28)"/>
                </g>

                <!-- tên lửa -->
                <g class="tl-float-2">
                    <path d="M360 120 c22 0 40 30 40 62 0 20 -8 38 -18 48 l-44 0 c-10 -10 -18 -28 -18 -48 0 -32 18 -62 40 -62z" fill="#FFFFFF"/>
                    <circle cx="360" cy="168" r="15" fill="#E03131"/>
                    <circle cx="360" cy="168" r="8" fill="#FF8080"/>
                    <path d="M320 208 l-18 30 32 -10z" fill="#C0EDA6"/>
                    <path d="M400 208 l18 30 -32 -10z" fill="#C0EDA6"/>
                    <path d="M346 236 l14 34 14 -34z" fill="#FFC14D"/>
                </g>

                <!-- quả bóng -->
                <g class="tl-float-3">
                    <circle cx="132" cy="182" r="40" fill="#C0EDA6"/>
                    <path d="M92 182 a40 40 0 0 0 80 0" fill="#FFFFFF" opacity=".35"/>
                    <circle cx="132" cy="182" r="40" fill="none" stroke="rgba(255,255,255,.75)" stroke-width="4"/>
                    <path d="M132 142 v80 M92 182 h80" stroke="rgba(255,255,255,.75)" stroke-width="4" fill="none"/>
                </g>

                <!-- xe đồ chơi -->
                <g class="tl-float">
                    <rect x="330" y="288" width="110" height="38" rx="14" fill="#FFF7BC"/>
                    <path d="M352 288 l12 -22 46 0 12 22z" fill="#FFC14D"/>
                    <circle cx="358" cy="330" r="14" fill="#5C2E2E"/>
                    <circle cx="358" cy="330" r="6" fill="#FFF7BC"/>
                    <circle cx="414" cy="330" r="14" fill="#5C2E2E"/>
                    <circle cx="414" cy="330" r="6" fill="#FFF7BC"/>
                </g>
            </svg>
        </div>
    </div>
</section>
@endunless

{{-- ============================================================
     THANH TÌM KIẾM & BỘ LỌC
     (trang "Danh mục" riêng đã bỏ - toàn bộ cây danh mục nằm ở đây)
     ============================================================ --}}
@php
    // Danh mục đang chọn, để biết nên hiện danh sách con nào
    $activeCategoryId = (int) request('category');
    $activeCategory = $categories->firstWhere('id', $activeCategoryId);
    $activeRoot = $activeCategory ? ($activeCategory->parent ?: $activeCategory) : null;
@endphp

<form action="{{ route('home') }}" method="GET" class="card p-3 p-md-4 mb-4 tl-reveal" id="tl-filter-form">
    <div class="row g-3 align-items-end">
        <div class="col-lg-6">
            <label class="form-label"><i class="bi bi-search me-1"></i> Bé đang tìm món gì?</label>
            <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-lg"
                   placeholder="Tên đồ chơi, thương hiệu, nhân vật, mã sản phẩm...">
        </div>

        <div class="col-lg-4 col-md-8">
            <label class="form-label"><i class="bi bi-sort-down me-1"></i> Sắp xếp</label>
            <select name="sort" class="form-select form-select-lg">
                @foreach ($sorts as $value => $label)
                <option value="{{ $value }}" {{ request('sort', 'newest') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-lg-2 col-md-4">
            <button type="submit" class="btn btn-primary btn-lg w-100">Tìm kiếm</button>
        </div>
    </div>

    {{-- ====== DÃY DANH MỤC CHA (bấm là lọc luôn) ====== --}}
    <div class="mt-4">
        <label class="form-label"><i class="bi bi-grid me-1"></i> Chọn nhóm đồ chơi</label>
        <div class="tl-cat-chips">
            <a href="{{ route('home', array_merge(request()->except(['category', 'page']), [])) }}"
               class="tl-cat-chip {{ $activeCategoryId ? '' : 'active' }}">Tất cả</a>

            @foreach ($categoryTree as $root)
            <a href="{{ route('home', array_merge(request()->except(['category', 'page']), ['category' => $root->id])) }}"
               class="tl-cat-chip {{ $activeRoot && $activeRoot->id === $root->id ? 'active' : '' }}">
                <span class="tl-cat-chip-icon">{{ $root->icon }}</span> {{ $root->name }}
            </a>
            @endforeach
        </div>
    </div>

    {{-- ====== DANH MỤC CON của nhóm đang chọn ====== --}}
    @if ($activeRoot && $activeRoot->children->count())
    <div class="mt-3">
        <label class="form-label small text-muted mb-2">Lọc chi tiết trong “{{ $activeRoot->name }}”</label>
        <div class="tl-cat-chips">
            @foreach ($activeRoot->children as $child)
            <a href="{{ route('home', array_merge(request()->except(['category', 'page']), ['category' => $child->id])) }}"
               class="tl-cat-chip tl-cat-chip-sm {{ $activeCategoryId === $child->id ? 'active' : '' }}">{{ $child->name }}</a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Giữ lại danh mục đang chọn khi bấm nút "Tìm kiếm" --}}
    <input type="hidden" name="category" value="{{ request('category') }}">

    {{-- ====== BỘ LỌC CHI TIẾT ====== --}}
    <div class="row g-3 mt-1 pt-3 border-top">
        <div class="col-lg-3 col-md-4 col-6">
            <label class="form-label"><i class="bi bi-cake2 me-1"></i> Độ tuổi của bé</label>
            <select name="age" class="form-select">
                <option value="">Mọi độ tuổi</option>
                @foreach ($ageBuckets as $key => $bucket)
                <option value="{{ $key }}" {{ request('age') === $key ? 'selected' : '' }}>{{ $bucket['label'] }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-lg-3 col-md-4 col-6">
            <label class="form-label"><i class="bi bi-award me-1"></i> Hãng / Thương hiệu</label>
            <select name="brand" class="form-select">
                <option value="">Tất cả thương hiệu</option>
                @foreach ($brands as $brand)
                <option value="{{ $brand }}" {{ request('brand') === $brand ? 'selected' : '' }}>{{ $brand }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-lg-3 col-md-4 col-6">
            <label class="form-label"><i class="bi bi-stars me-1"></i> Nhân vật bé thích</label>
            <select name="character" class="form-select">
                <option value="">Tất cả nhân vật</option>
                @foreach ($characters as $character)
                <option value="{{ $character }}" {{ request('character') === $character ? 'selected' : '' }}>{{ $character }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-lg-3 col-md-4 col-6">
            <label class="form-label"><i class="bi bi-gender-ambiguous me-1"></i> Phù hợp với</label>
            <select name="gender" class="form-select">
                <option value="">Bé trai &amp; bé gái</option>
                @foreach ($genders as $value => $label)
                <option value="{{ $value }}" {{ request('gender') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-lg-3 col-md-4 col-6">
            <label class="form-label">Giá từ (đ)</label>
            <input type="number" name="price_min" min="0" step="10000" value="{{ request('price_min') }}" class="form-control" placeholder="0">
        </div>

        <div class="col-lg-3 col-md-4 col-6">
            <label class="form-label">Đến (đ)</label>
            <input type="number" name="price_max" min="0" step="10000" value="{{ request('price_max') }}" class="form-control" placeholder="Không giới hạn">
        </div>

        <div class="col-lg-3 col-md-4 col-6 d-flex align-items-end">
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" name="in_stock" value="1" id="inStock" {{ request('in_stock') === '1' ? 'checked' : '' }}>
                <label class="form-check-label" for="inStock">Chỉ hiện hàng còn</label>
            </div>
        </div>

        <div class="col-lg-3 col-md-4 col-6 d-flex align-items-end gap-2">
            <button type="submit" class="btn btn-primary rounded-pill px-4 mb-2">Áp dụng</button>
            @if ($isFiltering)
            <a href="{{ route('home') }}" class="btn btn-outline-secondary rounded-pill px-3 mb-2">Xóa lọc</a>
            @endif
        </div>
    </div>
</form>

{{-- ====== CÁC ĐIỀU KIỆN ĐANG LỌC (bấm X để gỡ từng cái) ====== --}}
@if ($isFiltering)
@php
    $chips = [];
    if (request('q')) $chips['q'] = 'Từ khóa: “' . request('q') . '”';
    if ($activeCategory) $chips['category'] = 'Danh mục: ' . $activeCategory->name;
    if (request('age')) $chips['age'] = 'Tuổi: ' . ($ageBuckets[request('age')]['label'] ?? '');
    if (request('brand')) $chips['brand'] = 'Hãng: ' . request('brand');
    if (request('character')) $chips['character'] = 'Nhân vật: ' . request('character');
    if (request('gender')) $chips['gender'] = 'Cho: ' . ($genders[request('gender')] ?? '');
    if (request('price_min')) $chips['price_min'] = 'Từ ' . number_format((int) request('price_min'), 0, ',', '.') . 'đ';
    if (request('price_max')) $chips['price_max'] = 'Đến ' . number_format((int) request('price_max'), 0, ',', '.') . 'đ';
    if (request('in_stock')) $chips['in_stock'] = 'Còn hàng';
@endphp
<div class="d-flex flex-wrap align-items-center gap-2 mb-4">
    <span class="text-muted small">Đang lọc:</span>
    @foreach ($chips as $key => $label)
    <a href="{{ route('home', request()->except([$key, 'page'])) }}" class="tl-filter-chip">
        {{ $label }} <i class="bi bi-x-lg ms-1"></i>
    </a>
    @endforeach
    <span class="text-muted small ms-auto">{{ $products->total() }} sản phẩm</span>
</div>
@endif

{{-- ============================================================
     DANH MỤC NỔI BẬT
     ============================================================ --}}
@unless ($isFiltering)
<section class="mb-5">
    <div class="d-flex justify-content-between align-items-end mb-4">
        <h2 class="tl-section-title"><span>🎪</span> Bé muốn chơi gì hôm nay?</h2>
        <a href="{{ route('home') }}" class="small text-decoration-none fw-semibold">Xem tất cả →</a>
    </div>

    <div class="row row-cols-2 row-cols-sm-3 row-cols-lg-5 g-3">
        @foreach ($categoryTree as $index => $category)
        <div class="col tl-reveal" data-tl-delay="{{ $index * 50 }}">
            <a href="{{ route('home', ['category' => $category->id]) }}" class="text-decoration-none">
                <div class="category-card h-100 text-center p-3">
                    <div class="tl-cat-bubble tl-bubble-{{ ($index % 6) + 1 }}">{{ $category->icon ?: $catIcon($category->name, $index) }}</div>
                    <div class="fw-bold small text-dark">{{ $category->name }}</div>
                    <div class="text-muted" style="font-size: .72rem;">{{ $category->children->count() }} nhóm nhỏ</div>
                </div>
            </a>
        </div>
        @endforeach
    </div>
</section>
@endunless

{{-- ============================================================
     FLASH SALE - GIÁ SỐC CÓ HẠN
     Đồng hồ đếm ngược lấy SỐ GIÂY CÒN LẠI từ server, không lấy mốc thời gian
     tuyệt đối rồi để JavaScript so với đồng hồ máy khách (máy đặt sai giờ là
     đếm ngược sai theo).
     ============================================================ --}}
@if (!empty($flashSale) && $flashSale->isNotEmpty())
<section class="mb-5">
    <div class="tl-flash-header">
        <div>
            <h2 class="tl-section-title mb-0"><span>⚡</span> Giá sốc hôm nay</h2>
            <p class="text-muted small mb-0 mt-2">Số lượng có hạn, hết giờ là về giá cũ</p>
        </div>

        @php $soonest = $flashSale->whereNotNull('sale_ends_at')->sortBy('sale_ends_at')->first(); @endphp

        @if ($soonest && $soonest->sale_seconds_left !== null)
        <div class="text-end">
            <div class="small text-muted mb-1">Đợt gần nhất kết thúc sau</div>
            <span class="tl-countdown-clock" data-tl-countdown="{{ $soonest->sale_seconds_left }}">--:--:--</span>
        </div>
        @endif
    </div>

    <div class="row g-3">
        @foreach ($flashSale as $product)
        @include('partials._mini-product', ['product' => $product, 'ribbon' => null])
        @endforeach
    </div>
</section>
@endif

{{-- ============================================================
     GỢI Ý RIÊNG CHO BÉ ĐANG ĐƯỢC CHỌN
     Khối này chấm điểm theo ĐẶC ĐIỂM CỦA BÉ (tuổi, giới tính, nhân vật yêu
     thích) nên có kết quả ngay cả khi tài khoản chưa mua món nào - khác hẳn
     khối "Gợi ý riêng cho bạn" vốn cần lịch sử mua hàng mới chạy được.
     ============================================================ --}}
@if (!empty($childPicks) && $childPicks->isNotEmpty() && !empty($activeChild))
<section class="mb-5">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
        <div>
            <h2 class="tl-section-title">
                <span>{{ $activeChild->avatar ?: '🧒' }}</span> Hợp với {{ $activeChild->name }}
            </h2>
            <p class="text-muted small mb-0 mt-2">
                Chọn theo độ tuổi {{ $activeChild->age_label }}
                @if ($activeChild->interest_list)
                    và nhân vật bé thích: {{ implode(', ', $activeChild->interest_list) }}
                @endif
            </p>
        </div>

        <a href="{{ route('home', $activeChild->filterQuery()) }}" class="small text-decoration-none fw-semibold">
            Xem tất cả đồ hợp tuổi →
        </a>
    </div>

    @if ($activeChild->is_birthday_soon)
    <div class="alert tl-alert-birthday d-flex flex-wrap align-items-center gap-2 mb-3">
        <span style="font-size: 1.3rem;">🎂</span>
        <div class="flex-grow-1">
            <strong>{{ $activeChild->name }} {{ mb_strtolower($activeChild->countdown_label) }}</strong>
            — sắp tròn {{ $activeChild->turning_age }} tuổi.
        </div>
        <a href="{{ route('children.birthdays') }}" class="btn btn-sm btn-primary rounded-pill px-3">
            Xem gợi ý quà sinh nhật
        </a>
    </div>
    @endif

    <div class="row g-3">
        @foreach ($childPicks as $product)
        @include('partials._mini-product', ['product' => $product, 'ribbon' => null])
        @endforeach
    </div>
</section>
@endif

{{-- ============================================================
     SẢN PHẨM BÁN CHẠY
     ============================================================ --}}
@if ($bestSellers->isNotEmpty())
<section class="mb-5">
    <div class="d-flex justify-content-between align-items-end mb-4">
        <h2 class="tl-section-title"><span>🔥</span> Bé nào cũng thích</h2>
        <a href="{{ route('home', ['sort' => 'best_selling']) }}" class="small text-decoration-none fw-semibold">Xem tất cả →</a>
    </div>

    <div class="row g-3">
        @foreach ($bestSellers as $product)
        @include('partials._mini-product', [
            'product' => $product,
            'ribbon' => ['text' => '🔥 Đã bán ' . (int) $product->sold_quantity, 'class' => ''],
        ])
        @endforeach
    </div>
</section>
@endif

{{-- ============================================================
     GỢI Ý CHO BẠN
     ============================================================ --}}
@if ($recommended->isNotEmpty())
<section class="mb-5">
    <div class="mb-4">
        <h2 class="tl-section-title"><span>✨</span> Gợi ý riêng cho bạn</h2>
        <p class="text-muted small mb-0 mt-2">Dựa trên những món đồ chơi bạn đã xem và đã mua</p>
    </div>

    <div class="row g-3">
        @foreach ($recommended as $product)
        @include('partials._mini-product', ['product' => $product, 'ribbon' => null])
        @endforeach
    </div>
</section>
@endif

{{-- ============================================================
     ĐÃ XEM GẦN ĐÂY
     ============================================================ --}}
@if ($recentlyViewed->isNotEmpty())
<section class="mb-5">
    <h2 class="tl-section-title mb-4"><span>🕘</span> Bạn vừa xem</h2>
    <div class="row g-3">
        @foreach ($recentlyViewed as $product)
        @include('partials._mini-product', ['product' => $product, 'ribbon' => null])
        @endforeach
    </div>
</section>
@endif

{{-- ============================================================
     DANH SÁCH SẢN PHẨM
     ============================================================ --}}
<section id="tl-danh-sach" class="mb-5">
    <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-2">
        <h2 class="tl-section-title">
            <span>{{ $isFiltering ? '🔍' : '🛍️' }}</span>
            {{ $isFiltering ? 'Kết quả tìm kiếm' : 'Tất cả đồ chơi' }}
        </h2>
        <span class="badge bg-light">{{ $products->total() }} sản phẩm</span>
    </div>

    <div class="row g-4">
        @forelse ($products as $product)
        <div class="col-sm-6 col-lg-4 col-xl-3 tl-reveal">
            <div class="product-card card h-100 position-relative">

                @if ($product->quantity <= 0)
                <span class="tl-ribbon" style="background: var(--tl-border-strong); color: var(--tl-ink)">Hết hàng</span>
                @elseif ((int) $product->sold_quantity > 5)
                <span class="tl-ribbon">🔥 Bán chạy</span>
                @elseif ($product->created_at->gt(now()->subDays(14)))
                <span class="tl-ribbon tl-ribbon-mint">✨ Mới về</span>
                @endif

                {{-- Nút yêu thích --}}
                @auth
                @if (!Auth::user()->isAdmin())
                <form action="{{ route('wishlist.toggle', $product->id) }}" method="POST"
                      class="tl-wishlist-form position-absolute top-0 end-0 m-2" style="z-index: 3;">
                    @csrf
                    <button type="submit" class="tl-heart-btn"
                            title="{{ in_array($product->id, $wishlistIds) ? 'Bỏ khỏi yêu thích' : 'Thêm vào yêu thích' }}">
                        {{ in_array($product->id, $wishlistIds) ? '❤️' : '🤍' }}
                    </button>
                </form>
                @endif
                @endauth

                <a href="{{ route('products.show', $product->id) }}" class="text-decoration-none d-block overflow-hidden">
                    @if ($product->image)
                    <img src="{{ $product->image_url }}" class="card-img-top"
                         style="height: 210px; object-fit: cover;" alt="{{ $product->name }}" loading="lazy">
                    @else
                    <div class="d-flex align-items-center justify-content-center"
                         style="height: 210px; background: linear-gradient(135deg, var(--tl-primary-100), var(--tl-surface-2));">
                        <span style="font-size: 3rem">🧸</span>
                    </div>
                    @endif
                </a>

                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <span class="badge bg-light">{{ $product->category->name ?? 'Chưa phân loại' }}</span>
                        @if ($product->age_label)
                        <span class="badge bg-success">{{ $product->age_label }}</span>
                        @endif
                    </div>

                    <a href="{{ route('products.show', $product->id) }}" class="text-decoration-none text-dark">
                        <h3 class="fw-bold mb-1" style="font-size: 1rem; line-height: 1.35;">{{ $product->name }}</h3>
                    </a>

                    <div class="small text-muted mb-2">
                        {{ $product->brand ?: 'Đang cập nhật' }}
                        @if ($product->character_name)
                        <span class="ms-1" style="color: var(--tl-amber);">· {{ $product->character_name }}</span>
                        @endif
                    </div>

                    <div class="small mb-3">
                        <span class="star-rating">{{ $product->stars }}</span>
                        <span class="text-muted">({{ $product->visible_reviews_count ?? 0 }})</span>
                    </div>

                    <div class="mt-auto">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold" style="font-size: 1.15rem; color: var(--tl-danger)">
                                {{ number_format($product->price, 0, ',', '.') }} đ
                            </span>
                            @if ($product->quantity > 0)
                            <span class="small text-muted">Còn {{ $product->quantity }}</span>
                            @endif
                        </div>

                        @auth
                        @if (!Auth::user()->isAdmin() && $product->quantity > 0 && $product->has_variants)
                        <a href="{{ route('products.show', $product->id) }}" class="btn btn-primary w-100">
                            <i class="bi bi-sliders me-1"></i> Chọn phân loại
                        </a>
                        @elseif (!Auth::user()->isAdmin() && $product->quantity > 0)
                        <form action="{{ route('cart.add', $product->id) }}" method="POST" class="ajax-add-to-cart-form">
                            @csrf
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-cart-plus me-1"></i> Thêm vào giỏ
                            </button>
                        </form>
                        @elseif ($product->quantity <= 0)
                        <button class="btn btn-outline-secondary w-100" disabled>Tạm hết hàng</button>
                        @endif
                        @else
                        <a href="{{ route('login') }}" class="btn btn-outline-primary w-100">Đăng nhập để mua</a>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="tl-empty">
                <span class="tl-empty-icon">🔎</span>
                <h3 class="h5">Không tìm thấy món đồ chơi nào</h3>
                <p>Thử bớt vài bộ lọc hoặc đổi từ khóa khác xem sao nhé.</p>
                @if ($isFiltering)
                <a href="{{ route('home') }}" class="btn btn-primary mt-2">Xóa bộ lọc</a>
                @endif
            </div>
        </div>
        @endforelse
    </div>

    <div class="d-flex justify-content-center mt-5">
        {{ $products->links() }}
    </div>
</section>

{{-- ============================================================
     CAM KẾT
     ============================================================ --}}
@unless ($isFiltering)
<section class="mb-5">
    <div class="row g-3">
        @php
        $promises = [
            ['icon' => '🛡️', 'bubble' => 'tl-bubble-3', 'title' => 'An toàn tuyệt đối', 'desc' => 'Chất liệu đạt chuẩn, không cạnh sắc, ghi rõ độ tuổi phù hợp.'],
            ['icon' => '🚚', 'bubble' => 'tl-bubble-2', 'title' => 'Giao nhanh toàn quốc', 'desc' => 'Đối tác GHN, nội thành nhận hàng trong 24 giờ.'],
            ['icon' => '🔄', 'bubble' => 'tl-bubble-1', 'title' => 'Đổi trả 7 ngày', 'desc' => 'Sản phẩm lỗi được đổi mới miễn phí, không hỏi lý do.'],
            ['icon' => '💳', 'bubble' => 'tl-bubble-4', 'title' => 'Thanh toán linh hoạt', 'desc' => 'COD hoặc MoMo — ví, thẻ ATM và thẻ quốc tế.'],
        ];
        @endphp

        @foreach ($promises as $i => $promise)
        <div class="col-6 col-lg-3 tl-reveal" data-tl-delay="{{ $i * 80 }}">
            <div class="card h-100 text-center p-3 p-md-4">
                <div class="tl-cat-bubble {{ $promise['bubble'] }}">{{ $promise['icon'] }}</div>
                <div class="fw-bold mb-1">{{ $promise['title'] }}</div>
                <div class="small text-muted">{{ $promise['desc'] }}</div>
            </div>
        </div>
        @endforeach
    </div>
</section>
@endunless

{{-- ============================================================
     BÀI VIẾT MỚI NHẤT
     ============================================================ --}}
@if ($latestPosts->isNotEmpty())
<section class="mb-4">
    <div class="d-flex justify-content-between align-items-end mb-4">
        <h2 class="tl-section-title"><span>📰</span> Cẩm nang cho bố mẹ</h2>
        <a href="{{ route('posts.index') }}" class="small text-decoration-none fw-semibold">Xem tất cả →</a>
    </div>

    <div class="row g-4">
        @foreach ($latestPosts as $post)
        <div class="col-md-4 tl-reveal">
            <article class="card h-100 product-card">
                <a href="{{ route('posts.show', $post->slug) }}" class="d-block overflow-hidden">
                    @if ($post->image)
                    <img src="{{ $post->image_url }}" class="card-img-top"
                         style="height: 180px; object-fit: cover;" alt="{{ $post->title }}" loading="lazy">
                    @else
                    <div class="d-flex align-items-center justify-content-center"
                         style="height: 180px; background: var(--tl-grad-sky);">
                        <span style="font-size: 3rem">📰</span>
                    </div>
                    @endif
                </a>
                <div class="card-body">
                    <span class="badge bg-light mb-2">{{ $post->category }}</span>
                    <a href="{{ route('posts.show', $post->slug) }}" class="text-decoration-none text-dark">
                        <h3 class="fw-bold mb-2" style="font-size: 1.02rem; line-height: 1.4;">{{ $post->title }}</h3>
                    </a>
                    <p class="small text-muted mb-0">{{ \Illuminate\Support\Str::limit($post->summary, 95) }}</p>
                </div>
            </article>
        </div>
        @endforeach
    </div>
</section>
@endif

@endsection
