<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // MoMo bắn webhook IPN từ server của họ (server-to-server), không có session
        // nên không thể kèm CSRF token -> phải miễn kiểm tra CSRF cho riêng route này.
        // Bù lại, MomoController@ipn luôn xác thực chữ ký HMAC SHA256 trước khi xử lý.
        'payment/momo/ipn',
    ];
}
