<?php

namespace App\Services\Orders;

use App\Mail\OrderStatusChangedMail;
use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Models\PaymentTransaction;
use App\Models\PointTransaction;
use App\Models\Product;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\Shipping\GhnCarrier;
use App\Services\Shipping\ShippingCarrier;
use App\Services\Shipping\SimulatedCarrier;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * BỘ ĐIỀU PHỐI LUỒNG ĐƠN HÀNG - nơi DUY NHẤT được đổi trạng thái đơn.
 *
 * Mọi thao tác (shop bấm nút, khách hủy, bên vận chuyển báo, MoMo báo đã trả
 * tiền, hệ thống tự hủy đơn quá hạn) đều đi qua apply(). Nhờ vậy:
 *  - Không ai nhảy cóc được (ví dụ từ "Chờ xác nhận" sang thẳng "Đã giao").
 *  - Mỗi vai trò chỉ làm được đúng việc của mình (shop không tự bấm "Đã giao",
 *    bên vận chuyển không xác nhận đơn thay shop).
 *  - Các việc đi kèm luôn xảy ra đầy đủ: trả kho, cập nhật thanh toán, tạo mã
 *    vận đơn, cộng điểm, ghi nhật ký, báo cho khách.
 *  - Đơn bị KHÓA DÒNG (lockForUpdate) trong lúc xử lý, nên hai người bấm cùng
 *    lúc hay xử lý hàng loạt trùng đơn cũng không làm sai dữ liệu.
 *
 * Vai trò (actor): admin (shop) | customer (khách) | carrier (bên vận chuyển) | system
 */
class OrderWorkflow
{
    public const ACTORS = ['admin', 'customer', 'carrier', 'system'];

    /**
     * Bảng hành động. from = trạng thái đơn được phép; shipping_from = trạng thái
     * vận chuyển được phép (nếu có); to = trạng thái đơn sau đó; ship = trạng thái
     * vận chuyển sau đó.
     */
    public const ACTIONS = [
        // ---- Hệ thống ----
        'mark_paid' => [
            'label' => 'Ghi nhận đã thanh toán', 'actors' => ['system'],
            'from' => ['awaiting_payment'], 'to' => 'pending',
        ],
        'expire' => [
            'label' => 'Hủy do quá hạn thanh toán', 'actors' => ['system'],
            'from' => ['awaiting_payment'], 'to' => 'cancelled', 'ship' => 'cancelled',
        ],

        // ---- Shop ----
        'confirm' => [
            'label' => 'Xác nhận đơn', 'actors' => ['admin'],
            'from' => ['pending'], 'to' => 'confirmed', 'ship' => 'not_shipped',
        ],
        'pack' => [
            'label' => 'Đóng gói & tạo vận đơn', 'actors' => ['admin'],
            'from' => ['confirmed'], 'to' => 'packed', 'ship' => 'ready_to_pick',
        ],
        'cancel' => [
            'label' => 'Hủy đơn', 'actors' => ['admin', 'customer'],
            'from' => ['awaiting_payment', 'pending', 'confirmed', 'packed'], 'to' => 'cancelled', 'ship' => 'cancelled',
            'note_required' => true,
        ],

        // ---- Bên vận chuyển ----
        'pick' => [
            'label' => 'Đã lấy hàng', 'actors' => ['carrier'],
            'from' => ['packed'], 'shipping_from' => ['ready_to_pick', 'picking'], 'to' => 'shipping', 'ship' => 'picked',
        ],
        'out_for_delivery' => [
            'label' => 'Đi giao hàng', 'actors' => ['carrier'],
            'from' => ['shipping'], 'shipping_from' => ['picked', 'storing', 'transporting', 'sorting', 'delivery_fail'],
            'to' => 'shipping', 'ship' => 'delivering',
        ],
        'deliver' => [
            'label' => 'Giao thành công', 'actors' => ['carrier'],
            'from' => ['shipping'], 'shipping_from' => ['delivering'], 'to' => 'completed', 'ship' => 'delivered',
        ],
        'fail_delivery' => [
            'label' => 'Giao thất bại', 'actors' => ['carrier'],
            'from' => ['shipping'], 'shipping_from' => ['delivering'], 'to' => 'shipping', 'ship' => 'delivery_fail',
            'note_required' => true,
        ],
        'start_return' => [
            'label' => 'Chuyển hoàn về shop', 'actors' => ['carrier'],
            'from' => ['shipping'], 'shipping_from' => ['delivery_fail'], 'to' => 'returning', 'ship' => 'returning',
            'note_required' => true,
        ],
        'confirm_returned' => [
            'label' => 'Đã nhận lại hàng hoàn', 'actors' => ['admin', 'carrier'],
            'from' => ['returning'], 'to' => 'returned', 'ship' => 'returned',
        ],
    ];

