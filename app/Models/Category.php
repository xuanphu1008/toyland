<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['parent_id', 'name', 'slug', 'icon', 'description', 'sort_order'];

    protected $casts = [
        'parent_id' => 'integer',
        'sort_order' => 'integer',
    ];

    // 1 Danh mục có nhiều Sản phẩm (Quan hệ 1-N)
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    // Danh mục cha (VD: "Đồ Chơi Phương Tiện")
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    // Các danh mục con (VD: "Xe điều khiển từ xa", "Xe mô hình"...)
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    // ===================== SCOPE =====================

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // ===================== TIỆN ÍCH =====================

    /**
     * Cây danh mục 2 cấp dùng cho menu, bộ lọc và trang chủ.
     * Gọi 1 query duy nhất rồi tự ghép cha - con để tránh N+1.
     */
    public static function tree()
    {
        $all = static::ordered()->get();

        return $all->whereNull('parent_id')->values()->each(function ($root) use ($all) {
            $root->setRelation('children', $all->where('parent_id', $root->id)->values());
        });
    }

    /**
     * ID của chính nó + toàn bộ con, dùng khi lọc sản phẩm theo danh mục cha
     * (chọn "Đồ Chơi Phương Tiện" phải ra cả sản phẩm nằm trong "Xe mô hình").
     */
    public function descendantIds(): array
    {
        return array_merge([$this->id], $this->children()->pluck('id')->all());
    }

    public function getIsRootAttribute(): bool
    {
        return $this->parent_id === null;
    }

    public function getFullNameAttribute(): string
    {
        return $this->parent ? $this->parent->name . ' / ' . $this->name : $this->name;
    }

    /**
     * Tự sinh slug từ tên nếu admin không nhập.
     * Str::slug() bỏ dấu tiếng Việt sẵn: "Đồ Chơi Gỗ" -> "do-choi-go".
     */
    protected static function booted()
    {
        static::saving(function (Category $category) {
            if (blank($category->slug) && filled($category->name)) {
                $category->slug = Str::slug($category->name);
            }
        });

        // Xóa danh mục cha thì gỡ liên kết của các con để chúng không bị "mồ côi"
        static::deleting(function (Category $category) {
            static::where('parent_id', $category->id)->update(['parent_id' => null]);
        });
    }
}
