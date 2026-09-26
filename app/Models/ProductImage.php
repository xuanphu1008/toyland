<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Một ảnh phụ của sản phẩm.
 *
 * Ảnh đại diện vẫn nằm ở cột products.image - xem chú thích trong migration
 * create_product_images_table để biết vì sao tách làm hai chỗ.
 */
class ProductImage extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'path', 'sort_order'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Đường dẫn đầy đủ để nhét vào thẻ <img>.
     * Dùng lại Product::imageUrl() để ảnh phụ và ảnh đại diện luôn được xử lý
     * giống hệt nhau - đổi cách lưu ảnh một lần là cả hai cùng đúng.
     */
    public function getUrlAttribute(): ?string
    {
        return Product::imageUrl($this->path);
    }
}
