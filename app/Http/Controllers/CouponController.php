<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Áp dụng / gỡ mã giảm giá ở trang thanh toán.
 * Mã được giữ trong session cho tới khi đặt hàng xong hoặc khách tự gỡ.
 */
class CouponController extends Controller
{
    public const SESSION_KEY = 'coupon_id';

    public function apply(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50'],
        ], [], ['code' => 'Mã giảm giá']);

        $coupon = Coupon::whereRaw('UPPER(code) = ?', [mb_strtoupper(trim($data['code']))])->first();

        if (! $coupon) {
            return back()->with('error', 'Mã giảm giá không tồn tại.');
        }

        $subtotal = $this->cartSubtotal();

        if ($subtotal <= 0) {
            return back()->with('error', 'Giỏ hàng của bạn đang trống.');
        }

        if ($error = $coupon->validateFor(Auth::user(), $subtotal)) {
            return back()->with('error', $error);
        }

        session([self::SESSION_KEY => $coupon->id]);

        $discount = $coupon->discountFor($subtotal);

        return back()->with('success', 'Áp dụng mã "' . $coupon->code . '" thành công, bạn được giảm '
            . number_format($discount, 0, ',', '.') . ' đ.');
    }

    public function remove()
    {
        session()->forget(self::SESSION_KEY);

        return back()->with('success', 'Đã gỡ mã giảm giá khỏi đơn hàng.');
    }

    // Danh sách mã đang chạy để khách xem và tự chọn
    public function available()
    {
        $coupons = Coupon::active()
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->orderByDesc('id')
            ->get()
            ->filter(fn ($coupon) => $coupon->isAvailable())
            ->values();

        return view('coupons.index', compact('coupons'));
    }

    private function cartSubtotal(): int
    {
        $total = 0;

        foreach (session('cart', []) as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        return (int) $total;
    }
}
