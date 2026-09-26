<!DOCTYPE html>
<html lang="vi">

<head>
    @include('partials._head')
</head>

<body>
    {{-- ====================== NAVBAR (khách chưa đăng nhập / quản trị viên xem trang khách) ====================== --}}
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

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#guestNavbar"
                    aria-controls="guestNavbar" aria-expanded="false" aria-label="Mở menu">
                <i class="bi bi-list fs-2" style="color: var(--tl-primary)"></i>
            </button>

            <div class="collapse navbar-collapse" id="guestNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">
                            <i class="bi bi-house-heart me-1"></i> Trang chủ
                        </a>
                    </li>
                    @include('partials._nav-categories')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('posts.*') ? 'active' : '' }}" href="{{ route('posts.index') }}">
                            <i class="bi bi-newspaper me-1"></i> Tin tức
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('coupons.*') ? 'active' : '' }}" href="{{ route('coupons.index') }}">
                            <i class="bi bi-ticket-perforated me-1"></i> Mã giảm giá
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('faq.*') ? 'active' : '' }}" href="{{ route('faq.index') }}">
                            <i class="bi bi-question-circle me-1"></i> Hỏi đáp
                        </a>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <button type="button" class="tl-theme-toggle" data-tl-theme-toggle aria-label="Đổi nền sáng tối">
                        <span class="tl-knob" data-tl-theme-knob>☀️</span>
                    </button>

                    @guest
                    <a href="{{ route('login') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Đăng nhập</a>
                    <a href="{{ route('register') }}" class="btn btn-sm btn-primary rounded-pill px-3">Đăng ký</a>
                    @else
                    @if (Auth::user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                        <i class="bi bi-speedometer2 me-1"></i> Trang quản trị
                    </a>
                    @endif
                    <span class="text-muted small d-none d-sm-inline">Xin chào, <strong>{{ Auth::user()->name }}</strong></span>
                    <form action="{{ route('logout') }}" method="POST" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3">Đăng xuất</button>
                    </form>
                    @endguest
                </div>
            </div>
        </div>
    </nav>

    {{-- ====================== NỘI DUNG ====================== --}}
    <main class="container mt-4 mb-5">
        @if (session('success'))
        <div class="alert alert-success" data-tl-autohide><i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}</div>
        @endif
        @if (session('error'))
        <div class="alert alert-danger" data-tl-autohide><i class="bi bi-exclamation-triangle-fill me-1"></i> {{ session('error') }}</div>
        @endif

        @yield('content')
    </main>

    @include('partials._footer')

    {{-- Trợ lý tự động: khách chưa đăng nhập vẫn hỏi được về sản phẩm, phí ship... --}}
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
