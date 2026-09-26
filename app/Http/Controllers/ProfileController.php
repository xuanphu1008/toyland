<?php

namespace App\Http\Controllers;

use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Quản lý hồ sơ cá nhân của khách hàng:
 * thông tin tài khoản, đổi mật khẩu và sổ địa chỉ nhận hàng.
 */
class ProfileController extends Controller
{
    use \App\Http\Controllers\Concerns\HandlesAddressForm;

    public function edit()
    {
        $user = Auth::user();

        $addresses = $user->addresses()->orderByDesc('is_default')->latest()->get();

        $stats = [
            'orders' => $user->orders()->count(),
            'completed' => $user->orders()->where('status', 'completed')->count(),
            'spent' => (int) $user->lifetime_spent,
            'reviews' => $user->reviews()->count(),
            'wishlist' => $user->wishlists()->count(),
            'tickets' => $user->tickets()->count(),
        ];

        // Nhật ký cộng/trừ điểm thưởng của khách
        $pointHistory = $user->pointTransactions()->latest('id')->limit(20)->get();

        return view('profile.edit', compact('user', 'addresses', 'stats', 'pointHistory'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ], [], [
            'name' => 'Họ và tên',
            'email' => 'Email',
        ]);

        $emailChanged = $data['email'] !== $user->email;

        $user->name = $data['name'];
        $user->email = $data['email'];

        // Đổi email thì phải xác thực lại địa chỉ mới trước khi dùng tiếp
        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($emailChanged) {
            try {
                $user->sendEmailVerificationNotification();
            } catch (\Throwable $e) {
                // Hồ sơ đã lưu; lần đăng nhập sau hệ thống tự gửi lại link xác thực
                \Illuminate\Support\Facades\Log::warning('Không gửi được email xác thực cho user #' . $user->id, ['message' => $e->getMessage()]);

                return redirect()->route('profile.edit')
                    ->with('error', 'Đã cập nhật hồ sơ nhưng chưa gửi được email xác thực tới địa chỉ mới. Hãy thử lại sau ít phút.');
            }

            return redirect()->route('profile.edit')
                ->with('success', 'Đã cập nhật hồ sơ. Vì bạn đổi email nên hệ thống đã gửi link xác thực tới địa chỉ mới, vui lòng kiểm tra hộp thư.');
        }

        return redirect()->route('profile.edit')->with('success', 'Đã cập nhật thông tin cá nhân.');
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(6)],
        ], [], [
            'current_password' => 'Mật khẩu hiện tại',
            'password' => 'Mật khẩu mới',
        ]);

        if (! Hash::check($data['current_password'], $user->password)) {
            return back()
                ->withErrors(['current_password' => 'Mật khẩu hiện tại không đúng.'])
                ->with('tab', 'password');
        }

        $user->password = Hash::make($data['password']);
        $user->save();

        return redirect()->route('profile.edit')->with('success', 'Đã đổi mật khẩu thành công.');
    }

    public function storeAddress(Request $request)
    {
        $user = Auth::user();

        $request->validate($this->addressRules(), $this->addressMessages(), $this->addressAttributes());

        $data = $this->addressPayload($request);

        // Địa chỉ đầu tiên tự động là mặc định
        $data['is_default'] = $user->addresses()->count() === 0;

        $user->addresses()->create($data);

        return $this->redirectAfterAddress($request, 'Đã thêm địa chỉ mới vào sổ địa chỉ.');
    }

    public function updateAddress(Request $request, UserAddress $address)
    {
        abort_if((int) $address->user_id !== (int) Auth::id(), 403);

        $request->validate($this->addressRules(), $this->addressMessages(), $this->addressAttributes());

        $address->update($this->addressPayload($request));

        return $this->redirectAfterAddress($request, 'Đã cập nhật địa chỉ.');
    }

    public function setDefaultAddress(UserAddress $address)
    {
        abort_if((int) $address->user_id !== (int) Auth::id(), 403);

        DB::transaction(function () use ($address) {
            UserAddress::where('user_id', Auth::id())->update(['is_default' => false]);
            $address->update(['is_default' => true]);
        });

        return redirect()->route('profile.edit')->with('success', 'Đã đặt làm địa chỉ mặc định.');
    }

    public function destroyAddress(UserAddress $address)
    {
        abort_if((int) $address->user_id !== (int) Auth::id(), 403);

        $wasDefault = $address->is_default;
        $address->delete();

        // Nếu vừa xóa địa chỉ mặc định thì nâng địa chỉ còn lại gần nhất lên làm mặc định
        if ($wasDefault) {
            $next = UserAddress::where('user_id', Auth::id())->latest('id')->first();
            if ($next) {
                $next->update(['is_default' => true]);
            }
        }

        return redirect()->route('profile.edit')->with('success', 'Đã xóa địa chỉ khỏi sổ địa chỉ.');
    }

    /**
     * Form địa chỉ được dùng ở cả trang Hồ sơ lẫn trang Thanh toán.
     * Input ẩn `redirect_to=checkout` cho phép quay lại đúng nơi khách đang đứng
     * thay vì luôn nhảy về trang hồ sơ.
     */
    private function redirectAfterAddress(Request $request, string $message)
    {
        $target = $request->input('redirect_to') === 'checkout'
            ? route('checkout.index')
            : route('profile.edit');

        return redirect($target)->with('success', $message);
    }
}
