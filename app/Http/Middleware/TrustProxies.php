<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * @var array<int, string>|string|null
     */
    //
    // Render (và hầu hết nền tảng đám mây) nhận HTTPS ở bộ cân bằng tải rồi
    // chuyển tiếp HTTP thường vào container. Phải tin proxy đó thì Laravel
    // mới đọc được X-Forwarded-Proto=https: không có dòng này, mọi link/form
    // sinh ra đều là http:// -> trình duyệt chặn "mixed content" và cookie
    // phiên đăng nhập bị lệch. Container không nhận kết nối trực tiếp từ
    // Internet nên tin mọi proxy ('*') là an toàn.
    protected $proxies = '*';

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;
}
