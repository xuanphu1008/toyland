<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Một thông báo trong ứng dụng.
 *
 * Cách dùng ở nơi khác chỉ gọn một dòng:
 *
 *   UserNotification::send($userId, 'order_status',
 *       'Đơn #12 đang được giao',
 *       'Shop đã bàn giao cho đơn vị vận chuyển.',
 *       route('orders.show', 12));
 *
 * send() KHÔNG BAO GIỜ ném lỗi ra ngoài (xem chú thích ở hàm). Thông báo là
 * chức năng phụ - nó hỏng thì việc chính, ví dụ đặt hàng, vẫn phải chạy xong.
 */
class UserNotification extends Model
{
    use HasFactory;

    /** type => [biểu tượng, màu chữ Bootstrap] */
    public const TYPES = [
        'order' => ['🧾', 'text-primary'],
        'order_status' => ['🚚', 'text-info'],
        'birthday' => ['🎂', 'text-warning'],
        'coupon' => ['🎟️', 'text-success'],
        'review' => ['⭐', 'text-warning'],
        'system' => ['🔔', 'text-secondary'],
    ];

    /** Giữ tối đa bao nhiêu thông báo mỗi người - dọn bớt để bảng không phình mãi */
    public const KEEP_PER_USER = 50;

    protected $fillable = ['user_id', 'type', 'title', 'body', 'url', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ===================================================================

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeForUser($query, ?int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // ===================================================================

    /**
     * Tạo một thông báo.
     *
     * TÊN HÀM LÀ send() CHỨ KHÔNG PHẢI push():
     * Eloquent\Model đã có sẵn method push() (lưu model kèm mọi quan hệ đã nạp)
     * và nó KHÔNG static. Khai một method static trùng tên ở lớp con là lỗi
     * nghiêm trọng của PHP - "Cannot make non static method static" - sập ngay
     * khi nạp lớp, tức là sập cả website chứ không riêng chỗ gọi.
     *
     * NUỐT MỌI LỖI CÓ CHỦ Ý: hàm này được gọi xen giữa các việc quan trọng
     * (đặt hàng, đổi trạng thái đơn). Chưa chạy migrate, CSDL kén kiểu dữ liệu,
     * hay bất cứ trục trặc nào khác cũng không được phép làm hỏng việc chính.
     * Lỗi chỉ ghi vào log để còn biết mà sửa.
     */
    public static function send(
        ?int $userId,
        string $type,
        string $title,
        ?string $body = null,
        ?string $url = null
    ): ?self {
        if (! $userId) {
            return null;
        }

        try {
            $notification = self::create([
                'user_id' => $userId,
                'type' => array_key_exists($type, self::TYPES) ? $type : 'system',
                'title' => mb_substr($title, 0, 160),
                'body' => $body ? mb_substr($body, 0, 400) : null,
                'url' => $url,
            ]);

            self::trim($userId);

            return $notification;
        } catch (Throwable $e) {
            Log::warning('Không tạo được thông báo', [
                'user_id' => $userId,
                'type' => $type,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /** Gửi cùng một thông báo cho toàn bộ quản trị viên */
    public static function sendToAdmins(string $type, string $title, ?string $body = null, ?string $url = null): void
    {
        try {
            User::where('role', 'admin')->pluck('id')
                ->each(fn ($id) => self::send($id, $type, $title, $body, $url));
        } catch (Throwable $e) {
            Log::warning('Không gửi được thông báo cho admin', ['message' => $e->getMessage()]);
        }
    }

    /**
     * Xóa bớt thông báo cũ, chỉ giữ KEEP_PER_USER cái mới nhất.
     *
     * Không dùng `limit` trong câu DELETE vì SQL Server không cho; thay vào đó
     * lấy danh sách id cần giữ rồi xóa phần còn lại.
     */
    private static function trim(int $userId): void
    {
        $keepIds = self::forUser($userId)
            ->orderByDesc('id')
            ->limit(self::KEEP_PER_USER)
            ->pluck('id')
            ->all();

        // Mảng rỗng thì bỏ qua: `NOT IN ()` là cú pháp sai trên SQL Server
        if (! $keepIds) {
            return;
        }

        self::forUser($userId)->whereNotIn('id', $keepIds)->delete();
    }

    // ===================================================================

    public function getIconAttribute(): string
    {
        return self::TYPES[$this->type][0] ?? '🔔';
    }

    public function getColorClassAttribute(): string
    {
        return self::TYPES[$this->type][1] ?? 'text-secondary';
    }

    public function getIsUnreadAttribute(): bool
    {
        return $this->read_at === null;
    }
}
