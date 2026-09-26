<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Hệ thống Ticket hỗ trợ khách hàng (phía quản trị).
 */
class TicketController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(array_keys(Ticket::STATUSES))],
            'category' => ['nullable', Rule::in(array_keys(Ticket::CATEGORIES))],
            'priority' => ['nullable', Rule::in(array_keys(Ticket::PRIORITIES))],
        ]);

        $query = Ticket::with('user')->withCount('replies');

        if ($request->filled('search')) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', '%'.$search.'%')
                  ->orWhere('subject', 'like', '%'.$search.'%')
                  ->orWhereHas('user', fn ($u) => $u->where('name', 'like', '%'.$search.'%'));
            });
        }

        foreach (['status', 'category', 'priority'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $filters[$field]);
            }
        }

        // Ưu tiên khẩn cấp lên đầu, sau đó tới ticket mới nhất
        $tickets = $query->orderByRaw("CASE priority WHEN 'high' THEN 0 WHEN 'normal' THEN 1 ELSE 2 END")
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'open' => Ticket::where('status', 'open')->count(),
            'processing' => Ticket::where('status', 'processing')->count(),
            'closed' => Ticket::where('status', 'closed')->count(),
            'total' => Ticket::count(),
        ];

        return view('admin.tickets.index', compact('tickets', 'filters', 'stats'));
    }

    public function show(Ticket $ticket)
    {
        $ticket->load(['replies.user', 'user', 'order']);

        return view('admin.tickets.show', compact('ticket'));
    }

    public function reply(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'content' => ['required', 'string', 'max:2000'],
        ], [], ['content' => 'Nội dung phản hồi']);

        TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'is_staff' => true,
            'content' => $data['content'],
        ]);

        // Nhân viên trả lời -> chuyển sang "đã phản hồi", trừ khi ticket đã đóng
        if (! $ticket->isClosed()) {
            $ticket->update(['status' => 'answered']);
        }

        return back()->with('success', 'Đã gửi phản hồi tới khách hàng.');
    }

    public function updateStatus(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Ticket::STATUSES))],
        ]);

        $ticket->update([
            'status' => $data['status'],
            'closed_at' => $data['status'] === 'closed' ? now() : null,
        ]);

        return back()->with('success', 'Đã chuyển yêu cầu sang trạng thái "' . Ticket::STATUSES[$data['status']] . '".');
    }
}