    /** Hành động được làm hàng loạt từ màn hình danh sách */
    public const BULK_ACTIONS = [
        'admin' => ['confirm', 'pack', 'cancel'],
        'carrier' => ['pick', 'out_for_delivery', 'deliver', 'confirm_returned'],
    ];

    public const BULK_LIMIT = 100;

    // ===================================================================
    // API CHÍNH
    // ===================================================================

    /**
     * Thực hiện một hành động trên một đơn.
     *
     * @param  array{note?: string, user_id?: int}  $opts
     *         note: lý do/ghi chú; user_id: chủ đơn (bắt buộc khi actor = customer)
     *
     * @throws WorkflowException
     */
    public function apply(Order|int $order, string $action, string $actor, array $opts = []): Order
    {
        $def = self::ACTIONS[$action] ?? throw new WorkflowException('Không có thao tác "' . $action . '".');

        if (! in_array($actor, $def['actors'], true)) {
            throw new WorkflowException('Vai trò hiện tại không được phép "' . $def['label'] . '".');
        }

        $note = trim((string) ($opts['note'] ?? ''));

        if (! empty($def['note_required']) && $note === '') {
            throw new WorkflowException('Thao tác "' . $def['label'] . '" cần ghi rõ lý do.');
        }

        $orderId = $order instanceof Order ? $order->id : (int) $order;

        try {
            [$updated, $fromStatus] = DB::transaction(function () use ($orderId, $action, $actor, $def, $note, $opts) {
                $o = Order::with('items')->lockForUpdate()->findOrFail($orderId);

                $this->guard($o, $action, $actor, $def, $opts);

                $fromStatus = $o->status;
                $fromShipping = $o->shipping_status;

                $this->performSideEffects($o, $action, $note);

                $o->status = $def['to'];

                if (isset($def['ship'])) {
                    $o->shipping_status = $def['ship'];
                }

                $o->save();

                OrderStatusLog::create([
                    'order_id' => $o->id,
                    'changed_by' => $actor === 'system' ? null : Auth::id(),
                    'from_status' => $fromStatus,
                    'to_status' => $o->status,
                    'note' => $this->logNote($o, $action, $actor, $note, $fromShipping),
                ]);

                return [$o, $fromStatus];
            });
        } catch (ModelNotFoundException $e) {
            throw new WorkflowException('Không tìm thấy đơn hàng #' . $orderId . '.');
        }

        // Báo tin SAU khi dữ liệu đã chốt - lỗi gửi thư không làm hỏng thao tác
        $this->notify($updated, $action, $actor, $fromStatus, $note);

        return $updated;
    }

    /**
     * Xử lý hàng loạt: từng đơn chạy riêng, đơn này lỗi không kéo đơn khác hỏng.
     *
     * @return array{ok: int[], failed: array<int, string>}
     */
    public function bulk(array $ids, string $action, string $actor, array $opts = []): array
    {
        $ids = array_slice(array_values(array_unique(array_filter(array_map('intval', $ids)))), 0, self::BULK_LIMIT);
        $ok = [];
        $failed = [];

        foreach ($ids as $id) {
            try {
                $this->apply($id, $action, $actor, $opts);
                $ok[] = $id;
            } catch (WorkflowException $e) {
                $failed[$id] = $e->getMessage();
            } catch (Throwable $e) {
                report($e);
                $failed[$id] = 'Lỗi hệ thống, thử lại sau.';
            }
        }

        return ['ok' => $ok, 'failed' => $failed];
    }

