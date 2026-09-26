<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatConversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'session_id', 'message_count', 'handed_to_human', 'last_message_at',
    ];

    protected $casts = [
        'handed_to_human' => 'boolean',
        'last_message_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class, 'conversation_id')->orderBy('id');
    }

    /**
     * Xóa hội thoại thì xóa luôn tin nhắn bên trong.
     * Làm ở tầng ứng dụng vì migration cố ý không đặt khóa ngoại cascade
     * (SQL Server hay báo lỗi "multiple cascade paths").
     */
    protected static function booted()
    {
        static::deleting(function (ChatConversation $conversation) {
            ChatMessage::where('conversation_id', $conversation->id)->delete();
        });
    }

    public function getTitleAttribute(): string
    {
        $first = $this->messages()->where('role', 'user')->first();

        return $first ? \Illuminate\Support\Str::limit($first->content, 60) : 'Hội thoại trống';
    }
}
