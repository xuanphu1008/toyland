{{-- Khối cuối <body> dùng chung: nút lên đầu trang, vùng toast, thư viện JS --}}

<button type="button" class="tl-to-top" aria-label="Lên đầu trang">
    <i class="bi bi-arrow-up"></i>
</button>

{{-- Toast góc dưới bên phải --}}
<div id="toast-container" class="position-fixed bottom-0 end-0 p-3" style="z-index: 1080;"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    /**
     * Hiện một toast ở góc màn hình.
     * Khai báo ở window để mọi trang và mọi file JS khác đều gọi được.
     * @param {string} message nội dung
     * @param {string} type 'success' | 'danger' | 'warning'
     */
    window.showToast = function (message, type) {
        type = type || 'success';

        var container = document.getElementById('toast-container');
        if (!container) return;

        var icon = type === 'success' ? '✅' : (type === 'danger' ? '⚠️' : 'ℹ️');

        var toast = document.createElement('div');
        toast.className = 'toast align-items-center text-white bg-' + type + ' border-0 show mb-2';
        toast.setAttribute('role', 'alert');
        toast.innerHTML =
            '<div class="d-flex">' +
            '<div class="toast-body d-flex align-items-center gap-2"><span>' + icon + '</span><span></span></div>' +
            '<button type="button" class="btn-close btn-close-white me-2 m-auto" aria-label="Đóng"></button>' +
            '</div>';

        // Gán nội dung bằng textContent để tránh lỗi hiển thị khi thông báo có ký tự đặc biệt
        toast.querySelector('.toast-body span:last-child').textContent = message;

        container.appendChild(toast);
        toast.querySelector('.btn-close').addEventListener('click', function () { toast.remove(); });
        setTimeout(function () { toast.remove(); }, 4000);
    };

    // Cập nhật số lượng trên icon Giỏ hàng ở navbar
    window.updateCartBadge = function (count) {
        document.querySelectorAll('[data-tl-cart-count]').forEach(function (badge) {
            badge.textContent = count;
            badge.classList.toggle('d-none', count <= 0);
        });
    };

    /* AJAX "Thêm vào giỏ hàng" — bắt mọi form có class .ajax-add-to-cart-form */
    document.addEventListener('submit', function (e) {
        var form = e.target.closest('.ajax-add-to-cart-form');
        if (!form) return;

        // Nút "Mua ngay" phải chuyển hẳn sang trang thanh toán, không xử lý AJAX
        if (e.submitter && e.submitter.classList.contains('buy-now-btn')) return;

        e.preventDefault();

        var submitBtn = form.querySelector('button[type="submit"]');
        var originalHtml = submitBtn ? submitBtn.innerHTML : null;

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        }

        fetch(form.action, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: new FormData(form)
        })
            .then(async function (res) {
                var data = await res.json();
                if (!res.ok || !data.success) throw new Error(data.message || 'Có lỗi xảy ra, vui lòng thử lại.');
                return data;
            })
            .then(function (data) {
                window.showToast(data.message, 'success');
                window.updateCartBadge(data.cart_count);

                if (submitBtn) {
                    submitBtn.classList.add('tl-pop');
                    setTimeout(function () { submitBtn.classList.remove('tl-pop'); }, 400);
                }
            })
            .catch(function (err) { window.showToast(err.message, 'danger'); })
            .finally(function () {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalHtml;
                }
            });
    });
</script>

<script src="{{ asset('js/toyland.js') }}"></script>

{{-- Thư viện ảnh, đồng hồ đếm ngược flash sale, nút so sánh.
     filemtime() gắn thêm dấu thời gian sửa file để trình duyệt không dùng
     bản cũ trong bộ nhớ đệm sau mỗi lần cập nhật. --}}
<script src="{{ asset('js/toyland-shop.js') }}?v={{ @filemtime(public_path('js/toyland-shop.js')) }}"></script>

@auth
{{-- Cập nhật con số trên chuông thông báo mà không phải tải lại trang --}}
<script>
(function () {
    var URL_UNREAD = '{{ route('notifications.unread') }}';
    var badge = document.querySelector('[data-tl-noti-count]');
    if (!badge) return;

    function refresh() {
        // Tab đang ẩn thì thôi, khỏi gọi máy chủ vô ích
        if (document.hidden) return;

        fetch(URL_UNREAD, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                if (!data) return;

                var n = parseInt(data.count, 10) || 0;
                badge.textContent = n;
                badge.classList.toggle('d-none', n === 0);

                // Đổi luôn hình chuông rỗng <-> chuông đặc cho khớp
                var icon = badge.parentElement.querySelector('i');
                if (icon) icon.className = n > 0 ? 'bi bi-bell-fill' : 'bi bi-bell';
            })
            .catch(function () { /* mất mạng thì giữ nguyên con số cũ */ });
    }

    setInterval(refresh, 60000);          // mỗi 60 giây
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) refresh();  // quay lại tab thì cập nhật ngay
    });
})();
</script>
@endauth

@stack('scripts')