    /**
     * Những hành động vai trò này làm được với đơn NGAY LÚC NÀY (để vẽ nút bấm).
     *
     * @return array<string, string> action => nhãn
     */
    public function available(Order $order, string $actor, array $opts = []): array
    {
        $result = [];

        foreach (self::ACTIONS as $action => $def) {
            if (! in_array($actor, $def['actors'], true)) {
                continue;
            }

            try {
                $this->guard($order, $action, $actor, $def, $opts);
                $result[$action] = $def['label'];
            } catch (WorkflowException $e) {
                // không làm được -> không hiện nút
            }
        }

        return $result;
    }

    /**
     * Hủy các đơn MoMo quá thời gian giữ hàng mà chưa thanh toán -> trả hàng về kho.
     *
     * Render gói miễn phí không có cron chạy từng phút, nên hàm này được gọi
     * "khi cần": lúc có người đặt hàng, lúc mở danh sách đơn, lúc khách xem đơn...
     * Không cần chính xác tới từng giây: điều quan trọng là hàng giữ quá hạn
     * được trả lại TRƯỚC khi người khác cần mua.
     *
     * $force = false: chạy tối đa 1 lần/phút (đỡ tốn truy vấn ở các trang xem).
     */
    public function releaseExpired(bool $force = false): int
    {
        if (! $force && ! Cache::add('orders:release-expired', 1, 60)) {
            return 0;
        }

        $ids = Order::where('status', 'awaiting_payment')
            ->whereNotNull('payment_expires_at')
            ->where('payment_expires_at', '<', now())
            ->orderBy('id')
            ->limit(50)
            ->pluck('id');

        $released = 0;

        foreach ($ids as $id) {
            try {
                $this->apply($id, 'expire', 'system', ['note' => 'Quá ' . $this->holdMinutes() . ' phút chưa thanh toán']);
                $released++;
            } catch (Throwable $e) {
                Log::warning('Không tự hủy được đơn quá hạn #' . $id, ['message' => $e->getMessage()]);
            }
        }

        return $released;
    }

    /**
     * MoMo báo đã nhận tiền (gọi từ MomoService::markPaid).
     * - Đơn đang chờ thanh toán: chuyển sang Chờ xác nhận.
     * - Đơn đã bị hủy vì quá hạn: tiền đến muộn -> chuyển "chờ hoàn tiền" và báo shop.
     */
    public function paymentReceived(Order $order): void
    {
        if ($order->status === 'awaiting_payment') {
            $order->payment_status = 'paid';
            $order->save();
            $this->apply($order->id, 'mark_paid', 'system', ['note' => 'MoMo xác nhận đã thanh toán']);

            return;
        }

        if ($order->status === 'cancelled') {
            $order->update(['payment_status' => 'refund_pending']);

            PaymentTransaction::where('order_id', $order->id)->where('status', 'paid')
                ->update(['status' => 'refund_pending', 'message' => 'Tiền về sau khi đơn đã hủy - cần hoàn cho khách']);

            OrderStatusLog::create([
                'order_id' => $order->id,
                'changed_by' => null,
                'from_status' => 'cancelled',
                'to_status' => 'cancelled',
                'note' => '[Hệ thống] MoMo báo đã thanh toán SAU KHI đơn đã hủy -> chờ hoàn tiền cho khách',
            ]);

            UserNotification::sendToAdmins(
                'order',
                'Cần hoàn tiền đơn #' . $order->id,
                'Khách thanh toán MoMo sau khi đơn đã hết hạn/bị hủy.',
                route('admin.orders.show', $order->id)
            );
            UserNotification::send(
                $order->user_id,
                'order_status',
                'Đơn #' . $order->id . ': shop sẽ hoàn tiền',
                'Thanh toán của bạn đến sau khi đơn đã hết thời gian giữ hàng. Shop sẽ hoàn lại tiền cho bạn.',
                route('orders.show', $order->id)
            );

            return;
        }

        if ($order->payment_status !== 'paid') {
            $order->update(['payment_status' => 'paid']);
        }
    }

