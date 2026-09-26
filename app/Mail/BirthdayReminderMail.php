<?php

namespace App\Mail;

use App\Models\ChildProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/**
 * Email nhắc bố mẹ trước khi sinh nhật bé tới, kèm vài gợi ý quà.
 *
 * Quà được chọn theo độ tuổi bé SẮP TRÒN (xem GiftFinderService), nên nội dung
 * email không phải lời chào chung chung mà là danh sách món thật, còn hàng,
 * đúng lứa - bấm vào là mua được ngay.
 */
class BirthdayReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public ChildProfile $child;

    public Collection $gifts;

    public function __construct(ChildProfile $child, Collection $gifts)
    {
        $this->child = $child;
        $this->gifts = $gifts;
    }

    public function build()
    {
        $days = $this->child->days_until_birthday;

        $subject = $days === 0
            ? '[ToyLand] Chúc mừng sinh nhật ' . $this->child->name . '! 🎂'
            : '[ToyLand] Còn ' . $days . ' ngày nữa là sinh nhật ' . $this->child->name . ' 🎂';

        return $this->subject($subject)->view('emails.birthday-reminder');
    }
}
