<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Throwable;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'Email hoặc mật khẩu không đúng.'])
                ->onlyInput('email');
        }

        // Chặn cứng: chưa xác thực email thì không được phép đăng nhập
        if (! Auth::user()->hasVerifiedEmail()) {
            $user = Auth::user();
            Auth::logout();

            // Người chưa xác thực bị chặn đăng nhập nên KHÔNG tự bấm "gửi lại" được.
            // Nếu email lúc đăng ký bị lỗi (máy chủ thư trục trặc) thì họ kẹt mãi.
            // Vì vậy gửi lại link ngay tại đây, tối đa 1 lần / 10 phút / tài khoản
            // để không thành công cụ spam hộp thư người khác.
            $resent = Cache::add('verify-resend:' . $user->id, true, now()->addMinutes(10))
                && $this->sendVerification($user);

            return redirect()->route('login')->with('error', 'Tài khoản của bạn chưa được xác thực. '
                . ($resent ? 'Chúng tôi vừa gửi lại link xác nhận tới email của bạn. ' : '')
                . 'Vui lòng kiểm tra Hộp thư đến (hoặc Spam) để bấm link xác nhận trước khi đăng nhập!');
        }

        $request->session()->regenerate();

        // Admin vào thẳng Dashboard, người dùng thường về trang chủ
        if (Auth::user()->isAdmin()) {
            return redirect()->intended(route('admin.dashboard'));
        }

        // Khách thường mà địa chỉ "định vào" lại là khu quản trị thì bỏ nó đi.
        //
        // Tình huống thật: khách chưa đăng nhập bấm nhầm một link /admin ->
        // middleware 'auth' lưu /admin làm nơi định vào rồi đẩy về trang đăng
        // nhập. Đăng nhập xong, intended() lại ném họ về /admin và bị chặn
        // ngay - vừa đăng nhập xong đã thấy thông báo bị từ chối, rất khó hiểu.
        if ($this->intendsAdminArea($request)) {
            $request->session()->forget('url.intended');
        }

        return redirect()->intended('/');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            // Luôn ép role = 'user': không bao giờ tin dữ liệu role từ form đăng ký công khai
            'role' => 'user',
        ]);

        // Không tự động đăng nhập: bắt buộc phải xác thực email trước khi vào hệ thống.
        // Gửi thư lỗi cũng không được làm hỏng việc đăng ký (tài khoản đã tạo rồi):
        // lần đăng nhập đầu tiên hệ thống sẽ tự gửi lại link.
        if (! $this->sendVerification($user)) {
            return redirect()->route('login')->with('error', 'Đã tạo tài khoản nhưng chưa gửi được email xác thực. '
                . 'Bạn hãy thử đăng nhập sau ít phút, hệ thống sẽ tự gửi lại link xác nhận.');
        }

        return redirect()->route('register.success');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function showRegisterSuccess()
    {
        return view('auth.register-success');
    }

    public function showVerifyNotice()
    {
        return view('auth.verify-email');
    }

    public function verify(Request $request, $id, $hash)
    {
        // Link hết hạn hoặc bị chỉnh sửa (id/hash) đều làm sai lệch chữ ký -> chặn ở đây
        if (! $request->hasValidSignature()) {
            return redirect()->route('login')->with('error', 'Liên kết xác thực đã hết hạn hoặc không hợp lệ!');
        }

        $user = User::findOrFail($id);

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return redirect()->route('login')->with('success', '🎉 Xác thực email thành công! Vui lòng đăng nhập để tiếp tục.');
    }

    public function resendVerification(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect('/');
        }

        if (! $this->sendVerification($request->user())) {
            return back()->with('error', 'Chưa gửi được email xác thực, vui lòng thử lại sau ít phút.');
        }

        return back()->with('success', 'Đã gửi lại email xác thực. Vui lòng kiểm tra hộp thư của bạn.');
    }

    /** Gửi email xác thực; lỗi máy chủ thư thì ghi log và trả false thay vì trang 500 */
    private function sendVerification(User $user): bool
    {
        try {
            $user->sendEmailVerificationNotification();

            return true;
        } catch (Throwable $e) {
            Log::warning('Không gửi được email xác thực cho user #' . $user->id, ['message' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Địa chỉ mà người dùng "định vào" trước khi bị đẩy về trang đăng nhập có
     * nằm trong khu quản trị không.
     *
     * So bằng ĐƯỜNG DẪN chứ không phải chuỗi con: dùng strpos đơn thuần thì
     * một sản phẩm tên "/san-pham/bo-do-choi-admin" cũng bị tính là khu quản trị.
     */
    private function intendsAdminArea(Request $request): bool
    {
        $intended = $request->session()->get('url.intended');

        if (! $intended) {
            return false;
        }

        $path = trim((string) parse_url($intended, PHP_URL_PATH), '/');

        return $path === 'admin' || str_starts_with($path, 'admin/');
    }
}
