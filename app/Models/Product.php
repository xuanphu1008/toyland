<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    // Nhãn hiển thị cho cột gender (dùng chung cho form admin và trang khách)
    public const GENDERS = [
        'boy' => 'Bé trai',
        'girl' => 'Bé gái',
        'unisex' => 'Bé trai & Bé gái',
    ];

    // Các nhóm kỹ năng gợi ý sẵn cho admin tick chọn khi thêm sản phẩm
    public const SKILL_OPTIONS = [
        'Tư duy logic',
        'Sáng tạo',
        'Vận động tinh',
        'Vận động thô',
        'Ngôn ngữ',
        'Toán học sớm',
        'Kỹ năng xã hội',
        'Tập trung & kiên nhẫn',
    ];

    /**
     * Các khoảng tuổi chuẩn để lọc. Key là mã gửi lên URL, value gồm nhãn
     * hiển thị và khoảng tháng tương ứng ([min, max]; max = null nghĩa là trở lên).
     */
    public const AGE_BUCKETS = [
        '0-12m' => ['label' => 'Sơ sinh - 12 tháng', 'min' => 0, 'max' => 12],
        '1-3y' => ['label' => '1 - 3 tuổi', 'min' => 12, 'max' => 36],
        '3-6y' => ['label' => '3 - 6 tuổi', 'min' => 36, 'max' => 72],
        '6-9y' => ['label' => '6 - 9 tuổi', 'min' => 72, 'max' => 108],
        '9-12y' => ['label' => '9 - 12 tuổi', 'min' => 108, 'max' => 144],
        '12y+' => ['label' => 'Trên 12 tuổi', 'min' => 144, 'max' => null],
    ];

    protected $fillable = [
        'category_id',
        'name',
        'sku',
        'brand',
        'character_name',
        'age_range',
        'age_min_months',
        'age_max_months',
        'material',
        'origin',
        'gender',
        'skills',
        'piece_count',
        'battery',
        'warranty_months',
        'dimensions',
        'weight_grams',
        'safety_note',
        'is_featured',
        'description',
        'image',
        'price',
        'sale_price',
        'sale_starts_at',
        'sale_ends_at',
        'quantity',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'price' => 'integer',
        'sale_price' => 'integer',
        'sale_starts_at' => 'datetime',
        'sale_ends_at' => 'datetime',
        'quantity' => 'integer',
    ];

    // 1 Sản phẩm thuộc về 1 Danh mục (Quan hệ 1-1)
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Thư viện ảnh phụ, xếp theo thứ tự admin đặt
    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    // 1 Sản phẩm có thể xuất hiện trong nhiều dòng chi tiết đơn hàng (Quan hệ 1-N)
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    // 1 Sản phẩm có nhiều đánh giá của khách hàng (Quan hệ 1-N)
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    // Chỉ lấy các đánh giá đang hiển thị công khai
    public function visibleReviews()
    {
        return $this->hasMany(Review::class)->where('is_visible', true);
    }

    public function getGenderLabelAttribute(): string
    {
        return self::GENDERS[$this->gender] ?? 'Mọi lứa tuổi';
    }

    public function getInStockAttribute(): bool
    {
        return $this->quantity > 0;
    }

    /**
     * Đường dẫn ảnh dùng chung cho mọi view.
     *
     * Ảnh mới được lưu thẳng vào public/uploads/... nên chỉ cần asset().
     * Ảnh cũ (trước đây lưu qua disk 'public') có dạng "products/abc.jpg" và
     * cần tiền tố storage/ - vốn phụ thuộc lệnh `php artisan storage:link`.
     * Accessor này xử lý cả hai kiểu nên ảnh cũ không bị mất.
     */
    public function getImageUrlAttribute(): ?string
    {
        return self::imageUrl($this->image);
    }

    /**
     * Bản static để dùng cho dữ liệu KHÔNG phải model - ví dụ giỏ hàng lưu
     * trong session chỉ giữ chuỗi đường dẫn ảnh.
     */
    public static function imageUrl(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '/'])) {
            return $path;
        }

        return Str::startsWith($path, 'uploads/')
            ? asset($path)
            : asset('storage/' . $path);
    }

    // Chuỗi kỹ năng "Tư duy logic, Sáng tạo" -> mảng để vẽ badge
    public function getSkillListAttribute(): array
    {
        return collect(explode(',', (string) $this->skills))
            ->map(fn ($item) => trim($item))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Nhãn độ tuổi hiển thị. Ưu tiên chuỗi admin nhập, nếu trống thì
     * tự dựng lại từ age_min_months / age_max_months.
     */
    public function getAgeLabelAttribute(): ?string
    {
        if (filled($this->age_range)) {
            return $this->age_range;
        }

        $min = $this->age_min_months;
        $max = $this->age_max_months;

        if ($min === null && $max === null) {
            return null;
        }

        $format = function (?int $months): string {
            if ($months === null) {
                return '';
            }

            return $months < 24 ? $months . ' tháng' : intdiv($months, 12) . ' tuổi';
        };

        if ($max === null) {
            return $format($min) . ' trở lên';
        }

        return $format($min) . ' - ' . $format($max);
    }

    /**
     * Điểm trung bình đã làm tròn 1 chữ số.
     * Ưu tiên dùng giá trị withAvg() nếu query đã nạp sẵn (tránh N+1).
     */
    public function getRatingAttribute(): float
    {
        $avg = $this->attributes['visible_reviews_avg_rating'] ?? null;

        if ($avg === null && $this->relationLoaded('visibleReviews')) {
            $avg = $this->getRelation('visibleReviews')->avg('rating');
        }

        if ($avg === null && $this->exists) {
            $avg = $this->visibleReviews()->avg('rating');
        }

        return round((float) $avg, 1);
    }

    public function getStarsAttribute(): string
    {
        $rounded = (int) round($this->rating);

        return str_repeat('★', $rounded) . str_repeat('☆', 5 - $rounded);
    }

    // ===================================================================
    // FLASH SALE
    // ===================================================================

    /**
     * Sản phẩm có ĐANG trong khung giờ khuyến mãi không.
     *
     * Ba điều kiện phải đúng cùng lúc:
     *   - có khai sale_price, và nó thật sự rẻ hơn giá gốc
     *   - đã tới giờ bắt đầu (bỏ trống = bắt đầu ngay)
     *   - chưa qua giờ kết thúc (bỏ trống = không có hạn)
     *
     * Điều kiện "rẻ hơn giá gốc" là lớp chặn cuối: admin lỡ gõ sale_price cao
     * hơn price thì hệ thống lặng lẽ bán giá thường, chứ không bán đắt hơn cho
     * khách đang tưởng mình được giảm giá.
     */
    public function getIsOnSaleAttribute(): bool
    {
        if (! $this->sale_price || $this->sale_price >= $this->price) {
            return false;
        }

        $now = now();

        if ($this->sale_starts_at && $now->lt($this->sale_starts_at)) {
            return false;
        }

        if ($this->sale_ends_at && $now->gte($this->sale_ends_at)) {
            return false;
        }

        return true;
    }

    /**
     * GIÁ THẬT SỰ PHẢI TRẢ.
     *
     * Đây là con số DUY NHẤT được dùng cho giỏ hàng, thanh toán và dòng chi
     * tiết đơn hàng. Mọi nơi tính tiền đều phải gọi thuộc tính này chứ không
     * đọc thẳng ->price, nếu không sẽ có chỗ tính giá sale chỗ tính giá gốc.
     */
    public function getEffectivePriceAttribute(): int
    {
        return $this->is_on_sale ? (int) $this->sale_price : (int) $this->price;
    }

    /** Giảm bao nhiêu phần trăm, làm tròn để hiện lên nhãn "-30%" */
    public function getDiscountPercentAttribute(): int
    {
        if (! $this->is_on_sale || ! $this->price) {
            return 0;
        }

        return (int) round(($this->price - $this->sale_price) / $this->price * 100);
    }

    /** Số tiền khách tiết kiệm được */
    public function getSaleSavingAttribute(): int
    {
        return $this->is_on_sale ? (int) $this->price - (int) $this->sale_price : 0;
    }

    /**
     * Còn bao nhiêu GIÂY nữa thì hết khuyến mãi; null nếu không có hạn.
     *
     * Trả về giây để JavaScript tự đếm ngược trong trình duyệt. Cố ý KHÔNG gửi
     * mốc thời gian tuyệt đối rồi để JS so với đồng hồ máy khách: máy khách đặt
     * sai giờ là đồng hồ đếm ngược sai theo, có khi âm.
     */
    public function getSaleSecondsLeftAttribute(): ?int
    {
        if (! $this->is_on_sale || ! $this->sale_ends_at) {
            return null;
        }

        return max(0, now()->diffInSeconds($this->sale_ends_at, false));
    }

    /**
     * Toàn bộ ảnh để hiện trong thư viện: ảnh đại diện đứng đầu, rồi tới ảnh phụ.
     * Trả về mảng URL đã sẵn sàng nhét vào thẻ <img>.
     */
    public function getGalleryAttribute(): array
    {
        $urls = [];

        if ($this->image) {
            $urls[] = self::imageUrl($this->image);
        }

        foreach ($this->images as $extra) {
            $urls[] = $extra->url;
        }

        // Lọc trùng phòng khi admin lỡ tải cùng một file hai lần
        return array_values(array_unique(array_filter($urls)));
    }

    /**
     * Các sản phẩm ĐANG chạy khuyến mãi, còn hàng.
     *
     * Lọc thô ở SQL (có sale_price, rẻ hơn giá gốc, đúng khung giờ) rồi lọc
     * tinh lại ở PHP bằng accessor is_on_sale - để chỉ có MỘT định nghĩa
     * "đang sale" duy nhất, tránh cảnh SQL nói đang sale mà PHP nói không.
     */
    public function scopeOnFlashSale($query)
    {
        $now = now();

        return $query->whereNotNull('sale_price')
            ->whereColumn('sale_price', '<', 'price')
            ->where('quantity', '>', 0)
            ->where(function ($q) use ($now) {
                $q->whereNull('sale_starts_at')->orWhere('sale_starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('sale_ends_at')->orWhere('sale_ends_at', '>', $now);
            });
    }

    /**
     * Scope tìm kiếm & lọc cho trang khách hàng.
     * Mọi điều kiện đều tùy chọn, bỏ trống thì không áp dụng.
     */
    public function scopeFilter($query, array $filters)
    {
        // Tìm theo từ khóa: tên, thương hiệu, chất liệu, mô tả
        $query->when($filters['q'] ?? null, function ($q, $keyword) {
            $keyword = trim($keyword);
            $q->where(function ($sub) use ($keyword) {
                $sub->where('name', 'like', '%' . $keyword . '%')
                    ->orWhere('brand', 'like', '%' . $keyword . '%')
                    ->orWhere('character_name', 'like', '%' . $keyword . '%')
                    ->orWhere('sku', 'like', '%' . $keyword . '%')
                    ->orWhere('material', 'like', '%' . $keyword . '%')
                    ->orWhere('origin', 'like', '%' . $keyword . '%')
                    ->orWhere('description', 'like', '%' . $keyword . '%');
            });
        });

        // Chọn danh mục CHA thì lấy luôn sản phẩm của các danh mục con
        $query->when($filters['category'] ?? null, function ($q, $id) {
            $category = Category::find($id);

            return $category
                ? $q->whereIn('category_id', $category->descendantIds())
                : $q->where('category_id', $id);
        });

        $query->when($filters['brand'] ?? null, fn ($q, $brand) => $q->where('brand', $brand));
        $query->when($filters['character'] ?? null, fn ($q, $name) => $q->where('character_name', $name));
        $query->when($filters['gender'] ?? null, fn ($q, $gender) => $q->where('gender', $gender));

        // Lọc theo khoảng tuổi: sản phẩm được tính là "hợp" khi khoảng tuổi của nó
        // GIAO với khoảng tuổi khách chọn (min sản phẩm <= max chọn && max sản phẩm >= min chọn).
        $query->when($filters['age'] ?? null, function ($q, $bucket) {
            $range = self::AGE_BUCKETS[$bucket] ?? null;

            if (! $range) {
                return $q;
            }

            return $q->where(function ($sub) use ($range) {
                if ($range['max'] !== null) {
                    $sub->where('age_min_months', '<=', $range['max']);
                }

                $sub->where(function ($inner) use ($range) {
                    $inner->whereNull('age_max_months')
                        ->orWhere('age_max_months', '>=', $range['min']);
                });
            });
        });

        $query->when($filters['price_min'] ?? null, fn ($q, $min) => $q->where('price', '>=', (int) $min));
        $query->when($filters['price_max'] ?? null, fn ($q, $max) => $q->where('price', '<=', (int) $max));

        if (($filters['in_stock'] ?? null) === '1') {
            $query->where('quantity', '>', 0);
        }

        return $query;
    }

    /**
     * Sắp xếp danh sách sản phẩm. 'best_selling' dựa trên tổng số lượng
     * đã bán ở các đơn không bị hủy.
     */
    public function scopeSorted($query, ?string $sort)
    {
        switch ($sort) {
            case 'price_asc':
                return $query->orderBy('price', 'asc');
            case 'price_desc':
                return $query->orderBy('price', 'desc');
            case 'name_asc':
                return $query->orderBy('name', 'asc');
            case 'rating':
                return $query->orderByDesc('visible_reviews_avg_rating')->orderBy('id', 'desc');
            case 'best_selling':
                return $query->orderByDesc('sold_quantity')->orderBy('id', 'desc');
            default:
                return $query->orderBy('id', 'desc');
        }
    }

    /**
     * Thêm cột tính toán `sold_quantity` = tổng số lượng đã bán (đơn không bị hủy).
     * Dùng withSum + ràng buộc quan hệ nên chạy đúng trên cả MySQL lẫn SQL Server.
     */
    public function scopeWithSoldQuantity($query)
    {
        return $query->withSum([
            'orderItems as sold_quantity' => function ($q) {
                $q->whereHas('order', function ($order) {
                    $order->whereNotIn('status', Order::EXCLUDED_FROM_SALES);
                });
            },
        ], 'quantity');
    }

    /**
     * Top sản phẩm bán chạy - dùng cho trang chủ và báo cáo.
     * Lọc bỏ sản phẩm chưa bán được cái nào ở phía PHP thay vì dùng HAVING,
     * vì `sold_quantity` là subquery trong SELECT nên SQL Server không cho HAVING theo alias.
     */
    public static function bestSellers(int $limit = 8)
    {
        return static::query()
            ->with('category')
            ->withSoldQuantity()
            ->withAvg('visibleReviews as visible_reviews_avg_rating', 'rating')
            ->orderByDesc('sold_quantity')
            ->limit($limit)
            ->get()
            ->filter(fn ($product) => (int) $product->sold_quantity > 0)
            ->values();
    }
}
