<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Chặn khu vực quản trị.
 *
 * VÌ SAO CHUYỂN HƯỚNG CHỨ KHÔNG abort(403):
 * Bản cũ gọi abort(403) nên khách thường bấm nhầm một link /admin là rơi thẳng
 * vào trang lỗi trắng của Laravel - không có thanh điều hướng, không có đường
 * quay lại, người dùng tưởng web hỏng.
 *
 * Nguyên tắc: LỖI CỦA NGƯỜI DÙNG thì đưa họ về chỗ làm được việc kèm lời giải
 * thích, chỉ giữ trang lỗi cho những tình huống thật sự bất thường.
 *
 * Vẫn trả 403 dạng JSON cho lời gọi AJAX, vì JavaScript cần MÃ LỖI để xử lý
 * chứ không cần một trang HTML chuyển hướng.
 */
class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Chưa đăng nhập. Thường middleware 'auth' đã chặn trước, nhưng vẫn xử
        // lý ở đây phòng khi route nào đó chỉ khai 'admin' mà quên 'auth'.
        if (! Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Bạn cần đăng nhập.'], 401);
            }

            return redirect()->route('login')
                ->with('error', 'Bạn cần đăng nhập bằng tài khoản quản trị để vào khu vực này.');
        }

        // Đã đăng nhập nhưng không phải admin
        if (! Auth::user()->isAdmin()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Bạn không có quyền truy cập khu vực quản trị.'], 403);
            }

            // Dọn địa chỉ "định vào" đang trỏ tới khu quản trị. Không dọn thì
            // lần đăng nhập sau redirect()->intended() lại ném họ về /admin và
            // bị chặn tiếp - quanh quẩn mãi không thoát.
            $request->session()->forget('url.intended');

            return redirect()->route('home')
                ->with('error', 'Khu vực quản trị chỉ dành cho nhân viên. Bạn đã được đưa về trang chủ.');
        }

        return $next($request);
    }
}
