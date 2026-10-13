<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderStatusLog;
use App\Models\RefundRequest;
use App\Services\RefundException;
use App\Services\RefundService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Hộp thư "Hoàn tiền & trả hàng" của shop */
class RefundController extends Controller
{
    public function __construct(private RefundService $refunds)
    {
    }

    public function index(Request $request)
    {
        $q = RefundRequest::with(['order', 'user'])->orderByRaw("FIELD(status,'requested','received','in_transit','approved','refunded','rejected','cancelled')")->orderByDesc('id');

        if ($request->filled('status')) {
            $request->status === 'open' ? $q->whereIn('status', RefundRequest::OPEN) : $q->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $q->where('type', $request->type);
        }
        if ($request->filled('order')) {
            $q->where('order_id', (int) $request->order);
        }

        return view('admin.refunds.index', [
            'requests' => $q->paginate(20)->withQueryString(),
            'openCount' => RefundRequest::whereIn('status', RefundRequest::OPEN)->count(),
        ]);
    }

    public function show(RefundRequest $refund)
    {
        $refund->load(['order.items.product', 'user', 'paymentTransaction']);
        $logs = OrderStatusLog::where('order_id', $refund->order_id)->orderByDesc('id')->limit(15)->get();

        return view('admin.refunds.show', ['r' => $refund, 'logs' => $logs]);
    }

    public function payout(Request $request, RefundRequest $refund)
    {
        $note = $request->validate(['note' => 'nullable|string|max:500'])['note'] ?? null;

        return $this->run($refund, fn () => $this->refunds->payout($refund, Auth::user(), $note), 'Đã hoàn tiền vào ví của khách.');
    }

    public function reject(Request $request, RefundRequest $refund)
    {
        $data = $request->validate(['note' => 'required|string|max:500'], ['note.required' => 'Từ chối thì phải ghi rõ lý do.']);

        return $this->run($refund, fn () => $this->refunds->reject($refund, Auth::user(), $data['note']), 'Đã từ chối yêu cầu và báo khách.');
    }

    public function confirmPayment(RefundRequest $refund)
    {
        return $this->run($refund, fn () => $this->refunds->resolveByConfirmingPayment($refund, Auth::user()), 'Đã ghi nhận thanh toán, đơn tiếp tục xử lý.');
    }

    public function approveReturn(Request $request, RefundRequest $refund)
    {
        $note = $request->validate(['note' => 'nullable|string|max:500'])['note'] ?? null;

        return $this->run($refund, fn () => $this->refunds->approveReturn($refund, Auth::user(), $note), 'Đã duyệt. Khách có hạn để gửi hàng về.');
    }

    public function receive(RefundRequest $refund)
    {
        return $this->run($refund, fn () => $this->refunds->markReceived($refund, Auth::user()), 'Đã ghi nhận nhận hàng trả. Hãy kiểm tra hàng rồi chốt kết quả.');
    }

    public function inspect(Request $request, RefundRequest $refund)
    {
        $data = $request->validate([
            'result' => 'required|in:accept,reject',
            'restock' => 'nullable|boolean',
            'note' => 'nullable|string|max:500',
        ]);
        $accept = $data['result'] === 'accept';

        return $this->run($refund,
            fn () => $this->refunds->finishInspection($refund, Auth::user(), $accept, (bool) ($data['restock'] ?? false), $data['note'] ?? null),
            $accept ? 'Đã chấp nhận hàng trả và hoàn tiền vào ví khách.' : 'Đã từ chối hoàn tiền và báo khách.');
    }

    private function run(RefundRequest $refund, callable $fn, string $ok)
    {
        try {
            $fn();
        } catch (RefundException $e) {
            return redirect()->route('admin.refunds.show', $refund->id)->with('error', $e->getMessage());
        }

        return redirect()->route('admin.refunds.show', $refund->id)->with('success', $ok);
    }
}
