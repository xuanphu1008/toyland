<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\RefundRequest;
use App\Services\RefundException;
use App\Services\RefundService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Khách báo bị trừ tiền / yêu cầu trả hàng / gửi hàng trả / hủy yêu cầu */
class RefundRequestController extends Controller
{
    public function __construct(private RefundService $refunds)
    {
    }

    public function reportPayment(Request $request, Order $order)
    {
        $data = $request->validate([
            'momo_trans_id' => 'required|string|max:60',
            'note' => 'nullable|string|max:500',
        ], ['momo_trans_id.required' => 'Vui lòng nhập mã giao dịch MoMo.']);

        return $this->run(fn () => $this->refunds->requestPaymentNotReceived(Auth::user(), $order, $data['momo_trans_id'], $data['note'] ?? null),
            $order, 'Đã gửi báo cáo. Shop sẽ đối chiếu giao dịch MoMo và hoàn tiền vào ví nếu đúng.');
    }

    public function requestReturn(Request $request, Order $order)
    {
        $data = $request->validate([
            'reason_code' => 'required|in:' . implode(',', array_keys(RefundRequest::RETURN_REASONS)),
            'description' => 'required|string|min:10|max:1000',
        ], [
            'reason_code.required' => 'Vui lòng chọn lý do trả hàng.',
            'description.required' => 'Vui lòng mô tả tình trạng hàng.',
            'description.min' => 'Vui lòng mô tả tình trạng hàng (ít nhất 10 ký tự).',
        ]);

        return $this->run(fn () => $this->refunds->requestReturn(Auth::user(), $order, $data['reason_code'], $data['description']),
            $order, 'Đã gửi yêu cầu trả hàng. Shop sẽ phản hồi sớm - chưa gửi hàng khi shop chưa duyệt.');
    }

    public function ship(Request $request, RefundRequest $refund)
    {
        abort_if((int) $refund->user_id !== (int) Auth::id(), 403);
        $data = $request->validate(['carrier' => 'required|string|max:60', 'tracking' => 'required|string|max:60']);

        return $this->run(fn () => $this->refunds->markShipped(Auth::user(), $refund, $data['carrier'], $data['tracking']),
            $refund->order, 'Đã ghi nhận. Shop sẽ hoàn tiền vào ví sau khi nhận và kiểm tra hàng.');
    }

    public function cancel(RefundRequest $refund)
    {
        abort_if((int) $refund->user_id !== (int) Auth::id(), 403);

        return $this->run(fn () => $this->refunds->cancelByCustomer(Auth::user(), $refund), $refund->order, 'Đã hủy yêu cầu.');
    }

    private function run(callable $fn, Order $order, string $okMessage)
    {
        abort_if((int) $order->user_id !== (int) Auth::id(), 403);

        try {
            $fn();
        } catch (RefundException $e) {
            return redirect()->route('orders.show', $order->id)->with('error', $e->getMessage());
        }

        return redirect()->route('orders.show', $order->id)->with('success', $okMessage);
    }
}
