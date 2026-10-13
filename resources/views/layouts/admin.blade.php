<!DOCTYPE html>
<html lang="vi">

<head>
    @include('partials._head')

    <style>
        .admin-sidebar {
            width: 260px;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            overflow-y: auto;
            z-index: 1030;
            transition: transform .3s var(--tl-ease);
        }

        .admin-main {
            margin-left: 260px;
            width: calc(100% - 260px);
            padding: 1.8rem 2rem;
            min-height: 100vh;
        }

        /* Trên màn hình nhỏ, thanh bên trượt ra khi bấm nút menu */
        @media (max-width: 991.98px) {
            .admin-sidebar { transform: translateX(-100%); box-shadow: var(--tl-shadow-lg); }
            .admin-sidebar.tl-open { transform: none; }
            .admin-main { margin-left: 0; width: 100%; padding: 1rem; padding-top: 4.5rem; }
            .tl-admin-topbar { display: flex !important; }
        }

        .tl-admin-topbar {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1020;
            background: var(--tl-surface);
            border-bottom: 1px solid var(--tl-border);
            padding: .6rem 1rem;
            align-items: center;
            gap: .75rem;
            box-shadow: var(--tl-shadow-xs);
        }

        .tl-sidebar-backdrop {
            position: fixed; inset: 0;
            background: rgba(34, 31, 75, .5);
            backdrop-filter: blur(4px);
            z-index: 1025;
            display: none;
        }

        .tl-sidebar-backdrop.tl-open { display: block; }

        .admin-avatar { width: 56px; height: 56px; font-size: 1.35rem; }

        /* Nhóm mục trong thanh bên */
        .tl-nav-group {
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: .09em;
            font-weight: 700;
            color: rgba(255, 255, 255, .38);
            padding: .9rem .9rem .35rem;
        }
    </style>
</head>