    public function holdMinutes(): int
    {
        return max(5, (int) config('toyland.orders.payment_hold_minutes', 30));
    }

    public function maxDeliveryAttempts(): int
    {
        return max(1, (int) config('toyland.orders.max_delivery_attempts', 3));
    }

    // ===================================================================
    // KIỂM TRA ĐIỀU KIỆN
    // ===================================================================

    private function guard(Order $o, string $action, string $actor, array $def, array $opts): void
    {
        $label = $def['label'];

        if (! in_array($o->status, $def['from'], true)) {
            throw new WorkflowException(sprintf(
                'Đơn #%d đang "%s", không thể "%s".',
                $o->id,
                Order::STATUSES[$o->status] ?? $o->status,
                $label
            ));
        }

        if (isset($def['shipping_from']) && ! in_array($o->shipping_status, $def['shipping_from'], true)) {
            throw new WorkflowException(sprintf(
                'Đơn #%d đang ở bước vận chuyển "%s", không thể "%s".',
                $o->id,
                Order::SHIPPING_LABELS[$o->shipping_status] ?? $o->shipping_status,
                $label
            ));
        }

        if ($actor === 'customer') {
            if ((int) $o->user_id !== (int) ($opts['user_id'] ?? 0)) {
                throw new WorkflowException('Bạn không có quyền thao tác trên đơn hàng này.');
            }

            if ($action === 'cancel' && ! $o->canBeCancelledByCustomer()) {
                throw new WorkflowException('Đơn đã thanh toán hoặc đã bàn giao vận chuyển nên không tự hủy được. Vui lòng liên hệ shop.');
            }
        }

        if ($action === 'confirm' && $o->payment_method === 'momo' && ! $o->isPaid()) {
            throw new WorkflowException('Đơn #' . $o->id . ' thanh toán MoMo nhưng chưa nhận được tiền.');
        }

        if ($action === 'expire' && $o->payment_expires_at && $o->payment_expires_at->isFuture()) {
            throw new WorkflowException('Đơn #' . $o->id . ' vẫn còn trong thời gian chờ thanh toán.');
        }

        if ($action === 'out_for_delivery' && (int) $o->delivery_attempts >= $this->maxDeliveryAttempts()) {
            throw new WorkflowException(sprintf(
                'Đơn #%d đã giao không thành công %d lần - hãy chuyển hoàn về shop.',
                $o->id,
                $o->delivery_attempts
            ));
        }
    }

    // ===================================================================
    // CÁC VIỆC ĐI KÈM MỖI BƯỚC
    // ===================================================================

    private function performSideEffects(Order $o, string $action, string $note): void
    {
        switch ($action) {
            case 'mark_paid':
                $o->payment_expires_at = null;
                break;

            case 'pack':
                // MÃ VẬN ĐƠN được tạo ở bước này: hàng đã đóng gói, cân được, dán
                // được nhãn -> mới gọi đơn vị vận chuyển tới lấy.
                $carrier = app(ShippingCarrier::class);
                try {
                    $o->ghn_order_code = $carrier->createWaybill($o);
                } catch (\RuntimeException $e) {
                    throw new WorkflowException('Đơn #' . $o->id . ': ' . $e->getMessage());
                }
                $o->carrier = $carrier->code();
                $o->waybill_created_at = now();
                break;

            case 'cancel':
            case 'expire':
                if ($o->ghn_order_code && $o->status === 'packed') {
                    try {
                        $this->carrierFor($o)->cancelWaybill($o);
                    } catch (\RuntimeException $e) {
                        throw new WorkflowException('Đơn #' . $o->id . ': ' . $e->getMessage());
                    }
                }
                $this->restock($o);
                $this->settlePaymentOnCancel($o, $action === 'expire' ? 'Hết hạn thanh toán' : 'Đơn hàng bị hủy');
                $o->cancel_reason = $note !== '' ? $note : null;
                $o->cancelled_at = now();
                break;

            case 'pick':
                $o->picked_at = now();
                break;

            case 'out_for_delivery':
                $o->delivery_attempts = (int) $o->delivery_attempts + 1;
                $o->last_delivery_note = $note !== '' ? $note : null;
                break;

            case 'fail_delivery':
                $o->last_delivery_note = $note;
                break;

            case 'deliver':
                $o->delivered_at = now();
                $o->last_delivery_note = $note !== '' ? $note : null;
                $this->collectCod($o);
                if ((int) $o->points_earned <= 0) {
                    $this->awardLoyaltyPoints($o);
                }
                break;

            case 'start_return':
                $o->last_delivery_note = $note;
                break;

            case 'confirm_returned':
                $o->returned_at = now();
                $this->restock($o);
                $this->settlePaymentOnCancel($o, 'Khách không nhận hàng - đã hoàn về shop');
                break;
        }
    }

