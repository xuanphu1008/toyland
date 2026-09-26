<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'order_id',
        'rating',
        'comment',
        'is_visible',
        'reply',
        'replied_at',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'replied_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    // Chuỗi sao đặc để render nhanh trên giao diện, vd: 4 -> "★★★★☆"
    public function getStarsAttribute(): string
    {
        return str_repeat('★', (int) $this->rating) . str_repeat('☆', 5 - (int) $this->rating);
    }
}