<body>
    {{-- Thanh trên cùng chỉ hiện ở màn hình nhỏ --}}
    <div class="tl-admin-topbar">
        <button class="btn btn-sm btn-outline-primary rounded-pill" id="tl-sidebar-btn" aria-label="Mở menu quản trị">
            <i class="bi bi-list"></i>
        </button>
        <span class="tl-brand fs-5 mb-0">ToyLand Admin</span>
        <button type="button" class="tl-theme-toggle ms-auto" data-tl-theme-toggle aria-label="Đổi nền sáng tối">
            <span class="tl-knob" data-tl-theme-knob>☀️</span>
        </button>
    </div>

    <div class="tl-sidebar-backdrop" id="tl-sidebar-backdrop"></div>

    {{-- ====================== THANH BÊN ====================== --}}
    <aside class="admin-sidebar text-white d-flex flex-column p-3" id="tl-sidebar">
        <div class="text-center border-bottom border-secondary pb-3 mb-2">
            <div class="admin-avatar rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2 text-white">
                {{ strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}
            </div>
            <div class="fw-bold">{{ Auth::user()->name }}</div>
            <div class="small text-white-50">{{ \Illuminate\Support\Str::limit(Auth::user()->email, 24) }}</div>
            <span class="badge bg-primary mt-2">Quản trị viên</span>
        </div>

        <nav class="nav nav-pills flex-column flex-grow-1">
            <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                <i class="bi bi-speedometer2 me-2"></i> Bảng điều khiển
            </a>

            <div class="tl-nav-group">Bán hàng</div>
            <a class="nav-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}" href="{{ route('admin.orders.index') }}">
                <i class="bi bi-receipt me-2"></i> Đơn hàng
            </a>
            <a class="nav-link {{ request()->routeIs('admin.carrier.*') ? 'active' : '' }}" href="{{ route('admin.carrier.index') }}">
                <i class="bi bi-truck me-2"></i> Đơn vị vận chuyển
            </a>
            <a class="nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}" href="{{ route('admin.products.index') }}">
                <i class="bi bi-box-seam me-2"></i> Sản phẩm
            </a>
            <a class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}" href="{{ route('admin.categories.index') }}">
                <i class="bi bi-tags me-2"></i> Danh mục
            </a>
            <a class="nav-link {{ request()->routeIs('admin.coupons.*') ? 'active' : '' }}" href="{{ route('admin.coupons.index') }}">
                <i class="bi bi-ticket-perforated me-2"></i> Mã giảm giá
            </a>

            <div class="tl-nav-group">Khách hàng</div>
            <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                <i class="bi bi-people me-2"></i> Người dùng
            </a>
            <a class="nav-link {{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}" href="{{ route('admin.reviews.index') }}">
                <i class="bi bi-star me-2"></i> Đánh giá
            </a>
            <a class="nav-link {{ request()->routeIs('admin.tickets.*') ? 'active' : '' }}" href="{{ route('admin.tickets.index') }}">
                <i class="bi bi-life-preserver me-2"></i> Yêu cầu hỗ trợ
            </a>

            <div class="tl-nav-group">Nội dung</div>
            <a class="nav-link {{ request()->routeIs('admin.posts.*') ? 'active' : '' }}" href="{{ route('admin.posts.index') }}">
                <i class="bi bi-newspaper me-2"></i> Bài viết
            </a>
            <a class="nav-link {{ request()->routeIs('admin.pages.*') ? 'active' : '' }}" href="{{ route('admin.pages.index') }}">
                <i class="bi bi-file-earmark-text me-2"></i> Trang tĩnh
            </a>
            <a class="nav-link {{ request()->routeIs('admin.faqs.*') ? 'active' : '' }}" href="{{ route('admin.faqs.index') }}">
                <i class="bi bi-question-circle me-2"></i> Câu hỏi thường gặp
            </a>

            <div class="tl-nav-group">Phân tích</div>
            <a class="nav-link {{ request()->routeIs('admin.chatbot.*') ? 'active' : '' }}" href="{{ route('admin.chatbot.index') }}">
                <i class="bi bi-robot me-2"></i> Chatbot
            </a>
            <a class="nav-link {{ request()->routeIs('admin.birthdays.*') ? 'active' : '' }}" href="{{ route('admin.birthdays.index') }}">
                <i class="bi bi-calendar-heart me-2"></i> Sổ sinh nhật
            </a>
            <a class="nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}" href="{{ route('admin.reports.index') }}">
                <i class="bi bi-graph-up-arrow me-2"></i> Báo cáo doanh thu
            </a>
            <a class="nav-link {{ request()->routeIs('admin.finance.index') ? 'active' : '' }}" href="{{ route('admin.finance.index') }}">
                <i class="bi bi-cash-coin me-2"></i> Thống kê tài chính
            </a>
            <a class="nav-link {{ request()->routeIs('admin.finance.transactions') ? 'active' : '' }}" href="{{ route('admin.finance.transactions') }}">
                <i class="bi bi-credit-card-2-front me-2"></i> Giao dịch thanh toán
            </a>
            @php($openRefunds = \App\Models\RefundRequest::whereIn('status', ['requested', 'in_transit', 'received'])->count())
            <a class="nav-link {{ request()->routeIs('admin.refunds.*') ? 'active' : '' }}" href="{{ route('admin.refunds.index', ['status' => 'open']) }}">
                <i class="bi bi-arrow-return-left me-2"></i> Hoàn tiền & trả hàng
                @if ($openRefunds > 0)<span class="badge bg-danger ms-1">{{ $openRefunds }}</span>@endif
            </a>
            <a class="nav-link {{ request()->routeIs('admin.exports.*') ? 'active' : '' }}" href="{{ route('admin.exports.index') }}">
                <i class="bi bi-file-earmark-spreadsheet me-2"></i> Xuất dữ liệu
            </a>
        </nav>

        <div class="border-top border-secondary pt-3 mt-3">
            <div class="d-flex align-items-center justify-content-between px-2 mb-2">
                <span class="small text-white-50">Nền tối</span>
                <button type="button" class="tl-theme-toggle" data-tl-theme-toggle aria-label="Đổi nền sáng tối">
                    <span class="tl-knob" data-tl-theme-knob>☀️</span>
                </button>
            </div>
            <a href="{{ route('notifications.index') }}" class="nav-link">
                <i class="bi bi-bell me-2"></i> Thông báo
                @if (($unreadNotifications ?? 0) > 0)
                <span class="badge bg-danger ms-1">{{ $unreadNotifications }}</span>
                @endif
            </a>
            <a href="{{ url('/') }}" class="nav-link">
                <i class="bi bi-house-door me-2"></i> Xem trang bán hàng
            </a>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="nav-link btn-logout border-0 bg-transparent w-100 text-start">
                    <i class="bi bi-box-arrow-right me-2"></i> Đăng xuất
                </button>
            </form>
        </div>
    </aside>

    {{-- ====================== NỘI DUNG ====================== --}}
    <main class="admin-main">
        @if (session('success'))
        <div class="alert alert-success" data-tl-autohide><i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}</div>
        @endif
        @if (session('error'))
        <div class="alert alert-danger" data-tl-autohide><i class="bi bi-exclamation-triangle-fill me-1"></i> {{ session('error') }}</div>
        @endif

        @yield('content')
    </main>

    {{-- ====================== CHAT VỚI KHÁCH HÀNG ====================== --}}
    <div id="admin-chat-box" style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;">
        <button id="chat-toggle" class="btn btn-primary shadow px-3 py-2" aria-label="Mở khung chat khách hàng">
            <i class="bi bi-headset me-1"></i> Chat khách hàng
        </button>

        <div id="chat-popup" class="card" style="display: none; width: 420px;">
            <div class="card-header text-white d-flex justify-content-between align-items-center">
                <strong><i class="bi bi-chat-dots me-1"></i> Hỗ trợ trực tuyến</strong>
                <button id="chat-close" class="btn btn-sm btn-light rounded-circle" style="width:28px;height:28px;padding:0" aria-label="Đóng chat">✕</button>
            </div>
            <div class="d-flex" style="height: 350px;">
                <div id="user-list" class="border-end overflow-auto" style="width: 40%; cursor: pointer;">
                    <div class="p-2 text-center text-muted"><small>Đang tải...</small></div>
                </div>
                <div id="chat-messages" class="p-2 overflow-auto" style="width: 60%;">
                    <div class="text-center mt-5 text-muted"><small>Chọn khách hàng</small></div>
                </div>
            </div>
            <div class="card-footer bg-white">
                <div class="input-group">
                    <input type="text" id="chat-input" class="form-control form-control-sm" placeholder="Nhập câu trả lời..." aria-label="Nội dung trả lời">
                    <button id="send-btn" class="btn btn-success btn-sm">Gửi</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            /* ----- Thanh bên trên màn hình nhỏ ----- */
            var sidebar = document.getElementById('tl-sidebar');
            var backdrop = document.getElementById('tl-sidebar-backdrop');
            var sidebarBtn = document.getElementById('tl-sidebar-btn');

            function closeSidebar() {
                sidebar.classList.remove('tl-open');
                backdrop.classList.remove('tl-open');
            }

            if (sidebarBtn) {
                sidebarBtn.onclick = function () {
                    sidebar.classList.toggle('tl-open');
                    backdrop.classList.toggle('tl-open');
                };
                backdrop.onclick = closeSidebar;
            }

            /* ----- Chat với khách hàng ----- */
            let currentUserId = null;
            const chatPopup = document.getElementById("chat-popup");
            const chatMessages = document.getElementById("chat-messages");
            const chatInput = document.getElementById("chat-input");

            document.getElementById("chat-toggle").onclick = () => { chatPopup.style.display = "block"; loadUsers(); };
            document.getElementById("chat-close").onclick = () => { chatPopup.style.display = "none"; };

            function loadUsers() {
                fetch("{{ route('admin.chat.users') }}").then(res => res.json()).then(users => {
                    const list = document.getElementById("user-list");
                    list.innerHTML = "";

                    if (!users.length) {
                        list.innerHTML = '<div class="p-2 text-muted"><small>Chưa có hội thoại</small></div>';
                        return;
                    }

                    users.forEach(user => {
                        // Tạo phần tử bằng DOM API để tên khách không bị hiểu nhầm thành HTML
                        const row = document.createElement('div');
                        row.className = 'p-2 border-bottom' + (currentUserId == user.id ? ' bg-light fw-bold' : '');
                        row.textContent = user.name;
                        row.onclick = function () { selectUser(user.id, row); };
                        list.appendChild(row);
                    });
                });
            }

            window.selectUser = function (userId, element) {
                currentUserId = userId;
                document.querySelectorAll('#user-list > div').forEach(el => el.classList.remove('bg-light', 'fw-bold'));
                element.classList.add('bg-light', 'fw-bold');
                loadMessages();
            }

            function loadMessages() {
                if (!currentUserId) return;
                fetch(`{{ url('/admin/chat/messages') }}/${currentUserId}`).then(res => res.json()).then(messages => {
                    chatMessages.innerHTML = "";

                    messages.forEach(msg => {
                        const isMe = msg.sender_id == "{{ Auth::id() }}";
                        const wrap = document.createElement('div');
                        wrap.className = 'mb-2 d-flex ' + (isMe ? 'justify-content-end' : 'justify-content-start');

                        const bubble = document.createElement('div');
                        bubble.style.cssText = 'max-width:85%;padding:.45rem .7rem;border-radius:14px;font-size:.85rem;' +
                            (isMe ? 'background:var(--tl-grad-primary);color:#fff' : 'background:var(--tl-surface-2);color:var(--tl-ink)');
                        bubble.textContent = msg.content;

                        wrap.appendChild(bubble);
                        chatMessages.appendChild(wrap);
                    });

                    chatMessages.scrollTop = chatMessages.scrollHeight;
                });
            }

            function sendMessage() {
                let message = chatInput.value.trim();
                if (!message || !currentUserId) return;
                fetch("{{ route('admin.chat.send') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ message: message, user_id: currentUserId })
                }).then(res => res.json()).then(data => {
                    chatInput.value = ""; loadMessages();
                });
            }

            document.getElementById("send-btn").onclick = sendMessage;
            chatInput.onkeypress = (e) => { if (e.key === 'Enter') sendMessage(); };

            setInterval(() => {
                if (chatPopup.style.display === "block") { loadMessages(); loadUsers(); }
            }, 3000);
        });
    </script>

    {{-- Modal được đẩy ra đây - cuối trang, là con trực tiếp của thẻ body.
         Bootstrap khuyến cáo đặt modal ở cấp cao nhất của tài liệu: nếu modal nằm
         lồng trong một phần tử đã tạo stacking context (VD <main> có z-index),
         lớp phủ .modal-backdrop (z-index 1050, gắn thẳng vào body) sẽ nằm ĐÈ lên
         cả modal -> màn hình tối đen, bấm không được. --}}
    @stack('modals')

    @include('partials._scripts')
</body>

</html>
