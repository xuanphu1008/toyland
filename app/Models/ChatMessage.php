<?php

namespace App\Models;

use App\Services\Chatbot\IntentMatcher;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id', 'role', 'content', 'intent', 'confidence', 'meta',
    ];

    protected $casts = [
        'confidence' => 'float',
    ];

    public function conversation()
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }

    /** Chỉ lấy những câu bot KHÔNG hiểu - danh sách việc cần làm cho admin */
    public function scopeUnanswered($query)
    {
        return $query->where('role', 'bot')->where('intent', 'unknown');
    }

    public function getIntentLabelAttribute(): string
    {
        return IntentMatcher::label($this->intent ?? 'unknown');
    }

    /**
     * meta lưu dạng chuỗi JSON (dùng text thay vì cột json để SQL Server
     * bản cũ cũng chạy được). Accessor này giải mã an toàn.
     */
    public function getMetaDataAttribute(): array
    {
        if (blank($this->meta)) {
            return [];
        }

        $decoded = json_decode($this->meta, true);

        return is_array($decoded) ? $decoded : [];
    }
}
