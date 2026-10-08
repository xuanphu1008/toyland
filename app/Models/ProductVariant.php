<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = ['product_id', 'name', 'sku', 'price_adjust', 'quantity', 'image', 'sort_order'];

    protected $casts = ['price_adjust' => 'integer', 'quantity' => 'integer'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /** Giá gốc của biến thể (chưa tính flash sale) */
    public function getBasePriceAttribute(): int
    {
        return max(0, (int) $this->product->price + $this->price_adjust);
    }

    /** Giá khách phải trả: giá hiệu lực của sản phẩm (đã tính flash sale) + chênh lệch */
    public function getEffectivePriceAttribute(): int
    {
        return max(0, (int) $this->product->effective_price + $this->price_adjust);
    }
}
