<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Hồ sơ một bé.
 *
 * Toàn bộ phần "thông minh" của tính năng nằm ở mấy accessor tính tuổi bên dưới.
 * Điểm mấu chốt: tuổi được tính bằng THÁNG chứ không phải năm, vì với bé nhỏ
 * thì 8 tháng và 20 tháng là hai thế giới đồ chơi hoàn toàn khác nhau, trong
 * khi cả hai đều "0 tuổi".
 */
class ChildProfile extends Model
{
    use HasFactory;

    /** Emoji đại diện cho bé - chọn thay vì tải ảnh lên */
    public const AVATARS = ['🧒', '👦', '👧', '👶', '🧑', '🐻', '🦄', '🚀', '🦕', '🐼', '⭐', '🎈'];

    public const GENDERS = [
        'boy' => 'Bé trai',
        'girl' => 'Bé gái',
    ];

    /** Bao nhiêu ngày trước sinh nhật thì coi là "sắp tới" */
    public const REMIND_DAYS = 14;

    protected $fillable = [
        'user_id', 'name', 'birthday', 'gender', 'avatar', 'interests', 'favorite_category_id', 'note',
    ];

    protected $casts = [
        'birthday' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function favoriteCategory()
    {
        return $this->belongsTo(Category::class, 'favorite_category_id');
    }

    // ===================================================================
    // TUỔI
    // ===================================================================

    /** Tuổi hiện tại tính bằng tháng - đơn vị mà bộ lọc sản phẩm dùng */
    public function getAgeMonthsAttribute(): int
    {
        if (! $this->birthday) {
            return 0;
        }

        return max(0, $this->birthday->diffInMonths(Carbon::today()));
    }

    /** "8 tháng" hoặc "5 tuổi 3 tháng" - cách người Việt thực sự nói về tuổi bé */
    public function getAgeLabelAttribute(): string
    {
        $months = $this->age_months;

        // Bé dưới 1 tháng mà ghi "0 tháng" thì đọc rất kỳ
        if ($months === 0) {
            $weeks = $this->birthday ? intdiv($this->birthday->diffInDays(Carbon::today()), 7) : 0;

            return $weeks > 0 ? $weeks . ' tuần' : 'Mới sinh';
        }

        if ($months < 24) {
            return $months . ' tháng';
        }

        $years = intdiv($months, 12);
        $rest = $months % 12;

        return $rest > 0 ? "{$years} tuổi {$rest} tháng" : "{$years} tuổi";
    }

    // ===================================================================
    // SINH NHẬT
    // ===================================================================

    /**
     * Lần sinh nhật kế tiếp.
     * Bé sinh 29/2 thì năm không nhuận Carbon tự đẩy sang 1/3 - chấp nhận được,
     * và vẫn đúng ý "ngày kỷ niệm gần nhất".
     */
    public function getNextBirthdayAttribute(): ?Carbon
    {
        if (! $this->birthday) {
            return null;
        }

        $today = Carbon::today();
        $next = $this->birthday->copy()->year($today->year);

        if ($next->lt($today)) {
            $next->addYear();
        }

        return $next;
    }

    /** Còn bao nhiêu ngày nữa tới sinh nhật (0 = đúng hôm nay) */
    public function getDaysUntilBirthdayAttribute(): int
    {
        $next = $this->next_birthday;

        return $next ? Carbon::today()->diffInDays($next) : 999;
    }

    /** Sinh nhật tới bé tròn mấy tuổi */
    public function getTurningAgeAttribute(): int
    {
        $next = $this->next_birthday;

        return $next && $this->birthday ? $this->birthday->diffInYears($next) : 0;
    }

    /**
     * Tuổi (tính bằng tháng) của bé VÀO NGÀY SINH NHẬT TỚI.
     *
     * Đây là con số dùng để gợi ý quà sinh nhật. Bé sắp tròn 6 tuổi thì phải
     * gợi ý đồ cho 6 tuổi, không phải cho đứa trẻ 5 tuổi của hôm nay - món quà
     * sẽ còn được chơi suốt năm sau đó.
     */
    public function getGiftAgeMonthsAttribute(): int
    {
        return $this->turning_age * 12;
    }

    /** Sinh nhật có nằm trong khoảng nhắc trước không */
    public function getIsBirthdaySoonAttribute(): bool
    {
        return $this->days_until_birthday <= self::REMIND_DAYS;
    }

    /** "Còn 3 ngày" / "Hôm nay!" / "Còn 2 tháng" */
    public function getCountdownLabelAttribute(): string
    {
        $days = $this->days_until_birthday;

        return match (true) {
            $days === 0 => 'Sinh nhật hôm nay! 🎉',
            $days === 1 => 'Còn 1 ngày nữa',
            $days <= 30 => "Còn {$days} ngày nữa",
            default => 'Còn ' . intdiv($days, 30) . ' tháng nữa',
        };
    }

    // ===================================================================
    // SỞ THÍCH
    // ===================================================================

    /** Chuỗi "Spider-Man, LEGO" -> mảng */
    public function getInterestListAttribute(): array
    {
        if (blank($this->interests)) {
            return [];
        }

        return collect(explode(',', $this->interests))
            ->map(fn ($item) => trim($item))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /** Mảng -> chuỗi, dùng khi lưu từ form */
    public static function packInterests(?array $items): ?string
    {
        if (empty($items)) {
            return null;
        }

        return collect($items)->map(fn ($i) => trim($i))->filter()->unique()->implode(', ') ?: null;
    }

    // ===================================================================

    /**
     * Mã khoảng tuổi trong Product::AGE_BUCKETS ứng với số tháng cho trước.
     * Dùng để dựng link lọc "Xem đồ chơi hợp tuổi bé" trỏ về trang chủ,
     * tận dụng lại đúng bộ lọc sẵn có thay vì viết một luồng tìm kiếm riêng.
     */
    public static function ageBucketFor(int $months): ?string
    {
        foreach (Product::AGE_BUCKETS as $key => $range) {
            $withinMax = $range['max'] === null || $months < $range['max'];

            if ($months >= $range['min'] && $withinMax) {
                return $key;
            }
        }

        return null;
    }

    public function getAgeBucketAttribute(): ?string
    {
        return self::ageBucketFor($this->age_months);
    }

    /** Tham số lọc để xem đồ chơi hợp với bé trên trang chủ */
    public function filterQuery(?int $ageMonths = null): array
    {
        return array_filter([
            'age' => self::ageBucketFor($ageMonths ?? $this->age_months),
            'gender' => $this->gender,
        ]);
    }
}