    /** Cộng trả kho đúng MỘT lần cho mỗi đơn (restocked_at là chốt chặn) */
    private function restock(Order $o): void
    {
        if ($o->restocked_at) {
            return;
        }

        foreach ($o->items as $item) {
            Product::where('id', $item->product_id)->increment('quantity', $item->quantity);
        }

        $o->restocked_at = now();
    }

    /**
     * Đơn không thành: tiền đã thu thì chuyển "chờ hoàn tiền", chưa thu thì đóng
     * mọi giao dịch đang treo.
     */
    private function settlePaymentOnCancel(Order $o, string $message): void
    {
        if ($o->isPaid()) {
            $o->payment_status = 'refund_pending';

            PaymentTransaction::where('order_id', $o->id)->where('status', 'paid')
                ->update(['status' => 'refund_pending', 'message' => $message . ' - chờ hoàn tiền cho khách']);

            return;
        }

        $o->payment_status = 'cancelled';

        PaymentTransaction::where('order_id', $o->id)
            ->whereIn('status', ['pending', 'initiated', 'failed'])
            ->update(['status' => 'cancelled', 'message' => $message]);
    }

    /** Giao thành công đơn COD = bên vận chuyển đã thu hộ tiền */
    private function collectCod(Order $o): void
    {
        if ($o->payment_method !== 'cod' || $o->isPaid()) {
            return;
        }

        $o->payment_status = 'paid';

        $updated = PaymentTransaction::where('order_id', $o->id)
            ->where('gateway', 'cod')
            ->whereIn('status', ['pending', 'failed'])
            ->update(['status' => 'paid', 'paid_at' => now(), 'message' => 'Đơn vị vận chuyển đã thu hộ khi giao hàng']);

        if ($updated === 0) {
            PaymentTransaction::create([
                'order_id' => $o->id,
                'gateway' => 'cod',
                'amount' => $o->total_price,
                'status' => 'paid',
                'paid_at' => now(),
                'message' => 'Đơn vị vận chuyển đã thu hộ khi giao hàng',
            ]);
        }
    }

    /**
     * Cộng điểm thưởng khi đơn giao thành công: mỗi 10.000đ = 1 điểm, đồng thời
     * cộng dồn tổng chi tiêu để hệ thống xét lại hạng thành viên.
     */
    private function awardLoyaltyPoints(Order $o): void
    {
        $user = User::lockForUpdate()->find($o->user_id);

        if (! $user) {
            return;
        }

        $points = (int) floor($o->total_price / User::POINTS_PER_VND);

        $o->points_earned = $points;

        $user->lifetime_spent = (int) $user->lifetime_spent + (int) $o->total_price;
        $user->points = (int) $user->points + $points;
        $user->save();

        if ($points > 0) {
            PointTransaction::create([
                'user_id' => $user->id,
                'order_id' => $o->id,
                'points' => $points,
                'reason' => 'Tích điểm từ đơn hàng #' . $o->id,
            ]);
        }
    }

    private function carrierFor(Order $o): ShippingCarrier
    {
        return $o->carrier === 'ghn' ? app(GhnCarrier::class) : app(SimulatedCarrier::class);
    }

