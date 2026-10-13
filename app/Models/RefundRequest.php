<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefundRequest extends Model
{
    public const TYPES = [
        'order_cancelled' => 'Đơn đã thanh toán bị hủy',
        'payment_not_received' => 'Đã bị trừ tiền nhưng đơn chưa ghi nhận',
        'duplicate_payment' => 'Thanh toán trùng',
        'return_goods' => 'Trả hàng sau khi nhận',
    ];

    public const STATUSES = [
        'requested' => 'Chờ shop xử lý',
        'approved' => 'Shop đã duyệt - chờ khách gửi hàng về',
        'in_transit' => 'Hàng đang gửi về shop',
        'received' => 'Shop đã nhận hàng - đang kiểm tra',
        'refunded' => 'Đã hoàn tiền vào ví',
        'rejected' => 'Bị từ chối',
        'cancelled' => 'Đã hủy',
    ];

    public const STATUS_COLORS = [
        'requested' => 'bg-warning text-dark', 'approved' => 'bg-info text-dark', 'in_transit' => 'bg-primary',
        'received' => 'bg-primary', 'refunded' => 'bg-success', 'rejected' => 'bg-danger', 'cancelled' => 'bg-secondary',
    ];

    /** Trạng thái còn đang xử lý (chưa ngã ngũ) */
    public const OPEN = ['requested', 'approved', 'in_transit', 'received'];

    /**
     * Lý do trả hàng: [nhãn, lỗi do shop?, mặc định còn bán được?].
     * Lỗi do shop -> shop chịu phí ship, hoàn 100%. Khách đổi ý -> khách chịu
     * phí ship trả hàng và không được hoàn phí ship chiều đi.
     */
    public const RETURN_REASONS = [
        'defective' => ['Sản phẩm bị lỗi / hỏng', true, false],
        'wrong_item' => ['Shop giao sai sản phẩm', true, true],
        'missing_item' => ['Thiếu hàng / thiếu phụ kiện', true, true],
        'not_as_described' => ['Không giống mô tả', true, true],
        'change_of_mind' => ['Đổi ý / không còn nhu cầu', false, true],
    ];

    protected $fillable = [
        'order_id', 'user_id', 'payment_transaction_id', 'type', 'source', 'reason_code', 'reason', 'momo_trans_id',
        'amount', 'destination', 'status', 'restock', 'return_carrier', 'return_tracking_code', 'ship_back_deadline',
        'approved_at', 'shipped_at', 'received_at', 'handled_by', 'handled_at', 'admin_note', 'wallet_transaction_id',
    ];

    protected $casts = [
        'amount' => 'integer', 'restock' => 'boolean',
        'ship_back_deadline' => 'datetime', 'approved_at' => 'datetime', 'shipped_at' => 'datetime',
        'received_at' => 'datetime', 'handled_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function paymentTransaction()
    {
        return $this->belongsTo(PaymentTransaction::class);
    }

    public function isReturn(): bool
    {
        return $this->type === 'return_goods';
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN, true);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'bg-secondary';
    }

    public function getReasonLabelAttribute(): ?string
    {
        return $this->reason_code ? (self::RETURN_REASONS[$this->reason_code][0] ?? $this->reason_code) : null;
    }
}
