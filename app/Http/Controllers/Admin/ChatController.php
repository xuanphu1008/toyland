<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function getUsers()
    {
        $adminId = Auth::id();
        $userIds = Message::where('receiver_id', $adminId)
            ->orWhere('sender_id', $adminId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($msg) use ($adminId) {
                return $msg->sender_id == $adminId ? $msg->receiver_id : $msg->sender_id;
            })
            ->unique()
            ->toArray();

        return User::whereIn('id', $userIds)->where('id', '!=', $adminId)->select('id', 'name')->get();
    }

    public function getMessages($userId)
    {
        $adminId = Auth::id();

        return Message::with('sender')
            ->where(function ($q) use ($userId, $adminId) {
                $q->where('sender_id', $userId)->where('receiver_id', $adminId);
            })
            ->orWhere(function ($q) use ($userId, $adminId) {
                $q->where('sender_id', $adminId)->where('receiver_id', $userId);
            })
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function send(Request $request)
    {
        $request->validate(['user_id' => 'required|exists:users,id', 'message' => 'required']);

        $message = Message::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $request->user_id,
            'content' => $request->message,
            'is_read' => true,
        ]);

        return response()->json($message);
    }
}
