/*!
 * ToyLand - ba tiện ích nhỏ cho trang bán hàng
 *
 *   1. Thư viện ảnh sản phẩm  - bấm ảnh nhỏ để đổi ảnh lớn
 *   2. Đồng hồ đếm ngược      - cho flash sale
 *   3. Nút so sánh            - thêm/bỏ không phải tải lại trang
 *
 * Viết bằng JavaScript thuần, không phụ thuộc thư viện nào. Mọi phần đều tự
 * kiểm tra phần tử có tồn tại không rồi mới chạy, nên nhúng ở trang nào cũng
 * được mà không sinh lỗi trong Console.
 */
(function () {
    'use strict';

    /* ------------------------------------------------------------------
       1. THƯ VIỆN ẢNH
       ------------------------------------------------------------------ */

    function initGallery() {
        var gallery = document.querySelector('[data-tl-gallery]');
        if (!gallery) return;

        var main = gallery.querySelector('[data-tl-gallery-main]');
        var thumbs = gallery.querySelectorAll('[data-tl-gallery-thumb]');
        if (!main || !thumbs.length) return;

        // Nạp trước các ảnh còn lại để lần bấm đầu tiên không bị khựng
        thumbs.forEach(function (thumb) {
            var pre = new Image();
            pre.src = thumb.getAttribute('data-src');
        });

        function show(thumb) {
            var src = thumb.getAttribute('data-src');
            if (!src || main.getAttribute('src') === src) return;

            // Mờ đi rồi hiện lại cho đỡ giật, chứ không đổi ảnh đột ngột
            main.style.opacity = '0.35';
            main.src = src;
            main.onload = function () { main.style.opacity = '1'; };

            thumbs.forEach(function (t) { t.classList.remove('tl-active'); });
            thumb.classList.add('tl-active');
        }

        thumbs.forEach(function (thumb) {
            thumb.addEventListener('click', function () { show(thumb); });

            // Rê chuột là đổi luôn - nhanh hơn, quen thuộc với người mua hàng
            thumb.addEventListener('mouseenter', function () { show(thumb); });
        });
    }

    /* ------------------------------------------------------------------
       2. ĐỒNG HỒ ĐẾM NGƯỢC
       ------------------------------------------------------------------ */

    function initCountdowns() {
        var clocks = document.querySelectorAll('[data-tl-countdown]');
        if (!clocks.length) return;

        /*
         * Mỗi đồng hồ nhận SỐ GIÂY CÒN LẠI từ server, không nhận mốc thời gian
         * tuyệt đối. Lý do: nếu gửi mốc rồi để JavaScript so với đồng hồ máy
         * khách thì máy đặt sai giờ sẽ đếm ngược sai, có khi ra số âm.
         *
         * Ở đây lấy Date.now() tại thời điểm nạp trang làm gốc rồi trừ dần,
         * nên chỉ phụ thuộc vào việc máy khách đo được KHOẢNG thời gian trôi
         * qua - thứ mà máy nào cũng đúng.
         */
        var startedAt = Date.now();

        var items = Array.prototype.map.call(clocks, function (el) {
            return { el: el, seconds: parseInt(el.getAttribute('data-tl-countdown'), 10) || 0 };
        });

        function pad(n) { return n < 10 ? '0' + n : String(n); }

        function render() {
            var elapsed = Math.floor((Date.now() - startedAt) / 1000);
            var allDone = true;

            items.forEach(function (item) {
                var left = item.seconds - elapsed;

                if (left <= 0) {
                    item.el.textContent = 'Đã kết thúc';
                    item.el.classList.add('tl-ended');
                    return;
                }

                allDone = false;

                var d = Math.floor(left / 86400);
                var h = Math.floor((left % 86400) / 3600);
                var m = Math.floor((left % 3600) / 60);
                var s = left % 60;

                item.el.textContent = d > 0
                    ? d + ' ngày ' + pad(h) + ':' + pad(m) + ':' + pad(s)
                    : pad(h) + ':' + pad(m) + ':' + pad(s);
            });

            // Hết sạch thì dừng hẳn, khỏi chạy timer vô ích suốt phiên
            if (allDone && timer) {
                clearInterval(timer);
                timer = null;
            }
        }

        var timer = setInterval(render, 1000);
        render();
    }

    /* ------------------------------------------------------------------
       3. NÚT SO SÁNH
       ------------------------------------------------------------------ */

    function initCompare() {
        var forms = document.querySelectorAll('.tl-compare-form');
        if (!forms.length) return;

        forms.forEach(function (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();

                var btn = form.querySelector('.tl-compare-btn');

                fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: new FormData(form)
                })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (!data) return;

                        // Đã đủ 4 món thì server trả success = false kèm lời nhắc
                        if (data.success === false) {
                            if (window.showToast) window.showToast(data.message, 'warning');
                            return;
                        }

                        if (btn) btn.classList.toggle('tl-compare-on', !!data.added);

                        updateBar(data.count);

                        if (window.showToast) window.showToast(data.message, 'success');
                    })
                    .catch(function () {
                        // Mất mạng thì gửi form theo kiểu thường để vẫn xong việc
                        form.submit();
                    });
            });
        });
    }

    /**
     * Cập nhật thanh so sánh ở đáy màn hình.
     * Thanh chỉ được server vẽ ra khi đã có ít nhất một món; nếu nó chưa tồn
     * tại mà khách vừa thêm món đầu tiên thì tải lại trang để server vẽ - đơn
     * giản hơn nhiều so với dựng cả thanh bằng JavaScript, và chỉ xảy ra
     * đúng một lần.
     */
    function updateBar(count) {
        var bar = document.querySelector('[data-tl-compare-bar]');

        if (!bar) {
            if (count > 0) window.location.reload();
            return;
        }

        if (count === 0) {
            bar.remove();
            return;
        }

        var el = bar.querySelector('[data-tl-compare-count]');
        if (el) el.textContent = count;
    }

    /* ------------------------------------------------------------------ */

    function init() {
        initGallery();
        initCountdowns();
        initCompare();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
