<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

        'ghn' => [
        'base_url' => env('GHN_BASE_URL'),
        'token' => env('GHN_TOKEN'),
        'shop_id' => env('GHN_SHOP_ID'),
        'verify_ssl' => env('GHN_VERIFY_SSL', true),
        'from_district_id' => env('GHN_FROM_DISTRICT_ID'),
    ],

    // Cổng thanh toán MoMo (v2 - AIO). Sandbox: https://test-payment.momo.vn
    'momo' => [
        'endpoint' => env('MOMO_ENDPOINT', 'https://test-payment.momo.vn/v2/gateway/api/create'),
        'partner_code' => env('MOMO_PARTNER_CODE'),
        'access_key' => env('MOMO_ACCESS_KEY'),
        'secret_key' => env('MOMO_SECRET_KEY'),
        'partner_name' => env('MOMO_PARTNER_NAME', 'ToyLand'),
        'store_id' => env('MOMO_STORE_ID', 'ToyLandStore'),
        // Phương thức MẶC ĐỊNH khi khách không chọn gì trên form checkout.
        // Khách có thể tự chọn: captureWallet (ví/QR) | payWithATM (thẻ ATM nội địa)
        // | payWithCC (Visa/Master/JCB) | payWithMethod (hiện đủ phương thức tại cổng MoMo).
        // Danh sách hợp lệ khai báo trong App\Services\MomoService::PAYMENT_OPTIONS.
        'request_type' => env('MOMO_REQUEST_TYPE', 'captureWallet'),
        'lang' => env('MOMO_LANG', 'vi'),
        // URL MoMo redirect khách về sau khi thanh toán (GET) và URL nhận webhook IPN (POST)
        'redirect_url' => env('MOMO_REDIRECT_URL'),
        'ipn_url' => env('MOMO_IPN_URL'),
        // Sandbox MoMo hay lỗi chứng chỉ trên Windows/Laragon -> cho phép tắt verify SSL ở local
        'verify_ssl' => env('MOMO_VERIFY_SSL', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Chatbot - mô hình ngôn ngữ (TÙY CHỌN)
    |--------------------------------------------------------------------------
    | Bot nội bộ (App\Services\Chatbot) xử lý toàn bộ nghiệp vụ và LUÔN chạy
    | được kể cả khi không có mạng. Phần dưới đây chỉ dùng để đỡ những câu hỏi
    | ngoài kịch bản. KHÔNG khai gì thì hệ thống vẫn hoạt động bình thường.
    |
    | Khai trong .env nếu muốn bật:
    |   CHATBOT_LLM_DRIVER=gemini        # none | openai | gemini
    |   CHATBOT_LLM_KEY=...              # API key từ Google AI Studio
    |   CHATBOT_LLM_MODEL=...            # tên model Flash-Lite/Flash trong AI Studio
    |   CHATBOT_LLM_VERIFY_SSL=false     # đặt false nếu Laragon báo lỗi chứng chỉ
    */
    'chatbot' => [
        'driver' => env('CHATBOT_LLM_DRIVER', 'none'),
        'key' => env('CHATBOT_LLM_KEY'),
        // Bỏ trống thì mỗi driver tự chọn mặc định (xem LlmClient)
        'model' => env('CHATBOT_LLM_MODEL'),
        // Chấp nhận nhiều tên biến cho dễ dùng: nhiều hướng dẫn chạy model
        // nội bộ (Ollama, LM Studio, vLLM) bảo đặt OPENAI_BASE_URL/OPENAI_BASE_URI.
        // Ví dụ Ollama: http://localhost:11434/v1
        'base_url' => env('CHATBOT_LLM_BASE_URL')
            ?: env('OPENAI_BASE_URL')
            ?: env('OPENAI_BASE_URI')
            ?: 'https://api.openai.com/v1',

        // Model chạy trên máy (CPU) sinh chữ chậm hơn dịch vụ đám mây nhiều,
        // nên để mặc định rộng tay 45 giây thay vì 12.
        'timeout' => (int) env('CHATBOT_LLM_TIMEOUT', 45),

        'verify_ssl' => env('CHATBOT_LLM_VERIFY_SSL', true),

        // ---- Gemini: tắt bớt "suy nghĩ" nội bộ cho nhanh và đỡ tốn token ----
        // minimal | low | medium | high | off (off = không gửi tham số này)
        'thinking_level' => env('CHATBOT_LLM_THINKING', 'minimal'),
        // Trần token đầu ra (tính cả phần suy nghĩ). Độ dài câu trả lời do prompt khống chế.
        'max_output_tokens' => (int) env('CHATBOT_LLM_MAX_OUTPUT_TOKENS', 1024),

        // ---- Giới hạn dữ liệu gửi lên AI ----
        'max_input_chars' => (int) env('CHATBOT_LLM_MAX_INPUT_CHARS', 500),
        'history_turns' => (int) env('CHATBOT_LLM_HISTORY_TURNS', 4),
        'history_chars' => (int) env('CHATBOT_LLM_HISTORY_CHARS', 300),

        // ---- Hạn mức (đặt THẤP hơn trần gói miễn phí trong AI Studio) ----
        // Xem trần thật của project tại https://aistudio.google.com (mục Rate limit)
        'rpm' => (int) env('CHATBOT_LLM_RPM', 4),
        'rpd' => (int) env('CHATBOT_LLM_RPD', 18),
        'per_client_day' => (int) env('CHATBOT_LLM_PER_CLIENT_DAY', 5),
        // Câu hỏi mở đầu giống hệt nhau dùng lại câu trả lời trong ngần này phút
        'answer_cache_minutes' => (int) env('CHATBOT_LLM_CACHE_MINUTES', 360),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cloudinary - nơi lưu ảnh tải lên (sản phẩm, bài viết)
    |--------------------------------------------------------------------------
    | Máy chủ Render gói miễn phí XÓA SẠCH ổ đĩa mỗi lần khởi động lại, nên ảnh
    | ghi vào public/uploads sẽ mất. Khai Cloudinary thì ảnh nằm trên CDN của họ.
    |
    | Cách khai nhanh nhất: dán nguyên chuỗi "API environment variable" trong
    | trang Dashboard của Cloudinary vào CLOUDINARY_URL:
    |   CLOUDINARY_URL=cloudinary://<api_key>:<api_secret>@<cloud_name>
    | Hoặc khai rời 3 biến CLOUDINARY_CLOUD_NAME / _API_KEY / _API_SECRET.
    | Không khai gì -> ảnh lưu vào public/uploads như cũ (phù hợp khi chạy máy nhà).
    */
    'cloudinary' => [
        'url' => env('CLOUDINARY_URL'),
        'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
        'api_key' => env('CLOUDINARY_API_KEY'),
        'api_secret' => env('CLOUDINARY_API_SECRET'),
        // Thư mục gốc trên Cloudinary; ảnh sản phẩm vào <folder>/products, bài viết vào <folder>/posts
        'folder' => env('CLOUDINARY_FOLDER', 'toyland'),
        // Thu nhỏ ảnh quá khổ ngay lúc tải lên để tiết kiệm dung lượng gói miễn phí
        'max_dimension' => (int) env('CLOUDINARY_MAX_DIMENSION', 1600),
        'timeout' => (int) env('CLOUDINARY_TIMEOUT', 30),
        'verify_ssl' => env('CLOUDINARY_VERIFY_SSL', true),
    ],

    // Đăng nhập bằng tài khoản Google (OAuth 2.0).
    // Lấy Client ID / Client Secret tại https://console.cloud.google.com/apis/credentials
    // và khai 3 biến GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, GOOGLE_REDIRECT_URI trong .env.
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', env('APP_URL') . '/auth/google/callback'),
        // Tắt verify SSL khi chạy local trên Windows/Laragon nếu gặp lỗi chứng chỉ
        'verify_ssl' => env('GOOGLE_VERIFY_SSL', true),
    ],
];
