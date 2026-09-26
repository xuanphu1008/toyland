<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Email xác nhận đơn hàng gửi ngay sau khi khách đặt hàng thành công.
 */
class OrderPlacedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function build()
    {
        return $this->subject('[ToyLand] Xác nhận đơn hàng #' . $this->order->id)
            ->view('emails.order-placed');
    }
}
