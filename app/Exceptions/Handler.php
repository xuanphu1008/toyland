<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Link xác thực hết hạn/bị sửa đổi làm sai chữ ký -> middleware "signed" ném
     * InvalidSignatureException TRƯỚC KHI vào tới AuthController::verify(), nên phải
     * bắt riêng ở đây để chuyển hướng về Login kèm Alert thay vì trang lỗi 403 mặc định.
     */
    public function render($request, Throwable $e)
    {
        if ($request->routeIs('verification.verify') && $e instanceof InvalidSignatureException) {
            return redirect()->route('login')->with('error', 'Liên kết xác thực đã hết hạn hoặc không hợp lệ!');
        }

        return parent::render($request, $e);
    }
}
