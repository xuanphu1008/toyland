{{--
    KHUNG CHAT MỘT CỬA - hai chế độ trong cùng một widget:

      [Trợ lý ToyLand]  chế độ mặc định, bot trả lời tức thì, khách vãng lai
                        cũng dùng được (tìm hàng, phí ship, mã giảm giá...)
      [Nhân viên]       chat trực tiếp với admin, chỉ khi đã đăng nhập

    Gộp làm một thay vì hai nút nổi riêng biệt: màn hình đỡ rối, và luồng
    "hỏi bot trước -> không giải quyết được thì chuyển người thật" đúng với
    cách các sàn thương mại điện tử đang làm.
--}}
<div id="tl-chat" class="tl-chat" data-logged-in="{{ auth()->check() ? '1' : '0' }}">

    {{-- Nút nổi --}}
    <button type="button" id="tl-chat-toggle" class="tl-chat-fab" aria-label="Mở khung chat hỗ trợ">
        <i class="bi bi-chat-dots-fill"></i>
        <span class="tl-chat-badge" id="tl-chat-badge" hidden>1</span>
    </button>

    {{-- Khung hội thoại --}}
    <div class="tl-chat-panel" id="tl-chat-panel" hidden>
        <div class="tl-chat-head">
            <div class="d-flex align-items-center gap-2">
                <span class="tl-chat-avatar" id="tl-chat-avatar">🧸</span>
                <div class="lh-sm">
                    <div class="fw-bold" id="tl-chat-title">Trợ lý ToyLand</div>
                    <div class="tl-chat-sub" id="tl-chat-sub">Trả lời ngay lập tức</div>
                </div>
            </div>
            <button type="button" class="tl-chat-close" id="tl-chat-close" aria-label="Đóng khung chat">✕</button>
        </div>

        {{-- Chuyển chế độ --}}
        <div class="tl-chat-tabs">
            <button type="button" class="tl-chat-tab active" data-tl-chat-mode="bot">
                <i class="bi bi-robot me-1"></i> Trợ lý
            </button>
            <button type="button" class="tl-chat-tab" data-tl-chat-mode="human">
                <i class="bi bi-headset me-1"></i> Nhân viên
            </button>
        </div>

        <div class="tl-chat-body" id="tl-chat-body" aria-live="polite"></div>

        {{-- Câu hỏi gợi ý sẵn, khách bấm là gửi luôn --}}
        <div class="tl-chat-quick" id="tl-chat-quick"></div>

        <form class="tl-chat-foot" id="tl-chat-form" autocomplete="off">
            <input type="text" id="tl-chat-input" class="form-control"
                   placeholder="Nhập câu hỏi của bạn..." maxlength="500" aria-label="Nội dung tin nhắn">
            <button type="submit" class="tl-chat-send" aria-label="Gửi">
                <i class="bi bi-send-fill"></i>
            </button>
        </form>
    </div>
</div>

@push('scripts')
<script>
    window.TL_CHAT = {
        botSend: "{{ route('chatbot.send') }}",
        botGreeting: "{{ route('chatbot.greeting') }}",
        @auth
        humanSend: "{{ route('user.chat.send') }}",
        humanMessages: "{{ route('user.chat.messages') }}",
        @endauth
        loginUrl: "{{ route('login') }}",
        csrf: "{{ csrf_token() }}"
    };
</script>
<script src="{{ asset('js/toyland-chat.js') }}?v={{ filemtime(public_path('js/toyland-chat.js')) }}"></script>
@endpush
