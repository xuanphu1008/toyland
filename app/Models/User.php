<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Hạng thành viên xét theo tổng chi tiêu tích lũy (lifetime_spent).
     * discount = % giảm giá tự động áp dụng cho mọi đơn hàng của hạng đó.
     */
    public const TIERS = [
        'diamond' => ['label' => 'Kim Cương', 'min' => 15000000, 'discount' => 8,  'color' => 'bg-primary',            'icon' => '💎'],
        'gold'    => ['label' => 'Vàng',      'min' => 5000000,  'discount' => 5,  'color' => 'bg-warning text-dark',  'icon' => '🥇'],
        'silver'  => ['label' => 'Bạc',       'min' => 1000000,  'discount' => 2,  'color' => 'bg-secondary',          'icon' => '🥈'],
        'bronze'  => ['label' => 'Đồng',      'min' => 0,        'discount' => 0,  'color' => 'bg-light text-dark',    'icon' => '🥉'],
    ];

    // Cứ 10.000đ chi tiêu ở đơn đã giao thành công thì được 1 điểm
    public const POINTS_PER_VND = 10000;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'points',
        'lifetime_spent',
        'google_id',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    // ===================== QUAN HỆ =====================

    // 1 User có nhiều Đơn hàng (Quan hệ 1-N)
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // 1 User có nhiều Địa chỉ nhận hàng (Quan hệ 1-N)
    public function addresses()
    {
        return $this->hasMany(UserAddress::class);
    }

    // 1 User có nhiều Đánh giá sản phẩm (Quan hệ 1-N)
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    // Danh sách yêu thích
    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    /** Hồ sơ các bé mà tài khoản này đã khai */
    public function children()
    {
        return $this->hasMany(ChildProfile::class)->orderBy('birthday');
    }

    // Nhật ký điểm thưởng
    public function pointTransactions()
    {
        return $this->hasMany(PointTransaction::class);
    }

    // Lịch sử duyệt sản phẩm
    public function productViews()
    {
        return $this->hasMany(ProductView::class);
    }

    // Phiếu hỗ trợ
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    // ===================== MUA HÀNG & ĐÁNH GIÁ =====================

    /**
     * Kiểm tra khách đã từng mua (và nhận thành công) sản phẩm này chưa.
     * Đây là điều kiện bắt buộc để được viết đánh giá.
     */
    public function hasPurchased(int $productId): bool
    {
        return Order::where('user_id', $this->id)
            ->where('status', 'completed')
            ->whereHas('items', fn ($q) => $q->where('product_id', $productId))
            ->exists();
    }

    // Đơn hàng đã giao thành công gần nhất có chứa sản phẩm này (dùng làm bằng chứng mua hàng)
    public function completedOrderFor(int $productId): ?Order
    {
        return Order::where('user_id', $this->id)
            ->where('status', 'completed')
            ->whereHas('items', fn ($q) => $q->where('product_id', $productId))
            ->latest('id')
            ->first();
    }

    public function hasWishlisted(int $productId): bool
    {
        return $this->wishlists()->where('product_id', $productId)->exists();
    }

    // ===================== KHÁCH HÀNG THÂN THIẾT =====================

    // Khóa hạng hiện tại: diamond | gold | silver | bronze
    public function getTierAttribute(): string
    {
        foreach (self::TIERS as $key => $tier) {
            if ($this->lifetime_spent >= $tier['min']) {
                return $key;
            }
        }

        return 'bronze';
    }

    public function getTierInfoAttribute(): array
    {
        return self::TIERS[$this->tier];
    }

    public function getTierLabelAttribute(): string
    {
        return $this->tier_info['label'];
    }

    // % giảm giá tự động của hạng thành viên
    public function getTierDiscountAttribute(): int
    {
        return (int) $this->tier_info['discount'];
    }

    /**
     * Thông tin hạng kế tiếp và số tiền còn thiếu để lên hạng.
     * Trả về null nếu đã ở hạng cao nhất.
     */
    public function getNextTierAttribute(): ?array
    {
        $tiers = array_reverse(self::TIERS, true); // bronze -> diamond
        $found = false;

        foreach ($tiers as $key => $tier) {
            if ($found) {
                return [
                    'key' => $key,
                    'label' => $tier['label'],
                    'icon' => $tier['icon'],
                    'min' => $tier['min'],
                    'remaining' => max(0, $tier['min'] - $this->lifetime_spent),
                ];
            }

            if ($key === $this->tier) {
                $found = true;
            }
        }

        return null;
    }
}
