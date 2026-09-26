/* ============================================================================
   TOYLAND — tương tác giao diện dùng chung
   Nền tối, hiệu ứng xuất hiện khi cuộn, navbar co lại, nút lên đầu trang.
   Viết bằng JS thuần, không phụ thuộc thư viện ngoài.
   ========================================================================== */
(function () {
    'use strict';

    /* ---------------------------------------------------------------------
       1. NỀN TỐI
       Lưu ý: đoạn set data-theme ban đầu nằm inline trong <head> của layout
       để tránh "nháy trắng" khi tải trang. Ở đây chỉ xử lý nút gạt.
       --------------------------------------------------------------------- */
    var STORAGE_KEY = 'toyland-theme';

    function currentTheme() {
        return document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
    }

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);

        // localStorage có thể bị chặn (chế độ ẩn danh) -> bọc try/catch
        try {
            localStorage.setItem(STORAGE_KEY, theme);
        } catch (e) { /* bỏ qua, chỉ mất khả năng ghi nhớ lựa chọn */ }

        document.querySelectorAll('[data-tl-theme-knob]').forEach(function (knob) {
            knob.textContent = theme === 'dark' ? '🌙' : '☀️';
        });

        document.querySelectorAll('[data-tl-theme-toggle]').forEach(function (btn) {
            btn.setAttribute('aria-label', theme === 'dark' ? 'Chuyển sang nền sáng' : 'Chuyển sang nền tối');
            btn.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
        });
    }

    function initTheme() {
        applyTheme(currentTheme());

        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-tl-theme-toggle]');
            if (!btn) return;
            e.preventDefault();
            applyTheme(currentTheme() === 'dark' ? 'light' : 'dark');
        });
    }

    /* ---------------------------------------------------------------------
       2. HIỆU ỨNG XUẤT HIỆN KHI CUỘN TỚI
       --------------------------------------------------------------------- */
    function initReveal() {
        var items = document.querySelectorAll('.tl-reveal');
        if (!items.length) return;

        // Trình duyệt quá cũ hoặc người dùng tắt hiệu ứng -> hiện luôn
        if (!('IntersectionObserver' in window) ||
            window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            items.forEach(function (el) { el.classList.add('tl-in'); });
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;

                // Trễ dần theo thứ tự để các thẻ hiện lần lượt, không "bụp" cùng lúc
                var delay = parseInt(entry.target.dataset.tlDelay || '0', 10);
                setTimeout(function () { entry.target.classList.add('tl-in'); }, delay);

                observer.unobserve(entry.target);
            });
        }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });

        items.forEach(function (el, i) {
            if (!el.dataset.tlDelay) el.dataset.tlDelay = String(Math.min(i, 6) * 70);
            observer.observe(el);
        });
    }

    /* ---------------------------------------------------------------------
       3. NAVBAR CO LẠI KHI CUỘN
       --------------------------------------------------------------------- */
    function initNavbar() {
        var nav = document.querySelector('.tl-navbar');
        if (!nav) return;

        var onScroll = function () {
            nav.classList.toggle('tl-scrolled', window.scrollY > 18);
        };

        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    }

    /* ---------------------------------------------------------------------
       4. NÚT LÊN ĐẦU TRANG
       --------------------------------------------------------------------- */
    function initToTop() {
        var btn = document.querySelector('.tl-to-top');
        if (!btn) return;

        var onScroll = function () {
            btn.classList.toggle('tl-show', window.scrollY > 420);
        };

        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });

        btn.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    /* ---------------------------------------------------------------------
       5. NÚT YÊU THÍCH GỬI BẰNG AJAX
       Không phải tải lại cả trang chỉ để đổi một trái tim.
       --------------------------------------------------------------------- */
    function initWishlist() {
        document.addEventListener('submit', function (e) {
            var form = e.target.closest('.tl-wishlist-form');
            if (!form) return;

            e.preventDefault();

            var btn = form.querySelector('button');
            if (btn) btn.disabled = true;

            fetch(form.action, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(form)
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (!data || !data.success) throw new Error('Không cập nhật được');

                    if (btn) {
                        btn.classList.add('tl-pop');
                        setTimeout(function () { btn.classList.remove('tl-pop'); }, 400);

                        // Nút dạng trái tim trên thẻ sản phẩm
                        if (btn.classList.contains('tl-heart-btn')) {
                            btn.textContent = data.added ? '❤️' : '🤍';
                        } else {
                            // Nút dạng dài ở trang chi tiết sản phẩm
                            btn.textContent = data.added ? '❤️ Đã yêu thích' : '🤍 Thêm vào yêu thích';
                            btn.classList.toggle('btn-danger', data.added);
                            btn.classList.toggle('btn-outline-danger', !data.added);
                        }
                    }

                    // Cập nhật số đếm trên navbar
                    document.querySelectorAll('[data-tl-wishlist-count]').forEach(function (el) {
                        el.textContent = data.count;
                        el.classList.toggle('d-none', data.count <= 0);
                    });

                    if (typeof window.showToast === 'function') {
                        window.showToast(data.message, 'success');
                    }
                })
                .catch(function () {
                    // Lỗi mạng/phiên hết hạn -> gửi lại theo cách thường để người dùng thấy thông báo
                    form.submit();
                })
                .finally(function () {
                    if (btn) btn.disabled = false;
                });
        });
    }

    /* ---------------------------------------------------------------------
       6. TỰ ẨN THÔNG BÁO SAU 6 GIÂY
       --------------------------------------------------------------------- */
    function initAutoDismiss() {
        document.querySelectorAll('[data-tl-autohide]').forEach(function (el) {
            setTimeout(function () {
                el.style.transition = 'opacity .4s ease, transform .4s ease, margin .4s ease, max-height .4s ease';
                el.style.opacity = '0';
                el.style.transform = 'translateY(-8px)';
                el.style.maxHeight = '0';
                el.style.marginBottom = '0';
                el.style.overflow = 'hidden';
                setTimeout(function () { el.remove(); }, 450);
            }, 6000);
        });
    }

    /* ---------------------------------------------------------------------
       Ô CHỌN SỐ LƯỢNG (.tl-qty)
       Dùng uỷ quyền sự kiện trên document nên ô số lượng được thêm sau
       (VD: khi tải lại giỏ hàng bằng AJAX) vẫn hoạt động.
       --------------------------------------------------------------------- */
    function clampQty(input) {
        if (!input) return;

        var min = parseInt(input.min, 10);
        var max = parseInt(input.max, 10);
        if (isNaN(min)) min = 1;

        var value = parseInt(input.value, 10);
        if (isNaN(value)) value = min;

        value = Math.max(min, value);
        if (!isNaN(max)) value = Math.min(max, value);

        input.value = value;

        // Khoá nút khi đã chạm biên để khách biết là hết nấc
        var box = input.closest('.tl-qty');
        if (box) {
            var minus = box.querySelector('[data-tl-qty="-1"]');
            var plus = box.querySelector('[data-tl-qty="1"]');
            if (minus) minus.disabled = value <= min;
            if (plus) plus.disabled = !isNaN(max) && value >= max;
        }

        return value;
    }

    function initQty() {
        document.querySelectorAll('.tl-qty .tl-qty-input').forEach(clampQty);

        document.addEventListener('click', function (event) {
            var btn = event.target.closest('[data-tl-qty]');
            if (!btn) return;

            var box = btn.closest('.tl-qty');
            var input = box && box.querySelector('.tl-qty-input');
            if (!input) return;

            event.preventDefault();

            var step = parseInt(btn.dataset.tlQty, 10) || 0;
            input.value = (parseInt(input.value, 10) || 0) + step;
            clampQty(input);

            // Báo cho các script khác (VD: giỏ hàng tự cập nhật thành tiền)
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });

        // Gõ tay thì cũng phải nằm trong khoảng cho phép
        document.addEventListener('input', function (event) {
            if (event.target.classList && event.target.classList.contains('tl-qty-input')) {
                var box = event.target.closest('.tl-qty');
                var plus = box && box.querySelector('[data-tl-qty="1"]');
                var minus = box && box.querySelector('[data-tl-qty="-1"]');
                if (plus) plus.disabled = false;
                if (minus) minus.disabled = false;
            }
        });

        document.addEventListener('blur', function (event) {
            if (event.target.classList && event.target.classList.contains('tl-qty-input')) {
                clampQty(event.target);
            }
        }, true);
    }

    /* ---------------------------------------------------------------------
       KHỞI ĐỘNG
       --------------------------------------------------------------------- */
    function boot() {
        initTheme();
        initReveal();
        initNavbar();
        initToTop();
        initWishlist();
        initAutoDismiss();
        initQty();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
