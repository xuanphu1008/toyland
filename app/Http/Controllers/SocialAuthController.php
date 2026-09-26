<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Đăng nhập bằng tài khoản Google theo chuẩn OAuth 2.0 Authorization Code.
 *
 * Cố tình KHÔNG dùng package laravel/socialite để dự án không phải cài thêm
 * thư viện - toàn bộ luồng chỉ cần Http client sẵn có của Laravel.
 *
 * Luồng: redirect() -> Google hỏi ý người dùng -> callback() nhận ?code
 *        -> đổi code lấy access_token -> gọi userinfo -> tạo/đăng nhập user.
 */
class SocialAuthController extends Controller
{
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const USERINFO_URL = 'https://www.googleapis.com/oauth2/v3/userinfo';

    public function redirect(Request $request)
    {
        if (! $this->isConfigured()) {
            return redirect()->route('login')
                ->with('error', 'Đăng nhập Google chưa được cấu hình. Vui lòng khai GOOGLE_CLIENT_ID và GOOGLE_CLIENT_SECRET trong file .env.');
        }

        // state chống tấn công CSRF: sinh ngẫu nhiên, lưu session và đối chiếu ở callback
        $state = Str::random(40);
        $request->session()->put('google_oauth_state', $state);

        $query = http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => config('services.google.redirect'),
            'response_type' => 'code',
            'scope' => 'openid profile email',
            'state' => $state,
            'prompt' => 'select_account',
        ]);

        return redirect()->away(self::AUTH_URL . '?' . $query);
    }

    public function callback(Request $request)
    {
        if (! $this->isConfigured()) {
            return redirect()->route('login')->with('error', 'Đăng nhập Google chưa được cấu hình.');
        }

        // Người dùng bấm "Hủy" ở màn hình đồng ý của Google
        if ($request->filled('error')) {
            return redirect()->route('login')->with('error', 'Bạn đã hủy đăng nhập bằng Google.');
        }

        $expectedState = $request->session()->pull('google_oauth_state');

        if (! $request->filled('code') || ! $expectedState || $request->input('state') !== $expectedState) {
            return redirect()->route('login')->with('error', 'Phiên đăng nhập Google không hợp lệ hoặc đã hết hạn, vui lòng thử lại.');
        }

        try {
            $verify = (bool) config('services.google.verify_ssl', true);

            $tokenResponse = Http::asForm()->withOptions(['verify' => $verify])->post(self::TOKEN_URL, [
                'code' => $request->input('code'),
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'redirect_uri' => config('services.google.redirect'),
                'grant_type' => 'authorization_code',
            ]);

            if (! $tokenResponse->successful()) {
                throw new \RuntimeException('Google từ chối cấp access token: ' . $tokenResponse->body());
            }

            $accessToken = $tokenResponse->json('access_token');

            $profileResponse = Http::withToken($accessToken)
                ->withOptions(['verify' => $verify])
                ->get(self::USERINFO_URL);

            if (! $profileResponse->successful()) {
                throw new \RuntimeException('Không lấy được thông tin tài khoản Google.');
            }

            $profile = $profileResponse->json();
        } catch (Throwable $e) {
            Log::error('Lỗi đăng nhập Google', ['message' => $e->getMessage()]);

            return redirect()->route('login')->with('error', 'Không kết nối được tới Google, vui lòng thử lại hoặc đăng nhập bằng email.');
        }

        $email = $profile['email'] ?? null;
        $googleId = $profile['sub'] ?? null;

        if (! $email || ! $googleId) {
            return redirect()->route('login')->with('error', 'Tài khoản Google không cung cấp email, không thể đăng nhập.');
        }

        $user = User::where('google_id', $googleId)->orWhere('email', $email)->first();

        if ($user) {
            // Liên kết tài khoản email sẵn có với Google lần đầu đăng nhập kiểu này
            $user->google_id = $googleId;
            $user->avatar = $profile['picture'] ?? $user->avatar;

            // Google đã xác minh email hộ, khỏi bắt khách xác thực lại
            if (! $user->hasVerifiedEmail() && ($profile['email_verified'] ?? false)) {
                $user->email_verified_at = now();
            }

            $user->save();
        } else {
            $user = User::create([
                'name' => $profile['name'] ?? Str::before($email, '@'),
                'email' => $email,
                // Tài khoản Google không có mật khẩu, sinh chuỗi ngẫu nhiên để thỏa ràng buộc NOT NULL.
                // Khách muốn đăng nhập bằng mật khẩu thì dùng chức năng đổi mật khẩu trong hồ sơ.
                'password' => Hash::make(Str::random(40)),
                'role' => 'user',
                'google_id' => $googleId,
                'avatar' => $profile['picture'] ?? null,
            ]);

            $user->email_verified_at = ($profile['email_verified'] ?? false) ? now() : null;
            $user->save();
        }

        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('login')->with('error', 'Tài khoản của bạn chưa được xác thực email. Vui lòng kiểm tra hộp thư.');
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        if ($user->isAdmin()) {
            return redirect()->intended(route('admin.dashboard'));
        }

        return redirect()->intended('/')->with('success', 'Đăng nhập bằng Google thành công. Chào mừng ' . $user->name . '!');
    }

    private function isConfigured(): bool
    {
        return (bool) config('services.google.client_id') && (bool) config('services.google.client_secret');
    }
}
