@extends('layout')

@section('title', 'Đăng ký - ToyLand')

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-10">
        <div class="card overflow-hidden tl-reveal">
            <div class="row g-0">

                {{-- Cột form --}}
                <div class="col-lg-7 order-lg-1 order-2">
                    <div class="p-4 p-md-5">
                        <h1 class="h3 fw-bold mb-1">Tạo tài khoản mới</h1>
                        <p class="text-muted mb-4">Đã có tài khoản? <a href="{{ route('login') }}" class="fw-semibold">Đăng nhập</a></p>

                        @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                            </ul>
                        </div>
                        @endif

                        <form action="{{ route('register') }}" method="POST" id="tl-register-form">
                            @csrf

                            <div class="form-floating mb-3">
                                <input type="text" name="name" id="regName" value="{{ old('name') }}"
                                       class="form-control" placeholder="Nguyễn Văn A" required autofocus>
                                <label for="regName"><i class="bi bi-person me-1"></i> Họ và tên</label>
                            </div>

                            <div class="form-floating mb-3">
                                <input type="email" name="email" id="regEmail" value="{{ old('email') }}"
                                       class="form-control" placeholder="email@cua-ban.com" required>
                                <label for="regEmail"><i class="bi bi-envelope me-1"></i> Email</label>
                                <div class="form-text">Hệ thống sẽ gửi link xác thực tới địa chỉ này.</div>
                            </div>

                            <div class="form-floating mb-2 position-relative">
                                <input type="password" name="password" id="regPassword"
                                       class="form-control pe-5" placeholder="Mật khẩu" required minlength="6">
                                <label for="regPassword"><i class="bi bi-lock me-1"></i> Mật khẩu</label>
                                <button type="button" class="btn btn-sm position-absolute end-0 top-50 translate-middle-y me-2 border-0"
                                        data-tl-toggle-pw="regPassword" aria-label="Hiện mật khẩu">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>

                            {{-- Thanh đo độ mạnh mật khẩu --}}
                            <div class="mb-3">
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar" id="tl-pw-bar" style="width: 0%"></div>
                                </div>
                                <div class="form-text" id="tl-pw-text">Tối thiểu 6 ký tự.</div>
                            </div>

                            <div class="form-floating mb-4">
                                <input type="password" name="password_confirmation" id="regConfirm"
                                       class="form-control" placeholder="Nhập lại mật khẩu" required>
                                <label for="regConfirm"><i class="bi bi-lock-fill me-1"></i> Nhập lại mật khẩu</label>
                                <div class="invalid-feedback">Mật khẩu nhập lại chưa khớp.</div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                <i class="bi bi-person-plus me-1"></i> Đăng ký
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
                            Đăng ký bằng Google
                        </a>
                    </div>
                </div>

                {{-- Cột minh họa --}}
                <div class="col-lg-5 order-lg-2 order-1 d-flex flex-column justify-content-center p-4 p-lg-5 text-white position-relative"
                     style="background: linear-gradient(135deg, #E8574A 0%, #F2921D 100%);">
                    <div style="position:absolute;inset:0;background-image:radial-gradient(circle at 1px 1px, rgba(255,255,255,.22) 1px, transparent 0);background-size:20px 20px"></div>

                    <div class="position-relative">
                        <h2 class="text-white fw-bold mb-3">Tham gia<br>ToyLand ngay!</h2>

                        <ul class="list-unstyled small mb-4" style="color: rgba(255,255,255,.95)">
                            <li class="mb-2"><i class="bi bi-check-circle-fill me-2"></i> Tích điểm mỗi đơn hàng</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill me-2"></i> Giảm tới 8% theo hạng thành viên</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill me-2"></i> Lưu danh sách đồ chơi yêu thích</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill me-2"></i> Theo dõi đơn hàng theo thời gian thực</li>
                        </ul>

                        <svg viewBox="0 0 300 180" width="100%" height="auto" class="d-none d-lg-block" role="img" aria-label="Minh họa quà tặng và bóng bay">
                            <g class="tl-float">
                                <rect x="96" y="96" width="108" height="70" rx="14" fill="#FFFFFF" opacity=".95"/>
                                <rect x="142" y="96" width="16" height="70" fill="#FD5D5D"/>
                                <rect x="96" y="120" width="108" height="14" fill="#FD5D5D"/>
                                <path d="M150 96 c-20 -28 -44 -6 -22 6 M150 96 c20 -28 44 -6 22 6" stroke="#C0EDA6" stroke-width="9" fill="none" stroke-linecap="round"/>
                            </g>
                            <circle cx="52" cy="60" r="20" fill="#FFFFFF" opacity=".55" class="tl-float-2"/>
                            <circle cx="252" cy="52" r="15" fill="#FFFFFF" opacity=".45" class="tl-float-3"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Hiện/ẩn mật khẩu
    document.querySelectorAll('[data-tl-toggle-pw]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.dataset.tlTogglePw);
            if (!input) return;
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.innerHTML = show ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
        });
    });

    (function () {
        var pw = document.getElementById('regPassword');
        var confirm = document.getElementById('regConfirm');
        var bar = document.getElementById('tl-pw-bar');
        var text = document.getElementById('tl-pw-text');
        if (!pw) return;

        // Đo độ mạnh theo 4 tiêu chí: độ dài, chữ hoa, chữ số, ký tự đặc biệt
        pw.addEventListener('input', function () {
            var v = pw.value;
            var score = 0;
            if (v.length >= 6) score++;
            if (v.length >= 10) score++;
            if (/[A-Z]/.test(v) && /[a-z]/.test(v)) score++;
            if (/\d/.test(v)) score++;
            if (/[^A-Za-z0-9]/.test(v)) score++;

            var levels = [
                { w: '0%',   c: 'bg-danger',  t: 'Tối thiểu 6 ký tự.' },
                { w: '25%',  c: 'bg-danger',  t: 'Mật khẩu yếu.' },
                { w: '45%',  c: 'bg-warning', t: 'Tạm ổn, nên thêm chữ hoa hoặc số.' },
                { w: '65%',  c: 'bg-warning', t: 'Khá tốt.' },
                { w: '85%',  c: 'bg-success', t: 'Mạnh!' },
                { w: '100%', c: 'bg-success', t: 'Rất mạnh!' }
            ];

            var level = levels[Math.min(score, 5)];
            bar.style.width = level.w;
            bar.className = 'progress-bar ' + level.c;
            text.textContent = level.t;
        });

        // Kiểm tra khớp mật khẩu ngay khi gõ
        function checkMatch() {
            if (!confirm.value) {
                confirm.classList.remove('is-valid', 'is-invalid');
                return;
            }
            var ok = confirm.value === pw.value;
            confirm.classList.toggle('is-valid', ok);
            confirm.classList.toggle('is-invalid', !ok);
        }

        confirm.addEventListener('input', checkMatch);
        pw.addEventListener('input', checkMatch);
    })();
</script>
@endpush
@endsection
