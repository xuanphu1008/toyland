<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderStatusLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'changed_by',
        'from_status',
        'to_status',
        'note',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function getToStatusLabelAttribute(): string
    {
        return Order::STATUSES[$this->to_status] ?? $this->to_status;
    }

    public function getFromStatusLabelAttribute(): ?string
    {
        if (! $this->from_status) {
            return null;
        }

        return Order::STATUSES[$this->from_status] ?? $this->from_status;
    }
}
