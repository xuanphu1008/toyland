<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    public const TYPES = [
        'percent' => 'Giảm theo %',
        'fixed' => 'Giảm số tiền cố định',
    ];

    protected $fillable = [
        'code', 'description', 'type', 'value',
        'min_order_amount', 'max_discount',
        'usage_limit', 'usage_limit_per_user', 'used_count',
        'starts_at', 'expires_at', 'is_active',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function usages()
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Mã còn hạn và chưa hết lượt dùng chung
    public function isAvailable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    /**
     * Kiểm tra mã có dùng được cho user + giá trị đơn hiện tại không.
     * Trả về null nếu hợp lệ, hoặc chuỗi thông báo lỗi để hiển thị cho khách.
     */
    public function validateFor(?User $user, int $subtotal): ?string
    {
        if (! $this->is_active) {
            return 'Mã giảm giá đã bị vô hiệu hóa.';
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return 'Mã giảm giá chưa tới thời gian sử dụng (bắt đầu từ ' . $this->starts_at->format('d/m/Y H:i') . ').';
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return 'Mã giảm giá đã hết hạn.';
        }

        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return 'Mã giảm giá đã hết lượt sử dụng.';
        }

        if ($subtotal < $this->min_order_amount) {
            return 'Đơn hàng cần tối thiểu ' . number_format($this->min_order_amount, 0, ',', '.') . ' đ để dùng mã này.';
        }

        if ($user && $this->usage_limit_per_user > 0) {
            $used = CouponUsage::where('coupon_id', $this->id)->where('user_id', $user->id)->count();

            if ($used >= $this->usage_limit_per_user) {
                return 'Bạn đã dùng hết lượt cho mã giảm giá này.';
            }
        }

        return null;
    }

    // Số tiền được giảm với 1 giá trị đơn hàng cụ thể
    public function discountFor(int $subtotal): int
    {
        if ($this->type === 'fixed') {
            return min((int) $this->value, $subtotal);
        }

        $discount = (int) floor($subtotal * $this->value / 100);

        if ($this->max_discount) {
            $discount = min($discount, (int) $this->max_discount);
        }

        return min($discount, $subtotal);
    }

    public function getValueLabelAttribute(): string
    {
        return $this->type === 'percent'
            ? $this->value . '%' . ($this->max_discount ? ' (tối đa ' . number_format($this->max_discount, 0, ',', '.') . ' đ)' : '')
            : number_format($this->value, 0, ',', '.') . ' đ';
    }
}
