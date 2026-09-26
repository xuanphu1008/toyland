<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// Quản lý mã giảm giá / voucher khuyến mãi
class CouponController extends Controller
{
    public function index(Request $request)
    {
        $query = Coupon::withCount('usages');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', '%'.$search.'%')
                  ->orWhere('description', 'like', '%'.$search.'%');
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $coupons = $query->orderByDesc('id')->paginate(15)->appends($request->query());

        return view('admin.coupons.index', compact('coupons'));
    }

    public function create()
    {
        return view('admin.coupons.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateCoupon($request);

        Coupon::create($data);

        return redirect()->route('admin.coupons.index')->with('success', 'Đã tạo mã giảm giá ' . $data['code'] . '.');
    }

    public function edit(Coupon $coupon)
    {
        return view('admin.coupons.edit', compact('coupon'));
    }

    public function update(Request $request, Coupon $coupon)
    {
        $data = $this->validateCoupon($request, $coupon->id);

        $coupon->update($data);

        return redirect()->route('admin.coupons.index')->with('success', 'Đã cập nhật mã ' . $coupon->code . '.');
    }

    public function destroy(Coupon $coupon)
    {
        // Mã đã có người dùng thì chỉ nên tắt, không xóa, để giữ lịch sử đơn hàng
        if ($coupon->usages()->exists()) {
            $coupon->update(['is_active' => false]);

            return redirect()->route('admin.coupons.index')
                ->with('success', 'Mã đã từng được sử dụng nên được chuyển sang trạng thái ngưng thay vì xóa.');
        }

        $coupon->delete();

        return redirect()->route('admin.coupons.index')->with('success', 'Đã xóa mã giảm giá.');
    }

    private function validateCoupon(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9_-]+$/', Rule::unique('coupons', 'code')->ignore($ignoreId)],
            'description' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_keys(Coupon::TYPES))],
            'value' => ['required', 'integer', 'min:1'],
            'min_order_amount' => ['nullable', 'integer', 'min:0'],
            'max_discount' => ['nullable', 'integer', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'usage_limit_per_user' => ['required', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:starts_at'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'code.regex' => 'Mã chỉ được chứa chữ cái, số, dấu gạch ngang và gạch dưới.',
            'expires_at.after' => 'Ngày hết hạn phải sau ngày bắt đầu.',
        ], [
            'code' => 'Mã giảm giá',
            'type' => 'Loại giảm giá',
            'value' => 'Giá trị giảm',
            'usage_limit_per_user' => 'Số lượt mỗi khách',
        ]);

        // Giảm theo % thì giá trị không được vượt quá 100
        if ($data['type'] === 'percent' && $data['value'] > 100) {
            $data['value'] = 100;
        }

        $data['code'] = mb_strtoupper($data['code']);
        $data['min_order_amount'] = $data['min_order_amount'] ?? 0;
        $data['is_active'] = $request->boolean('is_active');

        // max_discount chỉ có ý nghĩa với loại giảm theo %
        if ($data['type'] === 'fixed') {
            $data['max_discount'] = null;
        }

        return $data;
    }
}
