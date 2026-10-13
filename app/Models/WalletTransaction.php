<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    public const TYPES = [
        'refund_in' => 'Hoàn tiền vào ví',
        'order_payment' => 'Thanh toán đơn hàng',
        'order_cancel_return' => 'Hoàn tiền đơn đã hủy',
        'adjust' => 'Điều chỉnh bởi shop',
    ];

    protected $fillable = ['user_id', 'type', 'amount', 'balance_after', 'order_id', 'refund_request_id', 'note', 'created_by'];

    protected $casts = ['amount' => 'integer', 'balance_after' => 'integer'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
