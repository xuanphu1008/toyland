<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function send(Request $request)
    {
        $messageText = $request->input('message');
        if (empty(trim($messageText))) {
            return response()->json(['error' => 'Nội dung tin nhắn không được để trống'], 400);
        }

        $admin = User::where('role', 'admin')->first();
        $receiverId = $admin ? $admin->id : 1;

        try {
            $message = Message::create([
                'sender_id' => Auth::id(),
                'receiver_id' => $receiverId,
                'content' => $messageText,
                'is_read' => false,
            ]);
            return response()->json($message);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Không thể gửi tin nhắn: ' . $e->getMessage()], 500);
        }
    }

    public function getMessages()
    {
        $userId = Auth::id();
        $admin = User::where('role', 'admin')->first();
        $adminId = $admin ? $admin->id : 1;

        $messages = Message::query()
            ->where(function ($q) use ($userId, $adminId) {
                $q->where(function ($sub) use ($userId, $adminId) {
                    $sub->where('sender_id', $userId)->where('receiver_id', $adminId);
                })->orWhere(function ($sub) use ($userId, $adminId) {
                    $sub->where('sender_id', $adminId)->where('receiver_id', $userId);
                });
            })
            ->orderBy('created_at', 'asc')
            ->get();

        // Trả về dạng ĐÃ CHUẨN HÓA thay vì nguyên model:
        //  - giao diện chỉ cần biết "ai nói" và "nói gì", không cần toàn bộ cột
        //  - không lộ thừa dữ liệu (email, quan hệ...) ra ngoài trình duyệt
        return response()->json(
            $messages->map(fn (Message $m) => [
                'id' => $m->id,
                'message' => $m->content,
                'is_admin' => (int) $m->sender_id !== (int) $userId,
                'time' => optional($m->created_at)->format('H:i d/m'),
            ])->values()
        );
    }
}
