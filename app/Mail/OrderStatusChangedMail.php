<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Email thông báo khi trạng thái đơn hàng thay đổi
 * (đã xác nhận, đang giao, đã giao thành công, đã hủy...).
 */
class OrderStatusChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Order $order;
    public string $previousStatus;

    public function __construct(Order $order, string $previousStatus = '')
    {
        $this->order = $order;
        $this->previousStatus = $previousStatus;
    }

    public function build()
    {
        $label = Order::STATUSES[$this->order->status] ?? $this->order->status;

        return $this->subject('[ToyLand] Đơn hàng #' . $this->order->id . ' - ' . $label)
            ->view('emails.order-status');
    }
}
