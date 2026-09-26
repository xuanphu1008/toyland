<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Hệ thống Ticket hỗ trợ khách hàng (phía khách).
 */
class TicketController extends Controller
{
    public function index()
    {
        $tickets = Ticket::withCount('replies')
            ->where('user_id', Auth::id())
            ->latest('id')
            ->paginate(10);

        return view('tickets.index', compact('tickets'));
    }

    public function create()
    {
        // Cho khách chọn đơn hàng liên quan (nếu có) để nhân viên dễ tra cứu
        $orders = Auth::user()->orders()->latest('id')->limit(20)->get();

        return view('tickets.create', compact('orders'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(array_keys(Ticket::CATEGORIES))],
            'priority' => ['required', Rule::in(array_keys(Ticket::PRIORITIES))],
            'order_id' => ['nullable', 'integer'],
            'content' => ['required', 'string', 'max:2000'],
        ], [], [
            'subject' => 'Tiêu đề',
            'category' => 'Loại yêu cầu',
            'priority' => 'Mức độ',
            'content' => 'Nội dung',
        ]);

        // Chỉ chấp nhận order_id thuộc về chính khách đang đăng nhập
        if (! empty($data['order_id'])) {
            $owns = Auth::user()->orders()->whereKey($data['order_id'])->exists();
            if (! $owns) {
                $data['order_id'] = null;
            }
        }

        $ticket = DB::transaction(function () use ($data) {
            return Ticket::create([
                'user_id' => Auth::id(),
                'order_id' => $data['order_id'] ?? null,
                'code' => Ticket::generateCode(),
                'subject' => $data['subject'],
                'category' => $data['category'],
                'priority' => $data['priority'],
                'status' => 'open',
                'content' => $data['content'],
            ]);
        });

        return redirect()->route('tickets.show', $ticket->id)
            ->with('success', 'Đã gửi yêu cầu hỗ trợ ' . $ticket->code . '. ToyLand sẽ phản hồi sớm nhất có thể.');
    }

    public function show(Ticket $ticket)
    {
        abort_if((int) $ticket->user_id !== (int) Auth::id(), 403);

        $ticket->load(['replies.user', 'order']);

        return view('tickets.show', compact('ticket'));
    }

    public function reply(Request $request, Ticket $ticket)
    {
        abort_if((int) $ticket->user_id !== (int) Auth::id(), 403);

        if ($ticket->isClosed()) {
            return back()->with('error', 'Yêu cầu này đã đóng. Vui lòng tạo yêu cầu mới nếu cần hỗ trợ thêm.');
        }

        $data = $request->validate([
            'content' => ['required', 'string', 'max:2000'],
        ], [], ['content' => 'Nội dung']);

        TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'is_staff' => false,
            'content' => $data['content'],
        ]);

        // Khách trả lời -> đưa về trạng thái chờ shop xử lý
        $ticket->update(['status' => 'open']);

        return back()->with('success', 'Đã gửi phản hồi của bạn.');
    }

    public function close(Ticket $ticket)
    {
        abort_if((int) $ticket->user_id !== (int) Auth::id(), 403);

        $ticket->update(['status' => 'closed', 'closed_at' => now()]);

        return back()->with('success', 'Đã đóng yêu cầu hỗ trợ ' . $ticket->code . '.');
    }
}
