/*!
 * ToyLand - Khung chat hai chế độ
 *
 *   bot   : hỏi trợ lý nội bộ, trả lời ngay, khách vãng lai cũng dùng được
 *   human : chat với nhân viên (chỉ khi đã đăng nhập), hỏi server mỗi 3 giây
 *
 * Toàn bộ nội dung do người dùng hoặc server gửi về đều chèn bằng textContent
 * chứ KHÔNG dùng innerHTML, để không dính lỗi XSS khi ai đó gõ thẻ <script>
 * vào ô chat.
 */
(function () {
    'use strict';

    var CFG = window.TL_CHAT || {};

    var root, panel, body, input, form, quickBox, titleEl, subEl, avatarEl, badgeEl;
    var mode = 'bot';
    var humanTimer = null;
    var botStarted = false;
    var lastHumanCount = -1;

    function $(id) { return document.getElementById(id); }

    /* ------------------------------------------------------------------
       DỰNG PHẦN TỬ TIN NHẮN
       ------------------------------------------------------------------ */

    function bubble(text, who) {
        var row = document.createElement('div');
        row.className = 'tl-msg tl-msg-' + who;

        var content = document.createElement('div');
        content.className = 'tl-msg-text';
        // textContent giữ nguyên xuống dòng nhờ CSS white-space: pre-line
        content.textContent = text;

        row.appendChild(content);
        return row;
    }

    function productCard(p) {
        var a = document.createElement('a');
        a.className = 'tl-chat-card';
        a.href = p.url;

        var thumb = document.createElement('div');
        thumb.className = 'tl-chat-card-img';

        if (p.image) {
            var img = document.createElement('img');
            img.src = p.image;
            img.alt = p.name;
            img.loading = 'lazy';
            thumb.appendChild(img);
        } else {
            thumb.textContent = '🧸';
        }

        var info = document.createElement('div');
        info.className = 'tl-chat-card-info';

        var name = document.createElement('div');
        name.className = 'tl-chat-card-name';
        name.textContent = p.name;

        var price = document.createElement('div');
        price.className = 'tl-chat-card-price';
        price.textContent = p.price;

        info.appendChild(name);
        info.appendChild(price);

        if (p.age) {
            var age = document.createElement('div');
            age.className = 'tl-chat-card-age';
            age.textContent = p.age;
            info.appendChild(age);
        }

        // Dấu hiệu bấm được: cả thẻ là một thẻ <a> nhưng nhìn giống ô thông tin,
        // nên thêm dòng này để khách biết bấm vào sẽ mở trang sản phẩm.
        var go = document.createElement('div');
        go.className = 'tl-chat-card-go';
        go.textContent = 'Xem chi tiết →';
        info.appendChild(go);

        a.appendChild(thumb);
        a.appendChild(info);
        a.title = p.name;
        return a;
    }

    function typingIndicator() {
        var row = document.createElement('div');
        row.className = 'tl-msg tl-msg-bot tl-typing';
        row.id = 'tl-typing';

        var dots = document.createElement('div');
        dots.className = 'tl-msg-text tl-dots';
        dots.appendChild(document.createElement('span'));
        dots.appendChild(document.createElement('span'));
        dots.appendChild(document.createElement('span'));

        row.appendChild(dots);
        return row;
    }

    function removeTyping() {
        var t = $('tl-typing');
        if (t) t.remove();
    }

    function scrollToEnd() {
        body.scrollTop = body.scrollHeight;
    }

    function append(node) {
        body.appendChild(node);
        scrollToEnd();
    }

    /* ------------------------------------------------------------------
       CÂU HỎI GỢI Ý & NÚT HÀNH ĐỘNG
       ------------------------------------------------------------------ */

    function renderActions(actions) {
        quickBox.innerHTML = '';

        (actions || []).forEach(function (action) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'tl-chat-chip';
            btn.textContent = action.label;

            if (action.url) {
                btn.addEventListener('click', function () { window.location.href = action.url; });
            } else if (action.send) {
                btn.addEventListener('click', function () { sendBot(action.send); });
            } else if (action.action === 'switch_to_human') {
                btn.addEventListener('click', function () { switchMode('human'); });
            }

            quickBox.appendChild(btn);
        });
    }

    /* ------------------------------------------------------------------
       CHẾ ĐỘ BOT
       ------------------------------------------------------------------ */

    function startBot() {
        if (botStarted) return;
        botStarted = true;

        append(typingIndicator());

        fetch(CFG.botGreeting, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                removeTyping();
                append(bubble(data.reply, 'bot'));
                renderActions(data.actions);
            })
            .catch(function () {
                removeTyping();
                append(bubble('Chào bạn! Mình là trợ lý của ToyLand, bạn cần tìm gì ạ?', 'bot'));
            });
    }

    function sendBot(text) {
        append(bubble(text, 'user'));
        quickBox.innerHTML = '';
        append(typingIndicator());

        fetch(CFG.botSend, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': CFG.csrf
            },
            body: JSON.stringify({ message: text })
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                removeTyping();

                if (!data || !data.success) {
                    append(bubble(
                        (data && data.reply) || 'Mình đang gặp trục trặc, bạn thử lại sau ít phút nhé.',
                        'bot'
                    ));

                    // Khi APP_DEBUG=true, server gửi kèm nguyên văn lỗi.
                    // Hiện luôn ra khung chat để khỏi phải mò trong log.
                    // (`data.message` là định dạng lỗi mặc định của Laravel.)
                    var detail = (data && (data.error || data.message)) || null;

                    if (detail) {
                        var note = bubble('⚠ ' + detail, 'bot');
                        note.style.fontSize = '.74rem';
                        note.style.opacity = '.85';
                        append(note);
                        if (window.console) console.error('[ToyLand chatbot]', detail);
                    }

                    return;
                }

                append(bubble(data.reply, 'bot'));

                if (data.products && data.products.length) {
                    var wrap = document.createElement('div');
                    wrap.className = 'tl-chat-cards';
                    data.products.forEach(function (p) { wrap.appendChild(productCard(p)); });
                    append(wrap);
                }

                renderActions(data.actions);
            })
            .catch(function () {
                removeTyping();
                append(bubble('Không gửi được tin nhắn. Bạn kiểm tra kết nối mạng giúp mình nhé.', 'bot'));
            });
    }

    /* ------------------------------------------------------------------
       CHẾ ĐỘ NHÂN VIÊN
       ------------------------------------------------------------------ */

    function loadHuman() {
        if (!CFG.humanMessages) return;

        fetch(CFG.humanMessages, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (rows) {
                if (!Array.isArray(rows)) return;

                // Chỉ vẽ lại khi số tin nhắn đổi, tránh giật màn hình mỗi 3 giây
                if (rows.length === lastHumanCount) return;
                lastHumanCount = rows.length;

                body.innerHTML = '';

                if (!rows.length) {
                    append(bubble('Bạn để lại câu hỏi, nhân viên ToyLand sẽ trả lời sớm nhất có thể nhé!', 'bot'));
                    return;
                }

                rows.forEach(function (row) {
                    append(bubble(row.message, row.is_admin ? 'bot' : 'user'));
                });
            })
            .catch(function () { /* mất mạng tạm thời thì bỏ qua, lần sau thử lại */ });
    }

    function sendHuman(text) {
        append(bubble(text, 'user'));
        lastHumanCount = -1;   // buộc vẽ lại ở lần hỏi kế tiếp

        fetch(CFG.humanSend, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': CFG.csrf
            },
            body: JSON.stringify({ message: text })
        }).catch(function () {
            append(bubble('Không gửi được tin nhắn, bạn thử lại nhé.', 'bot'));
        });
    }

    /* ------------------------------------------------------------------
       CHUYỂN CHẾ ĐỘ
       ------------------------------------------------------------------ */

    function switchMode(next) {
        // Chat với người thật bắt buộc đăng nhập để nhân viên biết đang nói với ai
        if (next === 'human' && root.dataset.loggedIn !== '1') {
            append(bubble('Bạn đăng nhập giúp mình để được nhân viên hỗ trợ trực tiếp nhé.', 'bot'));
            renderActions([{ label: 'Đăng nhập ngay', url: CFG.loginUrl }]);
            return;
        }

        mode = next;

        document.querySelectorAll('[data-tl-chat-mode]').forEach(function (tab) {
            tab.classList.toggle('active', tab.dataset.tlChatMode === next);
        });

        body.innerHTML = '';
        quickBox.innerHTML = '';
        stopHumanPolling();

        if (next === 'bot') {
            titleEl.textContent = 'Trợ lý ToyLand';
            subEl.textContent = 'Trả lời ngay lập tức';
            avatarEl.textContent = '🧸';
            botStarted = false;
            startBot();
        } else {
            titleEl.textContent = 'Nhân viên tư vấn';
            subEl.textContent = 'Thường trả lời trong vài phút';
            avatarEl.textContent = '👩‍💼';
            lastHumanCount = -1;
            loadHuman();
            humanTimer = setInterval(loadHuman, 3000);
        }
    }

    function stopHumanPolling() {
        if (humanTimer) {
            clearInterval(humanTimer);
            humanTimer = null;
        }
    }

    /* ------------------------------------------------------------------
       ĐÓNG / MỞ
       ------------------------------------------------------------------ */

    function open() {
        panel.hidden = false;
        root.classList.add('tl-chat-open');
        badgeEl.hidden = true;
        input.focus();

        if (mode === 'bot') startBot();
        else { loadHuman(); humanTimer = setInterval(loadHuman, 3000); }
    }

    function close() {
        panel.hidden = true;
        root.classList.remove('tl-chat-open');
        stopHumanPolling();
    }

    /* ------------------------------------------------------------------
       KHỞI ĐỘNG
       ------------------------------------------------------------------ */

    function init() {
        root = $('tl-chat');
        if (!root) return;

        panel = $('tl-chat-panel');
        body = $('tl-chat-body');
        input = $('tl-chat-input');
        form = $('tl-chat-form');
        quickBox = $('tl-chat-quick');
        titleEl = $('tl-chat-title');
        subEl = $('tl-chat-sub');
        avatarEl = $('tl-chat-avatar');
        badgeEl = $('tl-chat-badge');

        $('tl-chat-toggle').addEventListener('click', function () {
            panel.hidden ? open() : close();
        });

        $('tl-chat-close').addEventListener('click', close);

        document.querySelectorAll('[data-tl-chat-mode]').forEach(function (tab) {
            tab.addEventListener('click', function () { switchMode(tab.dataset.tlChatMode); });
        });

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            var text = input.value.trim();
            if (!text) return;

            input.value = '';
            mode === 'bot' ? sendBot(text) : sendHuman(text);
        });

        // Đóng bằng phím Esc
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !panel.hidden) close();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
