@extends('layout')

@section('title', 'Đăng nhập - ToyLand')

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-10">
        <div class="card overflow-hidden tl-reveal">
            <div class="row g-0">

                {{-- Cột minh họa --}}
                <div class="col-lg-5 d-none d-lg-flex flex-column justify-content-center p-5 text-white position-relative"
                     style="background: var(--tl-grad-hero);">
                    <div style="position:absolute;inset:0;background-image:radial-gradient(circle at 1px 1px, rgba(255,255,255,.2) 1px, transparent 0);background-size:20px 20px"></div>

                    <div class="position-relative">
                        <h2 class="text-white fw-bold mb-3">Chào mừng<br>trở lại ToyLand!</h2>
                        <p class="mb-4" style="color: rgba(255,255,255,.9)">
                            Đăng nhập để xem đơn hàng, điểm thưởng và danh sách đồ chơi bé yêu thích.
                        </p>

                        <svg viewBox="0 0 300 220" width="100%" height="auto" role="img" aria-label="Minh họa khối đồ chơi và ngôi sao">
                            <circle cx="150" cy="110" r="95" fill="rgba(255,255,255,.12)"/>
                            <g class="tl-float">
                                <rect x="100" y="130" width="72" height="50" rx="14" fill="#FFF7BC"/>
                                <rect x="112" y="84" width="58" height="44" rx="12" fill="#C0EDA6"/>
                                <rect x="122" y="46" width="42" height="36" rx="11" fill="#FFFFFF"/>
                            </g>
                            <g fill="#FFF7BC" class="tl-float-2">
                                <path d="M228 70 l6 13 14 2 -10 10 2 14 -12 -7 -12 7 2 -14 -10 -10 14 -2z"/>
                            </g>
                            <circle cx="62" cy="86" r="8" fill="#FFFFFF" opacity=".65" class="tl-float-3"/>
                            <circle cx="238" cy="160" r="11" fill="#C0EDA6" class="tl-float"/>
                        </svg>

                        <div class="d-flex gap-3 mt-4 small">
                            <span><i class="bi bi-shield-check me-1"></i> Bảo mật</span>
                            <span><i class="bi bi-lightning-charge me-1"></i> Nhanh chóng</span>
                            <span><i class="bi bi-gift me-1"></i> Tích điểm</span>
                        </div>
                    </div>
                </div>

                {{-- Cột form --}}
                <div class="col-lg-7">
                    <div class="p-4 p-md-5">
                        <h1 class="h3 fw-bold mb-1">Đăng nhập</h1>
                        <p class="text-muted mb-4">Chưa có tài khoản? <a href="{{ route('register') }}" class="fw-semibold">Đăng ký ngay</a></p>

                        @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                        @endif
                        @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif
                        @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                            </ul>
                        </div>
                        @endif

                        <form action="{{ route('login') }}" method="POST">
                            @csrf

                            <div class="form-floating mb-3">
                                <input type="email" name="email" id="loginEmail" value="{{ old('email') }}"
                                       class="form-control" placeholder="email@cua-ban.com" required autofocus>
                                <label for="loginEmail"><i class="bi bi-envelope me-1"></i> Email</label>
                            </div>

                            <div class="form-floating mb-3 position-relative">
                                <input type="password" name="password" id="loginPassword"
                                       class="form-control pe-5" placeholder="Mật khẩu" required>
                                <label for="loginPassword"><i class="bi bi-lock me-1"></i> Mật khẩu</label>
                                <button type="button" class="btn btn-sm position-absolute end-0 top-50 translate-middle-y me-2 border-0"
                                        data-tl-toggle-pw="loginPassword" aria-label="Hiện mật khẩu">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div class="form-check">
                                    <input type="checkbox" name="remember" class="form-check-input" id="remember">
                                    <label class="form-check-label" for="remember">Ghi nhớ đăng nhập</label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Đăng nhập
                            </button>
                        </form>

                        <div class="d-flex align-items-center my-4">
                            <hr class="flex-grow-1">
                            <span class="px-3 text-muted small">hoặc</span>
                            <hr class="flex-grow-1">
                        </div>

                        <a href="{{ route('auth.google') }}"
                           class="btn btn-outline-secondary btn-lg w-100 d-flex align-items-center justify-content-center gap-2">
                            <svg width="20" height="20" viewBox="0 0 48 48" aria-hidden="true" focusable="false">
                                <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                                <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                                <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                                <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                            </svg>
                            Đăng nhập bằng Google
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Nút con mắt hiện/ẩn mật khẩu
    document.querySelectorAll('[data-tl-toggle-pw]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.dataset.tlTogglePw);
            if (!input) return;
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.innerHTML = show ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
        });
    });
</script>
@endpush
@endsection
