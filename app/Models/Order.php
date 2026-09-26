<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    /**
     * Vòng đời đơn hàng theo yêu cầu đề bài:
     * đang xử lý -> đã xác nhận -> đã đóng gói -> đang vận chuyển -> đã giao.
     * 'cancelled' có thể xảy ra ở bất kỳ bước nào trước 'completed'.
     */
    public const STATUSES = [
        'pending'   => 'Chờ xác nhận',
        'confirmed' => 'Đã xác nhận',
        'packed'    => 'Đã đóng gói',
        'shipping'  => 'Đang vận chuyển',
        'completed' => 'Đã giao thành công',
        'cancelled' => 'Đã hủy',
    ];

    // Màu badge Bootstrap tương ứng từng trạng thái
    public const STATUS_COLORS = [
        'pending'   => 'bg-secondary',
        'confirmed' => 'bg-info text-dark',
        'packed'    => 'bg-primary',
        'shipping'  => 'bg-warning text-dark',
        'completed' => 'bg-success',
        'cancelled' => 'bg-danger',
    ];

    /**
     * Khi admin đổi trạng thái đơn, shipping_status được đồng bộ theo bảng này
     * để các tab lọc và báo cáo (vốn dựa trên shipping_status) vẫn đúng.
     */
    public const STATUS_TO_SHIPPING = [
        'pending'   => 'not_shipped',
        'confirmed' => 'ready_to_pick',
        'packed'    => 'picked',
        'shipping'  => 'delivering',
        'completed' => 'delivered',
        'cancelled' => 'cancelled',
    ];

    // Các trạng thái cũ còn sót trong DB trước khi chuẩn hóa (giữ để không vỡ dữ liệu cũ)
    public const LEGACY_STATUSES = ['paid', 'paid_momo', 'cod_ordered', 'cod_paid'];

    protected $fillable = [
        'user_id',
        'total_price',
        'status',
        'payment_method',
        'payment_status',
        'shipping_address',
        'customer_phone',
        'shipping_status',
        'ghn_order_code',
        'ghn_total_fee',
        'to_district_id',
        'to_ward_code',
        'coupon_id',
        'discount_amount',
        'points_earned',
        'admin_note',
        'cancelled_at',
        'cancel_reason',
        'restocked_at',
    ];

    protected $casts = [
        'cancelled_at' => 'datetime',
        'restocked_at' => 'datetime',
    ];

    // 1 Đơn hàng thuộc về 1 User (Quan hệ 1-1)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 1 Đơn hàng có nhiều dòng chi tiết OrderItem (Quan hệ 1-N)
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    // 1 Đơn hàng có nhiều lần giao dịch thanh toán (thanh toán lại khi lần trước thất bại)
    public function paymentTransactions()
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    // Lần giao dịch gần nhất - dùng để hiển thị trạng thái/nút "Thanh toán lại" ở trang lịch sử đơn
    public function latestPaymentTransaction()
    {
        return $this->hasOne(PaymentTransaction::class)->latestOfMany();
    }

    // Mã giảm giá đã áp dụng cho đơn (nếu có)
    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'bg-secondary';
    }

    /**
     * Khách chỉ được tự hủy đơn khi shop chưa đóng gói/giao,
     * và đơn chưa được thanh toán online (đã trả tiền thì phải liên hệ để hoàn tiền).
     */
    public function canBeCancelledByCustomer(): bool
    {
        return in_array($this->status, ['pending', 'confirmed'], true) && ! $this->isPaid();
    }

    // Admin được hủy mọi đơn chưa giao thành công và chưa bị hủy
    public function canBeCancelledByAdmin(): bool
    {
        return ! in_array($this->status, ['completed', 'cancelled'], true);
    }
}
