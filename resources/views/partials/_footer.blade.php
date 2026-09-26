{{-- Chân trang dùng chung cho khu vực khách hàng --}}
<footer class="tl-footer">
    {{-- Dải sóng nối mềm giữa nội dung và chân trang --}}
    <svg class="tl-wave" style="margin-bottom:-1px" viewBox="0 0 1440 70" preserveAspectRatio="none" aria-hidden="true" focusable="false">
        <path fill="#5C2E2E" d="M0,32 C240,72 480,0 720,24 C960,48 1200,72 1440,40 L1440,70 L0,70 Z"></path>
    </svg>

    <div class="container pt-2">
        <div class="row g-4">
            {{-- Cột thương hiệu --}}
            <div class="col-lg-4 col-md-6">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="tl-brand-mark">
                        <svg width="22" height="22" viewBox="0 0 100 100" aria-hidden="true" focusable="false">
                            <rect x="14" y="14" width="30" height="30" rx="8" fill="#FFF7BC"/>
                            <rect x="56" y="14" width="30" height="30" rx="8" fill="#FFFFFF"/>
                            <rect x="14" y="56" width="30" height="30" rx="8" fill="#C0EDA6"/>
                            <rect x="56" y="56" width="30" height="30" rx="8" fill="#FFD3CB"/>
                        </svg>
                    </span>
                    <span class="tl-foot-head mb-0 fs-4">ToyLand</span>
                </div>

                <p class="small mb-3" style="max-width: 320px;">
                    Thế giới đồ chơi chính hãng, an toàn cho bé. Mỗi món đồ chơi ở ToyLand đều được chọn theo
                    độ tuổi và kỹ năng mà bé đang phát triển.
                </p>

                <div class="d-flex gap-2">
                    <a href="#" class="tl-social" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="tl-social" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="tl-social" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
                    <a href="#" class="tl-social" aria-label="TikTok"><i class="bi bi-tiktok"></i></a>
                </div>
            </div>

            {{-- Cột mua sắm --}}
            <div class="col-lg-2 col-md-6 col-6">
                <div class="tl-foot-head">Mua sắm</div>
                <ul class="list-unstyled tl-foot-list small mb-0">
                    <li><a href="{{ route('home') }}">Tất cả đồ chơi</a></li>
                    <li><a href="{{ route('home') }}">Danh mục</a></li>
                    <li><a href="{{ route('home', ['sort' => 'best_selling']) }}">Bán chạy nhất</a></li>
                    <li><a href="{{ route('coupons.index') }}">Mã giảm giá</a></li>
                    <li><a href="{{ route('posts.index') }}">Tin tức & Cẩm nang</a></li>
                </ul>
            </div>

            {{-- Cột hỗ trợ --}}
            <div class="col-lg-2 col-md-6 col-6">
                <div class="tl-foot-head">Hỗ trợ</div>
                <ul class="list-unstyled tl-foot-list small mb-0">
                    <li><a href="{{ route('faq.index') }}">Câu hỏi thường gặp</a></li>
                    @auth
                    <li><a href="{{ route('tickets.index') }}">Gửi yêu cầu hỗ trợ</a></li>
                    <li><a href="{{ route('orders.index') }}">Tra cứu đơn hàng</a></li>
                    <li><a href="{{ route('profile.edit') }}">Tài khoản của tôi</a></li>
                    @else
                    <li><a href="{{ route('login') }}">Đăng nhập</a></li>
                    <li><a href="{{ route('register') }}">Đăng ký</a></li>
                    @endauth
                    @foreach ($footerPages ?? [] as $page)
                    <li><a href="{{ route('pages.show', $page->slug) }}">{{ $page->title }}</a></li>
                    @endforeach
                </ul>
            </div>

            {{-- Cột đăng ký nhận tin --}}
            <div class="col-lg-4 col-md-6">
                <div class="tl-foot-head">Nhận ưu đãi sớm nhất</div>
                <p class="small mb-3">Để lại email để nhận mã giảm giá và tin đồ chơi mới về mỗi tuần.</p>

                {{-- Form demo phía giao diện: chưa có bảng người đăng ký trong CSDL nên chỉ hiện lời cảm ơn --}}
                <form class="tl-newsletter" id="tl-newsletter-form" novalidate>
                    <div class="input-group">
                        <input type="email" class="form-control" placeholder="email@cua-ban.com" required aria-label="Email nhận tin">
                        <button type="submit" class="btn btn-warning px-3">Đăng ký</button>
                    </div>
                </form>

                <div class="d-flex align-items-center gap-2 mt-3 small">
                    <i class="bi bi-telephone-fill" style="color: var(--tl-yellow)"></i> 1900 6868
                    <span class="opacity-50">·</span>
                    <i class="bi bi-envelope-fill" style="color: var(--tl-yellow)"></i> hotro@toyland.vn
                </div>
            </div>
        </div>

        <div class="tl-footer-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span>© {{ date('Y') }} ToyLand — Đồ án môn học.</span>
            <span class="d-flex align-items-center gap-2">
                Thanh toán an toàn qua
                <span class="badge bg-light text-dark">COD</span>
                <span class="badge" style="background: #A50064">MoMo</span>
                <span class="opacity-50">·</span>
                Giao hàng bởi <span class="badge bg-light text-dark">GHN</span>
            </span>
        </div>
    </div>
</footer>

@push('scripts')
<script>
    // Form nhận tin ở chân trang chỉ là demo giao diện, chưa lưu vào CSDL
    (function () {
        var form = document.getElementById('tl-newsletter-form');
        if (!form) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var input = form.querySelector('input');

            if (!input.value || !input.checkValidity()) {
                input.classList.add('is-invalid');
                return;
            }

            input.classList.remove('is-invalid');
            input.value = '';

            if (typeof window.showToast === 'function') {
                window.showToast('Cảm ơn bạn! Ưu đãi mới sẽ được gửi tới email của bạn.', 'success');
            }
        });
    })();
</script>
@endpush
