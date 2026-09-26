<?php

namespace App\Models;

use App\Services\MomoService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// Nhật ký 1 lần giao dịch thanh toán của đơn hàng (MoMo, COD...).
// Mỗi lần khách bấm thanh toán/thanh toán lại sẽ sinh ra 1 bản ghi mới.
class PaymentTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'gateway',
        'payment_option',
        'gateway_order_id',
        'transaction_id',
        'amount',
        'status',
        'result_code',
        'message',
        'request_payload',
        'response_payload',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'request_payload' => 'array',
        'response_payload' => 'array',
        'paid_at' => 'datetime',
    ];

    // 1 giao dịch thuộc về 1 đơn hàng (N-1)
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    // Tên phương thức thanh toán MoMo đã dùng, dạng dễ đọc cho khách
    // (null nếu là giao dịch COD hoặc giao dịch cũ chưa lưu phương thức).
    public function getPaymentOptionLabelAttribute(): ?string
    {
        return MomoService::paymentOptionLabel($this->payment_option);
    }
}
