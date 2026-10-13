<?php

namespace App\Services;

use App\Mail\PaymentStatusChangedMail;
use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Models\PaymentTransaction;
use App\Models\RefundRequest;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\Orders\OrderWorkflow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * HOÀN TIỀN & TRẢ HÀNG - nơi DUY NHẤT xử lý các khoản tiền phải trả lại khách.
 *
 * Bốn tình huống, cùng một bảng refund_requests:
 *   1. order_cancelled       đơn đã thanh toán bị hủy/hết hạn/tiền về muộn  (hệ thống tự tạo)
 *   2. payment_not_received  MoMo đã trừ tiền nhưng shop không nhận được     (khách báo)
 *   3. duplicate_payment     một đơn bị thu tiền hai lần                      (hệ thống tự tạo)
 *   4. return_goods          trả hàng sau khi đã nhận                         (khách yêu cầu, có luồng riêng)
 *
 * Tiền hoàn luôn về VÍ TOYLAND của khách (xem WalletService): tức thì, và khách
 * dùng lại được cho đơn sau. Mọi thao tác tiền chạy trong giao dịch CSDL có khóa
 * dòng; làm lại lần hai gặp trạng thái "đã hoàn" và dừng (idempotent).
 *
 * ------- LUỒNG TRẢ HÀNG (return_goods) - mô phỏng đúng thực tế -------
 *   Đơn "Đã giao"  --(khách yêu cầu, trong hạn 7 ngày)-->  requested   Chờ shop duyệt
 *   requested      --(shop duyệt, nêu địa chỉ nhận)------>  approved    Chờ khách gửi hàng về (hạn 7 ngày)
 *   approved       --(khách nhập mã vận đơn trả)--------->  in_transit  Hàng đang gửi về
 *   in_transit     --(shop nhận được hàng)--------------->  received    Đang kiểm tra hàng
 *   received       --(hàng đạt)-->  refunded   hoàn tiền vào ví, đơn -> "Đã hoàn hàng", cộng kho nếu còn bán được,
 *                                               thu hồi điểm thưởng
 *                  --(không đạt)--> rejected   không hoàn tiền, shop gửi trả hàng cho khách
 *   requested|approved --(khách hủy / quá hạn gửi hàng)--> cancelled
 *   requested         --(shop từ chối, bắt buộc nêu lý do)--> rejected
 * Tiền CHỈ hoàn sau khi shop đã nhận và kiểm tra hàng - không hoàn trước.
 */
class RefundService
{
    public function __construct(private WalletService $wallet)
    {
    }

    // =====================================================================
    // 1 + 3. HỆ THỐNG TỰ TẠO YÊU CẦU (đơn bị hủy sau khi đã trả tiền, thanh toán trùng)
    // =====================================================================

    /** Với mọi giao dịch của đơn đang "chờ hoàn tiền", mở yêu cầu hoàn tương ứng */
    public function openForOrderPayments(Order $order, string $reason, string $type = 'order_cancelled'): void
    {
        foreach (PaymentTransaction::where('order_id', $order->id)->where('status', 'refund_pending')->get() as $tx) {
            $this->openForTransaction($order, $tx, $type, $reason);
        }
    }

