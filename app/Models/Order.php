<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    /**
     * VÒNG ĐỜI ĐƠN HÀNG - chi tiết chuyển trạng thái nằm ở App\Services\Orders\OrderWorkflow.
     *
     *   awaiting_payment ─(MoMo báo đã trả tiền)─▶ pending ─(shop xác nhận)─▶ confirmed
     *        │ quá 30 phút                                                        │ shop đóng gói
     *        ▼                                                                    ▼  + tạo MÃ VẬN ĐƠN
     *    cancelled ◀──── shop/khách hủy (trước khi bên vận chuyển lấy hàng) ── packed
     *                                                                            │ bên vận chuyển lấy hàng
     *                                                                            ▼
     *                                         completed ◀─(giao thành công)─ shipping
     *                                                                            │ giao thất bại, chuyển hoàn
     *                                                                            ▼
     *                                                       returned ◀────── returning
     *
     * Đơn COD bắt đầu thẳng từ 'pending' (không cần chờ thanh toán).
     */
    public const STATUSES = [
        'awaiting_payment' => 'Chờ thanh toán',
        'pending'   => 'Chờ xác nhận',
        'confirmed' => 'Đã xác nhận',
        'packed'    => 'Đã đóng gói',
        'shipping'  => 'Đang vận chuyển',
        'completed' => 'Đã giao thành công',
        'returning' => 'Đang hoàn hàng',
        'returned'  => 'Đã hoàn hàng',
        'cancelled' => 'Đã hủy',
    ];

    // Màu badge Bootstrap tương ứng từng trạng thái
    public const STATUS_COLORS = [
        'awaiting_payment' => 'bg-warning text-dark',
        'pending'   => 'bg-secondary',
        'confirmed' => 'bg-info text-dark',
        'packed'    => 'bg-primary',
        'shipping'  => 'bg-warning text-dark',
        'completed' => 'bg-success',
        'returning' => 'bg-danger-subtle text-danger',
        'returned'  => 'bg-dark',
        'cancelled' => 'bg-danger',
    ];

    /**
     * Trạng thái vận chuyển (shipping_status). Dùng chung bộ mã với GHN để sau này
     * nhận webhook GHN là khớp ngay, không phải đổi tên.
     */
    public const SHIPPING_LABELS = [
        'not_shipped' => 'Chưa bàn giao vận chuyển',
        'pending' => 'Chờ tạo vận đơn',
        'processing' => 'Đang tạo vận đơn',
        'ready_to_pick' => 'Chờ lấy hàng',
        'picking' => 'Đang lấy hàng',
        'picked' => 'Đã lấy hàng',
        'storing' => 'Đang lưu kho',
        'transporting' => 'Đang trung chuyển',
        'sorting' => 'Đang phân loại',
        'delivering' => 'Đang giao hàng',
        'delivery_fail' => 'Giao thất bại',
        'delivered' => 'Giao hàng thành công',
        'return' => 'Chờ hoàn hàng',
        'returning' => 'Đang hoàn hàng',
        'return_transporting' => 'Đang chuyển hoàn',
        'return_sorting' => 'Đang phân loại hoàn',
        'returned' => 'Đã hoàn hàng',
        'cancelled' => 'Đã hủy',
    ];

    public const PAYMENT_LABELS = [
        'pending' => 'Chờ thanh toán', 'initiated' => 'Đang chờ MoMo', 'paid' => 'Đã thanh toán',
        'failed' => 'Thanh toán thất bại', 'cancelled' => 'Đã hủy',
        'cod_collected' => 'Đã thu hộ - chờ đối soát',
        'refund_pending' => 'Chờ hoàn tiền', 'refunded' => 'Đã hoàn tiền',
    ];

    /**
     * Đơn ở các trạng thái này KHÔNG được tính là đã bán (doanh thu, hàng bán chạy,
     * số lượng đã bán): đã hủy, đã hoàn về shop, hoặc chưa thanh toán nên có thể tự hủy.
     */
    public const EXCLUDED_FROM_SALES = ['cancelled', 'returned', 'awaiting_payment'];

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
        'payment_expires_at',
        'carrier',
        'waybill_created_at',
        'picked_at',
        'delivered_at',
        'returned_at',
        'delivery_attempts',
        'last_delivery_note',
    ];

    protected $casts = [
        'cancelled_at' => 'datetime',
        'restocked_at' => 'datetime',
        'payment_expires_at' => 'datetime',
        'waybill_created_at' => 'datetime',
        'picked_at' => 'datetime',
        'delivered_at' => 'datetime',
        'returned_at' => 'datetime',
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

    /**
     * Khách đã trả tiền chưa (theo góc nhìn của KHÁCH): đã thanh toán, hoặc đã đưa
     * tiền mặt cho shipper (cod_collected). Khác isPaid(): isPaid chỉ đúng khi tiền
     * đã thật sự nằm ở shop (MoMo/ví đã nhận, COD đã đối soát xong).
     */
    /** Nhãn phương thức thanh toán để hiển thị */
    public function getPaymentMethodLabelAttribute(): string
    {
        return ['cod' => 'COD khi nhận hàng', 'momo' => 'MoMo', 'wallet' => 'Ví ToyLand'][$this->payment_method] ?? (string) $this->payment_method;
    }

    public function customerHasPaid(): bool
    {
        return in_array($this->payment_status, ['paid', 'cod_collected'], true);
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

    public function getShippingLabelAttribute(): string
    {
        return self::SHIPPING_LABELS[$this->shipping_status] ?? (string) $this->shipping_status;
    }

    /** Mã vận đơn (cột giữ tên cũ ghn_order_code cho khỏi vỡ dữ liệu, nhưng dùng cho mọi đơn vị vận chuyển) */
    public function getTrackingCodeAttribute(): ?string
    {
        return $this->ghn_order_code;
    }

    /** Đơn MoMo còn trong thời gian giữ hàng chờ thanh toán */
    public function isAwaitingPayment(): bool
    {
        return $this->status === 'awaiting_payment'
            && (! $this->payment_expires_at || $this->payment_expires_at->isFuture());
    }

    /** Số tiền bên vận chuyển phải thu hộ khi giao (COD chưa thanh toán) */
    public function getCodAmountAttribute(): int
    {
        return $this->payment_method === 'cod' && ! $this->customerHasPaid() ? (int) $this->total_price : 0;
    }

    /** Chỉ đếm những đơn thật sự bán được (xem EXCLUDED_FROM_SALES) */
    public function scopeCountedAsSale($query)
    {
        return $query->whereNotIn($query->qualifyColumn('status'), self::EXCLUDED_FROM_SALES);
    }

    /**
     * Khách chỉ được tự hủy khi bên vận chuyển chưa lấy hàng và đơn chưa thanh toán
     * online (đã trả tiền thì phải liên hệ shop để được hoàn tiền).
     */
    public function canBeCancelledByCustomer(): bool
    {
        // Đã thanh toán (MoMo/ví) vẫn tự hủy được khi shop chưa đóng gói: tiền được chuyển
        // thành yêu cầu hoàn tiền về ví (xem RefundService).
        return in_array($this->status, ['awaiting_payment', 'pending', 'confirmed'], true);
    }

    /**
     * Shop được hủy tới trước khi bên vận chuyển lấy hàng. Đã lấy hàng rồi thì
     * không "hủy" được nữa - chỉ có thể giao hoặc chuyển hoàn.
     */
    public function canBeCancelledByAdmin(): bool
    {
        return in_array($this->status, ['awaiting_payment', 'pending', 'confirmed', 'packed'], true);
    }
}
