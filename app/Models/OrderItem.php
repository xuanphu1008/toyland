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
        'variant_id',
        'variant_name',
        'quantity',
        'price',
    ];

    /** Tên hiển thị kèm phân loại đã chụp lại lúc mua: "Gấu Bông Teddy (Lông xoăn)" */
    public function getDisplayNameAttribute(): ?string
    {
        $name = $this->product->name ?? null;

        return $name && $this->variant_name ? $name . ' (' . $this->variant_name . ')' : $name;
    }

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
