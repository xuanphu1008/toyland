<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    public const CATEGORIES = [
        'order' => 'Đơn hàng',
        'payment' => 'Thanh toán',
        'shipping' => 'Vận chuyển',
        'product' => 'Sản phẩm',
        'other' => 'Khác',
    ];

    public const PRIORITIES = [
        'low' => 'Thấp',
        'normal' => 'Bình thường',
        'high' => 'Khẩn cấp',
    ];

    public const STATUSES = [
        'open' => 'Đang chờ xử lý',
        'processing' => 'Đang xử lý',
        'answered' => 'Đã phản hồi',
        'closed' => 'Đã đóng',
    ];

    public const STATUS_COLORS = [
        'open' => 'bg-warning text-dark',
        'processing' => 'bg-info text-dark',
        'answered' => 'bg-primary',
        'closed' => 'bg-secondary',
    ];

    protected $fillable = [
        'user_id', 'order_id', 'code', 'subject', 'category',
        'priority', 'status', 'content', 'closed_at',
    ];

    protected $casts = ['closed_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function replies()
    {
        return $this->hasMany(TicketReply::class)->orderBy('id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'bg-secondary';
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function getPriorityLabelAttribute(): string
    {
        return self::PRIORITIES[$this->priority] ?? $this->priority;
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    // Sinh mã ticket dạng TK-000012 dựa trên id lớn nhất hiện có
    public static function generateCode(): string
    {
        return 'TK-' . str_pad((string) (((int) static::max('id')) + 1), 6, '0', STR_PAD_LEFT);
    }
}