    /**
     * Mở yêu cầu hoàn tiền cho MỘT khoản thanh toán. Idempotent: khoản này đã có
     * yêu cầu (chưa bị từ chối/hủy) thì trả lại chính yêu cầu đó.
     * Thanh toán bằng ví thì hoàn NGAY (tiền vốn của ví, không cần shop duyệt).
     */
    public function openForTransaction(Order $order, PaymentTransaction $tx, string $type, string $reason): RefundRequest
    {
        $existing = RefundRequest::where('payment_transaction_id', $tx->id)
            ->whereNotIn('status', ['rejected', 'cancelled'])->first();

        if ($existing) {
            return $existing;
        }

        // Khách đã được hoàn theo báo cáo "bị trừ tiền" trước đó rồi, tiền về muộn chỉ là
        // cùng một khoản đó -> đóng sổ giao dịch, không hoàn lần hai.
        $prior = RefundRequest::where('order_id', $order->id)->where('type', 'payment_not_received')
            ->where('status', 'refunded')->first();

        if ($prior && $type === 'order_cancelled') {
            $tx->update(['status' => 'refunded', 'message' => 'Đã hoàn trước đó theo yêu cầu #' . $prior->id]);
            $order->payment_status = 'refunded';
            $order->save();

            return $prior;
        }

        $r = RefundRequest::create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'payment_transaction_id' => $tx->id,
            'type' => $type,
            'source' => 'system',
            'reason' => $reason,
            'amount' => (int) round($tx->amount),
            'status' => 'requested',
        ]);

        if ($tx->gateway === 'wallet') {
            return $this->payout($r, null, 'Tự động hoàn về ví vì đơn không thành', $order);
        }

        UserNotification::sendToAdmins(
            'order',
            'Cần hoàn tiền đơn #' . $order->id,
            $r->type_label . ' - ' . number_format($r->amount, 0, ',', '.') . ' đ',
            route('admin.refunds.show', $r->id)
        );

        $this->tell($order->user_id, 'Đơn #' . $order->id . ': shop sẽ hoàn tiền cho bạn',
            'Khoản ' . number_format($r->amount, 0, ',', '.') . ' đ sẽ được hoàn vào Ví ToyLand của bạn sau khi shop xác nhận.',
            route('wallet.index'));

        return $r;
    }

    // =====================================================================
    // 2. KHÁCH BÁO "ĐÃ BỊ TRỪ TIỀN NHƯNG ĐƠN CHƯA GHI NHẬN"
    // =====================================================================

    public function requestPaymentNotReceived(User $user, Order $order, string $momoTransId, ?string $note): RefundRequest
    {
        $this->ownedBy($order, $user);
        $momoTransId = trim($momoTransId);

        if ($order->payment_method !== 'momo') {
            throw new RefundException('Chỉ đơn thanh toán MoMo mới dùng được chức năng này.');
        }
        if (! in_array($order->status, ['awaiting_payment', 'cancelled'], true) || $order->customerHasPaid()
            || in_array($order->payment_status, ['refund_pending', 'refunded'], true)) {
            throw new RefundException('Đơn này đã ghi nhận thanh toán hoặc đang được hoàn tiền, không cần báo thêm.');
        }
        if ($momoTransId === '' || mb_strlen($momoTransId) > 60) {
            throw new RefundException('Vui lòng nhập mã giao dịch MoMo (trong tin nhắn/lịch sử giao dịch MoMo của bạn).');
        }
        if (RefundRequest::where('order_id', $order->id)->where('type', 'payment_not_received')->whereIn('status', RefundRequest::OPEN)->exists()) {
            throw new RefundException('Bạn đã gửi báo cáo cho đơn này, shop đang kiểm tra.');
        }

        $r = RefundRequest::create([
            'order_id' => $order->id, 'user_id' => $user->id, 'type' => 'payment_not_received', 'source' => 'customer',
            'momo_trans_id' => $momoTransId, 'reason' => $note, 'amount' => (int) $order->total_price, 'status' => 'requested',
        ]);

        UserNotification::sendToAdmins('order', 'Khách báo bị trừ tiền - đơn #' . $order->id,
            'Mã giao dịch MoMo: ' . $momoTransId . '. Hãy đối chiếu trong cổng quản trị MoMo.', route('admin.refunds.show', $r->id));

        return $r;
    }

    // =====================================================================
    // 4. TRẢ HÀNG SAU KHI NHẬN
    // =====================================================================

    /** @return array{0: bool, 1: string} [được yêu cầu không, lý do nếu không] */
    public function canRequestReturn(Order $order): array
    {
        if ($order->status !== 'completed') {
            return [false, 'Chỉ đơn đã giao thành công mới yêu cầu trả hàng được.'];
        }
        if (! $order->customerHasPaid() && $order->payment_status !== 'paid') {
            return [false, 'Đơn chưa ghi nhận thanh toán.'];
        }
        $days = (int) config('toyland.refunds.return_window_days', 7);
        $deliveredAt = $order->delivered_at ?? $order->updated_at;
        if ($deliveredAt->copy()->addDays($days)->isPast()) {
            return [false, 'Đã quá ' . $days . ' ngày kể từ lúc nhận hàng nên không còn trả hàng được.'];
        }
        if (RefundRequest::where('order_id', $order->id)->where('type', 'return_goods')->whereNotIn('status', ['rejected', 'cancelled'])->exists()) {
            return [false, 'Đơn này đã có yêu cầu trả hàng.'];
        }

        return [true, ''];
    }

    /** Tiền được hoàn: lỗi do shop -> hoàn 100%; khách đổi ý -> trừ phí vận chuyển chiều đi */
    public function returnAmount(Order $order, string $reasonCode): int
    {
        $shopFault = RefundRequest::RETURN_REASONS[$reasonCode][1] ?? false;
        $total = (int) $order->total_price;

        return $shopFault ? $total : max(0, $total - (int) $order->ghn_total_fee);
    }

    public function requestReturn(User $user, Order $order, string $reasonCode, string $description): RefundRequest
    {
        $this->ownedBy($order, $user);
        $description = trim($description);

        if (! isset(RefundRequest::RETURN_REASONS[$reasonCode])) {
            throw new RefundException('Vui lòng chọn lý do trả hàng.');
        }
        if (mb_strlen($description) < 10) {
            throw new RefundException('Vui lòng mô tả tình trạng hàng (ít nhất 10 ký tự) để shop xử lý nhanh.');
        }

        return DB::transaction(function () use ($user, $order, $reasonCode, $description) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            [$ok, $why] = $this->canRequestReturn($order);
            if (! $ok) {
                throw new RefundException($why);
            }

            $r = RefundRequest::create([
                'order_id' => $order->id, 'user_id' => $user->id, 'type' => 'return_goods', 'source' => 'customer',
                'reason_code' => $reasonCode, 'reason' => $description,
                'amount' => $this->returnAmount($order, $reasonCode), 'status' => 'requested',
            ]);

            UserNotification::sendToAdmins('order', 'Khách yêu cầu trả hàng - đơn #' . $order->id,
                $r->reason_label . ': ' . mb_substr($description, 0, 120), route('admin.refunds.show', $r->id));

            return $r;
        });
    }

    /** Shop đồng ý cho trả hàng: nêu địa chỉ nhận, khách có hạn để gửi về */
    public function approveReturn(RefundRequest $r, User $admin, ?string $note): RefundRequest
    {
        $r = DB::transaction(function () use ($r, $admin, $note) {
            $r = $this->lock($r);
            $this->mustBe($r, 'return_goods', ['requested']);

            $days = (int) config('toyland.refunds.ship_back_days', 7);
            $r->update([
                'status' => 'approved', 'approved_at' => now(), 'ship_back_deadline' => now()->addDays($days),
                'handled_by' => $admin->id, 'admin_note' => $note,
            ]);
            $this->log($r->order_id, $admin->id, '[Shop] Duyệt yêu cầu trả hàng #' . $r->id . ($note ? ': ' . $note : ''));

            return $r;
        });

        $this->tell($r->user_id, 'Đơn #' . $r->order_id . ': shop đã duyệt yêu cầu trả hàng',
            'Hãy gửi hàng về: ' . config('toyland.refunds.return_address') . '. Gửi trước ' . $r->ship_back_deadline->format('d/m/Y')
            . ' rồi nhập mã vận đơn trong chi tiết đơn. Tiền được hoàn vào ví sau khi shop nhận và kiểm tra hàng.',
            route('orders.show', $r->order_id));

        return $r;
    }

    /** Khách đã gửi hàng về: nhập đơn vị vận chuyển + mã vận đơn */
    public function markShipped(User $user, RefundRequest $r, string $carrier, string $tracking): RefundRequest
    {
        $carrier = trim($carrier);
        $tracking = trim($tracking);
        if ($carrier === '' || $tracking === '') {
            throw new RefundException('Vui lòng nhập đơn vị vận chuyển và mã vận đơn của kiện hàng trả.');
        }

        $r = DB::transaction(function () use ($user, $r, $carrier, $tracking) {
            $r = $this->lock($r);
            if ((int) $r->user_id !== (int) $user->id) {
                throw new RefundException('Bạn không có quyền thao tác trên yêu cầu này.');
            }
            $this->mustBe($r, 'return_goods', ['approved']);
            if ($r->ship_back_deadline && $r->ship_back_deadline->isPast()) {
                throw new RefundException('Đã quá hạn gửi hàng, yêu cầu này không còn hiệu lực.');
            }

            $r->update([
                'status' => 'in_transit', 'shipped_at' => now(),
                'return_carrier' => mb_substr($carrier, 0, 60), 'return_tracking_code' => mb_substr($tracking, 0, 60),
            ]);
            $this->log($r->order_id, $user->id, '[Khách] Đã gửi hàng trả: ' . $carrier . ' - ' . $tracking);

            return $r;
        });

        UserNotification::sendToAdmins('order', 'Hàng trả đang về - đơn #' . $r->order_id,
            $r->return_carrier . ' ' . $r->return_tracking_code, route('admin.refunds.show', $r->id));

        return $r;
    }

    /** Shop nhận được kiện hàng trả, bắt đầu kiểm tra */
    public function markReceived(RefundRequest $r, User $admin): RefundRequest
    {
        $r = DB::transaction(function () use ($r, $admin) {
            $r = $this->lock($r);
            $this->mustBe($r, 'return_goods', ['approved', 'in_transit']);
            $r->update(['status' => 'received', 'received_at' => now(), 'handled_by' => $admin->id]);
            $this->log($r->order_id, $admin->id, '[Shop] Đã nhận hàng trả, đang kiểm tra (yêu cầu #' . $r->id . ')');

            return $r;
        });

        $this->tell($r->user_id, 'Đơn #' . $r->order_id . ': shop đã nhận hàng trả',
            'Shop đang kiểm tra hàng, kết quả và tiền hoàn sẽ được báo cho bạn sớm.', route('orders.show', $r->order_id));

        return $r;
    }

    /**
     * Kết quả kiểm hàng.
     *  - đạt: đơn -> "Đã hoàn hàng" (qua OrderWorkflow), cộng kho nếu còn bán được, thu hồi điểm,
     *    hoàn tiền vào ví - tất cả trong MỘT giao dịch.
     *  - không đạt: không hoàn tiền, ghi rõ lý do, shop gửi trả hàng cho khách.
     */
    public function finishInspection(RefundRequest $r, User $admin, bool $accept, bool $restock, ?string $note): RefundRequest
    {
        $note = $note !== null ? trim($note) : null;

        if (! $accept && ($note === null || $note === '')) {
            throw new RefundException('Hàng không đạt thì phải ghi rõ lý do để báo khách.');
        }

        $r = DB::transaction(function () use ($r, $admin, $accept, $restock, $note) {
            $r = $this->lock($r);
            $this->mustBe($r, 'return_goods', ['received']);

            if (! $accept) {
                $r->update(['status' => 'rejected', 'handled_by' => $admin->id, 'handled_at' => now(), 'admin_note' => $note, 'restock' => false]);
                $this->log($r->order_id, $admin->id, '[Shop] Hàng trả không đạt, từ chối hoàn tiền: ' . $note);

                return $r;
            }

            $order = Order::whereKey($r->order_id)->lockForUpdate()->firstOrFail();

            // Chuyển đơn "Đã giao" -> "Đã hoàn hàng": cộng kho + thu hồi điểm do OrderWorkflow lo
            app(OrderWorkflow::class)->apply($order, 'return_accepted', 'admin', [
                'via_refund' => true, 'restock' => $restock, 'note' => 'Nhận hàng trả đạt (yêu cầu #' . $r->id . ')',
            ]);
            $order->refresh();

            $this->settle($r, $order, $admin, $note ?: 'Hoàn tiền trả hàng', $restock);

            return $r;
        });

        if ($accept) {
            $this->announceRefunded($r);
        } else {
            $this->tell($r->user_id, 'Đơn #' . $r->order_id . ': yêu cầu trả hàng không được chấp nhận',
                'Lý do: ' . $note . '. Shop sẽ gửi lại hàng cho bạn. Cần hỗ trợ gọi 1900 6868.', route('orders.show', $r->order_id));
        }

        return $r;
    }

    // =====================================================================
    // HOÀN TIỀN (các loại không có luồng gửi hàng)
    // =====================================================================

    /** Duyệt và hoàn tiền về ví. $order: đơn đã khóa sẵn nếu đang gọi từ trong OrderWorkflow */
    public function payout(RefundRequest $r, ?User $admin, ?string $note, ?Order $order = null): RefundRequest
    {
        $r = DB::transaction(function () use ($r, $admin, $note, $order) {
            $r = $this->lock($r);

            if ($r->isReturn()) {
                throw new RefundException('Yêu cầu trả hàng phải đi theo luồng nhận hàng và kiểm tra.');
            }
            if ($r->status !== 'requested') {
                throw new RefundException('Yêu cầu này đã được xử lý (' . $r->status_label . ').');
            }

            $o = $order ?? Order::whereKey($r->order_id)->lockForUpdate()->firstOrFail();

            if ($r->type === 'payment_not_received' && $o->status !== 'cancelled') {
                throw new RefundException('Đơn đang còn giữ hàng chờ thanh toán. Hãy bấm "Ghi nhận đã nhận tiền" để đơn đi tiếp, hoặc từ chối nếu không có giao dịch.');
            }

            $this->settle($r, $o, $admin, $note ?: 'Hoàn tiền vào ví', null);

            return $r;
        });

        $this->announceRefunded($r);

        return $r;
    }

    /**
     * Phần chung của mọi lần hoàn: cộng ví, đóng giao dịch, đổi trạng thái thanh toán của đơn, ghi nhật ký.
     * Phải gọi trong giao dịch CSDL, trên yêu cầu và đơn đã khóa.
     */
    private function settle(RefundRequest $r, Order $order, ?User $admin, string $note, ?bool $restock): void
    {
        $tx = $r->payment_transaction_id ? PaymentTransaction::find($r->payment_transaction_id) : null;
        $walletType = $tx && $tx->gateway === 'wallet' ? 'order_cancel_return' : 'refund_in';

        $wt = $this->wallet->credit($r->user_id, (int) $r->amount, $walletType, [
            'order_id' => $r->order_id, 'refund_request_id' => $r->id, 'created_by' => $admin?->id,
            'note' => 'Hoàn tiền đơn #' . $r->order_id . ' (' . $r->type_label . ')',
        ]);

        if ($tx) {
            $tx->update(['status' => 'refunded', 'message' => 'Đã hoàn vào ví ToyLand (yêu cầu #' . $r->id . ')']);
        } elseif ($r->type === 'payment_not_received') {
            // Dọn các giao dịch MoMo còn treo của đơn đã hủy
            PaymentTransaction::where('order_id', $order->id)->whereIn('status', ['pending', 'initiated'])
                ->update(['status' => 'cancelled', 'message' => 'Đã hoàn tiền theo báo cáo của khách (yêu cầu #' . $r->id . ')']);
        }

        // Trạng thái thanh toán của đơn: hoàn xong là "Đã hoàn tiền" (trừ trường hợp hoàn khoản thu TRÙNG,
        // khi đó khoản thanh toán gốc vẫn hợp lệ)
        if ($r->type !== 'duplicate_payment') {
            $order->payment_status = 'refunded';
            $order->save();
        }

        $r->update([
            'status' => 'refunded', 'handled_by' => $admin?->id, 'handled_at' => now(),
            'admin_note' => $note, 'wallet_transaction_id' => $wt->id,
            'restock' => $restock ?? $r->restock,
        ]);

        $this->log($order->id, $admin?->id,
            ($admin ? '[Shop]' : '[Hệ thống]') . ' Hoàn ' . number_format($r->amount, 0, ',', '.') . ' đ vào ví khách (yêu cầu #' . $r->id . ', ' . $r->type_label . ')');
    }

    /** Từ chối (chỉ cho báo cáo của khách; khoản hệ thống tạo thì shop bắt buộc phải hoàn) */
    public function reject(RefundRequest $r, User $admin, string $reason): RefundRequest
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new RefundException('Từ chối thì phải ghi rõ lý do để báo khách.');
        }

        $r = DB::transaction(function () use ($r, $admin, $reason) {
            $r = $this->lock($r);
            if ($r->source === 'system') {
                throw new RefundException('Khoản này do hệ thống tạo vì đơn không thành, shop cần hoàn cho khách, không từ chối được.');
            }
            if (! in_array($r->status, ['requested'], true)) {
                throw new RefundException('Chỉ từ chối được yêu cầu đang chờ xử lý (hiện: ' . $r->status_label . ').');
            }
            $r->update(['status' => 'rejected', 'handled_by' => $admin->id, 'handled_at' => now(), 'admin_note' => $reason]);
            $this->log($r->order_id, $admin->id, '[Shop] Từ chối yêu cầu #' . $r->id . ' (' . $r->type_label . '): ' . $reason);

            return $r;
        });

        $this->tell($r->user_id, 'Đơn #' . $r->order_id . ': yêu cầu của bạn không được chấp nhận',
            'Lý do: ' . $reason . '. Cần hỗ trợ gọi 1900 6868 hoặc gửi phiếu hỗ trợ.', route('orders.show', $r->order_id));

        return $r;
    }

    /** Khách báo bị trừ tiền, shop kiểm tra MoMo thấy tiền đã về -> ghi nhận thanh toán cho đơn đang giữ hàng */
    public function resolveByConfirmingPayment(RefundRequest $r, User $admin): RefundRequest
    {
        $r = DB::transaction(function () use ($r, $admin) {
            $r = $this->lock($r);
            if ($r->type !== 'payment_not_received' || $r->status !== 'requested') {
                throw new RefundException('Yêu cầu này không ghi nhận thanh toán được.');
            }
            $order = Order::whereKey($r->order_id)->firstOrFail();
            if ($order->status !== 'awaiting_payment') {
                throw new RefundException('Đơn không còn ở trạng thái chờ thanh toán (hiện: ' . $order->status_label . ').');
            }

            $tx = PaymentTransaction::where('order_id', $order->id)->where('gateway', 'momo')->orderByDesc('id')->first();
            if (! $tx) {
                throw new RefundException('Đơn chưa có giao dịch MoMo nào để ghi nhận.');
            }

            app(MomoService::class)->markPaid($tx, [
                'transId' => $r->momo_trans_id, 'resultCode' => '0', 'message' => 'Shop xác nhận đã nhận tiền theo báo cáo của khách #' . $r->id,
            ]);

            $r->update(['status' => 'cancelled', 'handled_by' => $admin->id, 'handled_at' => now(),
                'admin_note' => 'Đã ghi nhận thanh toán MoMo, đơn tiếp tục xử lý - không cần hoàn tiền.']);
            $this->log($order->id, $admin->id, '[Shop] Xác nhận đã nhận tiền MoMo (mã ' . $r->momo_trans_id . ') theo báo cáo của khách');

            return $r;
        });

        $this->tell($r->user_id, 'Đơn #' . $r->order_id . ': shop đã ghi nhận thanh toán của bạn',
            'Cảm ơn bạn đã báo. Đơn đang được xử lý bình thường.', route('orders.show', $r->order_id));

        return $r;
    }

    public function cancelByCustomer(User $user, RefundRequest $r): RefundRequest
    {
        return DB::transaction(function () use ($user, $r) {
            $r = $this->lock($r);
            if ((int) $r->user_id !== (int) $user->id || $r->source !== 'customer') {
                throw new RefundException('Bạn không có quyền thao tác trên yêu cầu này.');
            }
            if ($r->isReturn() && ! in_array($r->status, ['requested', 'approved'], true)) {
                throw new RefundException('Hàng đã được gửi về shop nên không hủy yêu cầu được nữa.');
            }
            if (! $r->isReturn() && $r->status !== 'requested') {
                throw new RefundException('Yêu cầu đã được xử lý, không hủy được.');
            }
            $r->update(['status' => 'cancelled', 'handled_at' => now(), 'admin_note' => 'Khách tự hủy yêu cầu']);
            $this->log($r->order_id, $user->id, '[Khách] Hủy yêu cầu #' . $r->id . ' (' . $r->type_label . ')');

            return $r;
        });
    }

    /** Yêu cầu trả hàng đã duyệt nhưng khách không gửi hàng trong hạn -> tự hủy */
    public function expireStaleReturns(): int
    {
        $count = 0;
        $ids = RefundRequest::where('type', 'return_goods')->where('status', 'approved')
            ->whereNotNull('ship_back_deadline')->where('ship_back_deadline', '<', now())->limit(50)->pluck('id');

        foreach ($ids as $id) {
            try {
                $r = DB::transaction(function () use ($id) {
                    $r = RefundRequest::whereKey($id)->lockForUpdate()->first();
                    if (! $r || $r->status !== 'approved') {
                        return null;
                    }
                    $r->update(['status' => 'cancelled', 'handled_at' => now(), 'admin_note' => 'Quá hạn gửi hàng về shop']);
                    $this->log($r->order_id, null, '[Hệ thống] Yêu cầu trả hàng #' . $r->id . ' tự hủy vì khách không gửi hàng đúng hạn');

                    return $r;
                });
                if ($r) {
                    $this->tell($r->user_id, 'Đơn #' . $r->order_id . ': yêu cầu trả hàng đã hết hạn',
                        'Bạn chưa gửi hàng về trong thời hạn nên yêu cầu đã tự hủy.', route('orders.show', $r->order_id));
                    $count++;
                }
            } catch (Throwable $e) {
                Log::warning('Không tự hủy được yêu cầu trả hàng #' . $id, ['message' => $e->getMessage()]);
            }
        }

        return $count;
    }

    // =====================================================================
    // TRỢ GIÚP
    // =====================================================================

    private function lock(RefundRequest $r): RefundRequest
    {
        return RefundRequest::whereKey($r->id)->lockForUpdate()->firstOrFail();
    }

    private function mustBe(RefundRequest $r, string $type, array $statuses): void
    {
        if ($r->type !== $type || ! in_array($r->status, $statuses, true)) {
            throw new RefundException('Yêu cầu đang ở trạng thái "' . $r->status_label . '", không thực hiện được thao tác này.');
        }
    }

    private function ownedBy(Order $order, User $user): void
    {
        if ((int) $order->user_id !== (int) $user->id) {
            throw new RefundException('Bạn không có quyền thao tác trên đơn hàng này.');
        }
    }

    private function log(int $orderId, ?int $userId, string $note): void
    {
        $status = Order::whereKey($orderId)->value('status') ?? 'completed';
        OrderStatusLog::create([
            'order_id' => $orderId, 'changed_by' => $userId, 'from_status' => $status, 'to_status' => $status,
            'note' => mb_substr($note, 0, 255),
        ]);
    }

    private function tell(int $userId, string $title, string $body, string $url): void
    {
        UserNotification::send($userId, 'order', $title, $body, $url);
    }

    /** Báo khách đã hoàn tiền: thông báo trong ứng dụng + email (lỗi gửi thư chỉ ghi log) */
    private function announceRefunded(RefundRequest $r): void
    {
        $money = number_format($r->amount, 0, ',', '.') . ' đ';
        $message = 'Shop đã hoàn ' . $money . ' vào Ví ToyLand của bạn. Bạn dùng số dư này để thanh toán cho đơn hàng sau.';

        $this->tell($r->user_id, 'Đơn #' . $r->order_id . ': đã hoàn ' . $money . ' vào ví', $message, route('wallet.index'));

        try {
            $order = Order::with('user')->find($r->order_id);
            if ($order && $order->user && $order->user->email) {
                Mail::to($order->user->email)->send(new PaymentStatusChangedMail($order, 'refund_pending', 'refunded', 'Đã hoàn tiền vào ví', $message));
            }
        } catch (Throwable $e) {
            Log::warning('Không gửi được email hoàn tiền đơn #' . $r->order_id, ['message' => $e->getMessage()]);
        }
    }
}
