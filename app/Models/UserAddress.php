<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'address',
        // Mã + tên hành chính lấy từ GHN. Mã (id/code) dùng để tính phí ship,
        // tên dùng để hiển thị lại cho khách đọc.
        'province_id',
        'province_name',
        'district_id',
        'district_name',
        'ward_code',
        'ward_name',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'province_id' => 'integer',
        'district_id' => 'integer',
    ];

    // 1 Địa chỉ thuộc về 1 User (Quan hệ 1-1)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Đủ dữ liệu để gọi API tính phí GHN hay chưa.
     * Địa chỉ tạo từ trước khi có tính năng này sẽ trả về false và
     * giao diện sẽ nhắc khách bấm "Sửa" để bổ sung.
     */
    public function hasLocation(): bool
    {
        return ! empty($this->district_id) && ! empty($this->ward_code);
    }

    // "Số 1 Trần Phú, Phường Dịch Vọng, Cầu Giấy, Hà Nội"
    public function getFullAddressAttribute(): string
    {
        return collect([
            $this->address,
            $this->ward_name,
            $this->district_name,
            $this->province_name,
        ])->filter()->implode(', ');
    }

    // Phần hành chính riêng, để hiển thị ở dòng thứ hai cho gọn
    public function getLocationTextAttribute(): string
    {
        return collect([$this->ward_name, $this->district_name, $this->province_name])
            ->filter()
            ->implode(', ');
    }
}
