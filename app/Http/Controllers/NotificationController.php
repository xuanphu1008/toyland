<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Trung tâm thông báo của khách hàng.
 *
 * BẢO MẬT: mọi truy vấn đều bắt đầu bằng scope forUser(Auth::id()). Không có
 * đường nào đọc hay đánh dấu đã đọc thông báo của người khác, kể cả khi đoán
 * đúng id trên URL - findOrFail sẽ trả 404.
 */
class NotificationController extends Controller
{
    /** Trang xem toàn bộ thông báo */
    public function index(Request $request)
    {
        $filter = $request->input('filter') === 'unread' ? 'unread' : 'all';

        $notifications = UserNotification::forUser(Auth::id())
            ->when($filter === 'unread', fn ($q) => $q->unread())
            ->orderByDesc('id')
            ->paginate(20)
            ->appends($request->query());

        return view('notifications.index', [
            'notifications' => $notifications,
            'filter' => $filter,
            'unreadCount' => UserNotification::forUser(Auth::id())->unread()->count(),
        ]);
    }

    /**
     * Bấm vào một thông báo: đánh dấu đã đọc rồi chuyển tới trang liên quan.
     *
     * Gộp hai việc vào một route thay vì để JavaScript gọi API rồi mới chuyển
     * trang - ít code hơn, và vẫn chạy khi trình duyệt tắt JavaScript.
     */
    public function open(int $id)
    {
        $notification = UserNotification::forUser(Auth::id())->findOrFail($id);

        if ($notification->is_unread) {
            $notification->update(['read_at' => now()]);
        }

        return redirect($notification->url ?: route('notifications.index'));
    }

    public function markAllRead()
    {
        UserNotification::forUser(Auth::id())->unread()->update(['read_at' => now()]);

        return back()->with('success', 'Đã đánh dấu tất cả là đã đọc.');
    }

    public function destroy(int $id)
    {
        UserNotification::forUser(Auth::id())->findOrFail($id)->delete();

        return back()->with('success', 'Đã xóa thông báo.');
    }

    public function destroyAll()
    {
        UserNotification::forUser(Auth::id())->delete();

        return back()->with('success', 'Đã dọn sạch thông báo.');
    }

    /**
     * Số thông báo chưa đọc, dạng JSON.
     * Chuông trên thanh điều hướng gọi mỗi 60 giây để cập nhật con số mà không
     * phải tải lại trang. Trả về đúng một con số cho nhẹ.
     */
    public function unreadCount(): JsonResponse
    {
        return response()->json([
            'count' => UserNotification::forUser(Auth::id())->unread()->count(),
        ]);
    }
}
