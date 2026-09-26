<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug', 'title', 'category', 'image', 'excerpt', 'content',
        'author_id', 'views', 'is_published', 'published_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    // Tóm tắt hiển thị ở trang danh sách: ưu tiên excerpt, không có thì cắt từ nội dung
    public function getSummaryAttribute(): string
    {
        return $this->excerpt ?: Str::limit(strip_tags($this->content), 160);
    }

    /**
     * Sinh slug duy nhất từ tiêu đề (bỏ qua chính bài đang sửa).
     * Str::slug xử lý được tiếng Việt có dấu.
     */
    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'bai-viet';
        $slug = $base;
        $i = 2;

        while (static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    /**
     * Đường dẫn ảnh bìa. Hỗ trợ cả ảnh mới (public/uploads/...) lẫn ảnh cũ
     * lưu qua disk 'public' (cần storage:link).
     */
    public function getImageUrlAttribute(): ?string
    {
        $path = trim((string) $this->image);

        if ($path === '') {
            return null;
        }

        if (\Illuminate\Support\Str::startsWith($path, ['http://', 'https://', '/'])) {
            return $path;
        }

        return \Illuminate\Support\Str::startsWith($path, 'uploads/')
            ? asset($path)
            : asset('storage/' . $path);
    }
}
