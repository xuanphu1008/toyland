<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Email báo khách khi TRẠNG THÁI THANH TOÁN của đơn thay đổi
 * (shop xác nhận đã thu tiền COD, đang hoàn tiền, đã hoàn tiền...).
 *
 * Tách riêng khỏi OrderStatusChangedMail vì hai việc khác nhau: email kia báo
 * VÒNG ĐỜI ĐƠN (đã xác nhận, đang giao...), email này báo TIỀN. Dùng chung sẽ
 * khiến khách nhận tin "đơn đã xác nhận" trong khi thực ra shop vừa nhận tiền.
 *
 * Nhãn và lời nhắn do controller truyền vào, để email và thông báo trong ứng
 * dụng luôn nói cùng một câu - chỉ có một nguồn chữ duy nhất.
 */
class PaymentStatusChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Order $order;
    public string $previousStatus;
    public string $newStatus;
    public string $statusLabel;
    public string $customerMessage;

    public function __construct(
        Order $order,
        string $previousStatus,
        string $newStatus,
        string $statusLabel,
        string $customerMessage
    ) {
        $this->order = $order;
        $this->previousStatus = $previousStatus;
        $this->newStatus = $newStatus;
        $this->statusLabel = $statusLabel;
        $this->customerMessage = $customerMessage;
    }

    public function build()
    {
        return $this->subject('[ToyLand] Thanh toán đơn #' . $this->order->id . ' - ' . $this->statusLabel)
            ->view('emails.payment-status');
    }
}
