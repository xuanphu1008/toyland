<!DOCTYPE html>
<html lang="vi">

<head>
    @include('partials._head')
</head>

<body>
    {{-- ====================== NAVBAR ====================== --}}
    <nav class="navbar navbar-expand-lg tl-navbar sticky-top">
        <div class="container">
            <a class="navbar-brand tl-brand" href="{{ route('home') }}">
                <span class="tl-brand-mark">
                    <svg width="24" height="24" viewBox="0 0 100 100" aria-hidden="true" focusable="false">
                        <rect x="14" y="14" width="30" height="30" rx="8" fill="#FFF7BC"/>
                        <rect x="56" y="14" width="30" height="30" rx="8" fill="#FFFFFF"/>
                        <rect x="14" y="56" width="30" height="30" rx="8" fill="#C0EDA6"/>
                        <rect x="56" y="56" width="30" height="30" rx="8" fill="#FFD3CB"/>
                    </svg>
                </span>
                ToyLand
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#userNavbar"
                    aria-controls="userNavbar" aria-expanded="false" aria-label="Mở menu">
                <i class="bi bi-list fs-2" style="color: var(--tl-primary)"></i>
            </button>

            <div class="collapse navbar-collapse" id="userNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">
                            <i class="bi bi-house-heart me-1"></i> Trang chủ
                        </a>
                    </li>
                    @include('partials._nav-categories')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('orders.*') ? 'active' : '' }}" href="{{ route('orders.index') }}">
                            <i class="bi bi-bag-check me-1"></i> Đơn của tôi
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-three-dots me-1"></i> Khác
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('reviews.index') }}"><i class="bi bi-star me-2"></i>Đánh giá của tôi</a></li>
                            <li><a class="dropdown-item" href="{{ route('coupons.index') }}"><i class="bi bi-ticket-perforated me-2"></i>Mã giảm giá</a></li>
                            <li><a class="dropdown-item" href="{{ route('tickets.index') }}"><i class="bi bi-life-preserver me-2"></i>Yêu cầu hỗ trợ</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('posts.index') }}"><i class="bi bi-newspaper me-2"></i>Tin tức & Cẩm nang</a></li>
                            <li><a class="dropdown-item" href="{{ route('faq.index') }}"><i class="bi bi-question-circle me-2"></i>Câu hỏi thường gặp</a></li>
                        </ul>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-2 flex-wrap">
                    {{-- Nút gạt nền sáng / nền tối --}}
                    <button type="button" class="tl-theme-toggle" data-tl-theme-toggle aria-label="Đổi nền sáng tối">
                        <span class="tl-knob" data-tl-theme-knob>☀️</span>
                    </button>

                    {{-- Chuông thông báo --}}
                    @include('partials._notification-bell')

                    {{-- ============ CHỌN BÉ ĐANG XEM ============ --}}
                    {{-- Chỉ hiện khi tài khoản đã khai hồ sơ bé, để navbar của khách
                         chưa dùng tính năng này không bị thêm nút thừa. --}}
                    @if (($myChildren ?? collect())->isNotEmpty())
                    <div class="dropdown">
                        <button class="btn btn-sm rounded-pill px-3 position-relative
                                       {{ $activeChild ? 'btn-primary' : 'btn-outline-primary' }}"
                                type="button" data-bs-toggle="dropdown" title="Chọn bé đang xem">
                            <span class="me-1">{{ $activeChild->avatar ?? '🧒' }}</span>
                            <span class="d-none d-md-inline">
                                {{ $activeChild ? \Illuminate\Support\Str::limit($activeChild->name, 10) : 'Chọn bé' }}
                            </span>

                            {{-- Chấm đỏ khi có bé sắp sinh nhật --}}
                            @if (($birthdaySoon ?? collect())->isNotEmpty())
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark"
                                  style="font-size: .6rem;">🎂</span>
                            @endif
                        </button>

                        <ul class="dropdown-menu dropdown-menu-end" style="min-width: 260px;">
                            <li class="px-3 pt-2 pb-1">
                                <div class="small text-muted">Xem đồ chơi hợp với bé nào?</div>
                            </li>

                            @foreach ($myChildren as $kid)
                            <li>
                                <form action="{{ route('children.select', $kid->id) }}" method="POST">
                                    @csrf
                                    <button class="dropdown-item d-flex align-items-center gap-2
                                                   {{ optional($activeChild)->id === $kid->id ? 'active' : '' }}">
                                        <span style="font-size: 1.15rem;">{{ $kid->avatar ?: '🧒' }}</span>
                                        <span class="flex-grow-1 text-start">
                                            {{ $kid->name }}
                                            <span class="d-block small opacity-75">{{ $kid->age_label }}</span>
                                        </span>
                                        @if ($kid->is_birthday_soon)
                                        <span title="{{ $kid->countdown_label }}">🎂</span>
                                        @endif
                                    </button>
                                </form>
                            </li>
                            @endforeach

                            @if ($activeChild)
                            <li>
                                <form action="{{ route('children.clear') }}" method="POST">
                                    @csrf
                                    <button class="dropdown-item text-muted small">
                                        <i class="bi bi-x-circle me-2"></i>Bỏ chọn, xem tất cả
                                    </button>
                                </form>
                            </li>
                            @endif

                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="{{ route('children.birthdays') }}">
                                    <i class="bi bi-calendar-heart me-2"></i>Sổ sinh nhật
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('children.index') }}">
                                    <i class="bi bi-people me-2"></i>Quản lý hồ sơ bé
                                </a>
                            </li>
                        </ul>
                    </div>
                    @endif

                    {{-- Yêu thích --}}
                    <a href="{{ route('wishlist.index') }}" class="btn btn-sm btn-outline-danger rounded-pill position-relative px-3"
                       title="Danh sách yêu thích">
                        <i class="bi bi-heart-fill"></i>
                        <span class="badge bg-danger tl-pill-count position-absolute top-0 start-100 translate-middle {{ empty($wishlistCount) ? 'd-none' : '' }}"
                              data-tl-wishlist-count>{{ $wishlistCount ?? 0 }}</span>
                    </a>

                    {{-- Giỏ hàng --}}
                    <a href="{{ route('cart.index') }}" class="btn btn-sm btn-primary rounded-pill position-relative px-3" title="Giỏ hàng">
                        <i class="bi bi-cart3"></i>
                        <span class="badge bg-danger tl-pill-count position-absolute top-0 start-100 translate-middle {{ (session('cart') && count(session('cart'))) ? '' : 'd-none' }}"
                              data-tl-cart-count>{{ session('cart') ? count(session('cart')) : 0 }}</span>
                    </a>

                    {{-- Tài khoản --}}
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary rounded-pill dropdown-toggle px-3" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i>
                            <span class="d-none d-sm-inline">{{ \Illuminate\Support\Str::limit(Auth::user()->name, 12) }}</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li class="px-3 py-2">
                                <div class="fw-bold">{{ Auth::user()->name }}</div>
                                <div class="small text-muted">{{ Auth::user()->email }}</div>
                                <span class="badge {{ Auth::user()->tier_info['color'] }} mt-2">
                                    {{ Auth::user()->tier_info['icon'] }} Hạng {{ Auth::user()->tier_label }}
                                </span>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person-gear me-2"></i>Hồ sơ của tôi</a></li>
                            <li><a class="dropdown-item" href="{{ route('orders.index') }}"><i class="bi bi-bag-check me-2"></i>Lịch sử đơn hàng</a></li>
                            <li>
                                <a class="dropdown-item" href="{{ route('children.index') }}">
                                    <i class="bi bi-emoji-smile me-2"></i>Hồ sơ của bé
                                    @if (($birthdaySoon ?? collect())->isNotEmpty())
                                    <span class="badge bg-warning text-dark ms-1">🎂</span>
                                    @endif
                                </a>
                            </li>
                            @if (Auth::user()->isAdmin())
                            <li><a class="dropdown-item" href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2 me-2"></i>Trang quản trị</a></li>
                            @endif
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('logout') }}" method="POST" class="px-2">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill w-100">
                                        <i class="bi bi-box-arrow-right me-1"></i> Đăng xuất
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    {{-- ====================== THÔNG BÁO ====================== --}}
    <div class="container mt-4">
        @unless (Auth::user()->hasVerifiedEmail())
        <div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span><i class="bi bi-envelope-exclamation me-1"></i> Vui lòng xác thực email của bạn để có thể mua hàng.</span>
            <form action="{{ route('verification.send') }}" method="POST" class="m-0">
                @csrf
                <button type="submit" class="btn btn-sm btn-warning">Gửi lại email xác thực</button>
            </form>
        </div>
        @endunless

        @if (session('success'))
        <div class="alert alert-success" data-tl-autohide><i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}</div>
        @endif
        @if (session('error'))
        <div class="alert alert-danger" data-tl-autohide><i class="bi bi-exclamation-triangle-fill me-1"></i> {{ session('error') }}</div>
        @endif
    </div>

    {{-- ====================== NỘI DUNG ====================== --}}
    <main class="container mt-2 mb-5">
        @yield('content')
    </main>

    {{-- ====================== CHÂN TRANG ====================== --}}
    @include('partials._footer')

    {{-- ====================== HỖ TRỢ KHÁCH HÀNG ======================
         Một widget, hai chế độ: trợ lý tự động (bot) và nhân viên thật.
         Khối chat cũ (viết thẳng trong layout) đã được tách ra partial + file JS
         riêng để dùng lại được ở cả layout khách vãng lai. --}}
    @include('partials._chat-widget')

    {{-- Modal được đẩy ra đây - cuối trang, là con trực tiếp của thẻ body.
         Bootstrap khuyến cáo đặt modal ở cấp cao nhất của tài liệu: nếu modal nằm
         lồng trong một phần tử đã tạo stacking context (VD <main> có z-index),
         lớp phủ .modal-backdrop (z-index 1050, gắn thẳng vào body) sẽ nằm ĐÈ lên
         cả modal -> màn hình tối đen, bấm không được. --}}
    @include('partials._compare-bar')

    @stack('modals')

    @include('partials._scripts')
</body>

</html>