    // ===================================================================
    // NHẬT KÝ & THÔNG BÁO
    // ===================================================================

    private function logNote(Order $o, string $action, string $actor, string $note, ?string $fromShipping): string
    {
        $prefix = [
            'admin' => '[Shop]',
            'customer' => '[Khách]',
            'carrier' => '[Vận chuyển]',
            'system' => '[Hệ thống]',
        ][$actor];

        $text = match ($action) {
            'pack' => 'Đóng gói xong, tạo vận đơn ' . $o->ghn_order_code,
            'out_for_delivery' => 'Đi giao lần ' . $o->delivery_attempts,
            'fail_delivery' => 'Giao thất bại lần ' . $o->delivery_attempts,
            default => self::ACTIONS[$action]['label'],
        };

        return mb_substr(trim($prefix . ' ' . $text . ($note !== '' ? ': ' . $note : '')), 0, 255);
    }

    private function notify(Order $o, string $action, string $actor, string $fromStatus, string $note): void
    {
        // Khách tự hủy thì khách đã biết, không cần báo lại
        if ($actor === 'customer') {
            return;
        }

        $messages = [
            'mark_paid' => 'Shop đã nhận được thanh toán, đơn đang chờ xác nhận.',
            'expire' => 'Đơn đã tự hủy vì quá ' . $this->holdMinutes() . ' phút chưa thanh toán. Bạn có thể đặt lại bất cứ lúc nào.',
            'confirm' => 'Shop đã xác nhận đơn, đang chuẩn bị hàng cho bạn.',
            'pack' => 'Đơn đã đóng gói xong. Mã vận đơn: ' . $o->ghn_order_code . '.',
            'cancel' => 'Đơn đã được hủy' . ($note !== '' ? ' (' . $note . ')' : '') . '. Liên hệ 1900 6868 nếu bạn cần hỗ trợ.',
            'pick' => 'Đơn vị vận chuyển đã lấy hàng, đơn đang trên đường tới bạn.',
            'out_for_delivery' => 'Shipper đang giao đơn tới bạn (lần ' . $o->delivery_attempts . '). Giữ máy để shipper liên hệ nhé!',
            'deliver' => 'Đơn đã giao thành công. Mời bạn đánh giá sản phẩm để nhận điểm thưởng.',
            'fail_delivery' => 'Shipper chưa giao được đơn' . ($note !== '' ? ': ' . $note : '') . '. Shop sẽ liên hệ để giao lại.',
            'start_return' => 'Đơn không giao được và đang được hoàn về shop.',
            'confirm_returned' => 'Đơn đã hoàn về shop.' . ($o->payment_status === 'refund_pending' ? ' Shop sẽ hoàn tiền cho bạn.' : ''),
        ];

        UserNotification::send(
            $o->user_id,
            in_array($action, ['cancel', 'expire'], true) ? 'system' : 'order_status',
            'Đơn #' . $o->id . ': ' . ($action === 'fail_delivery' ? 'Giao hàng chưa thành công' : $o->status_label),
            $messages[$action] ?? 'Trạng thái đơn hàng vừa được cập nhật.',
            route('orders.show', $o->id)
        );

        if (in_array($action, ['start_return', 'fail_delivery'], true)) {
            UserNotification::sendToAdmins(
                'order',
                'Đơn #' . $o->id . ': ' . self::ACTIONS[$action]['label'],
                $note !== '' ? $note : null,
                route('admin.orders.show', $o->id)
            );
        }

        // Email chỉ gửi khi trạng thái đơn thực sự đổi (không gửi cho từng bước nhỏ của shipper)
        if ($fromStatus === $o->status) {
            return;
        }

        try {
            $o->loadMissing('user');
            $email = $o->user->email ?? null;

            if ($email) {
                Mail::to($email)->send(new OrderStatusChangedMail($o, $fromStatus));
            }
        } catch (Throwable $e) {
            Log::warning('Không gửi được email cập nhật đơn #' . $o->id, ['message' => $e->getMessage()]);
        }
    }
}
