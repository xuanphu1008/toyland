<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'price',
    ];

    // 1 Dòng chi tiết thuộc về 1 Đơn hàng (Quan hệ 1-1)
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // 1 Dòng chi tiết tham chiếu tới 1 Sản phẩm (Quan hệ 1-1)
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
