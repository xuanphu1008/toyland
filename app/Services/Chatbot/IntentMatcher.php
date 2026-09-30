<?php

namespace App\Services\Chatbot;

/**
 * Nhận diện Ý ĐỊNH (intent) của khách.
 *
 * CÁCH HOẠT ĐỘNG - chấm điểm theo từ khóa có trọng số:
 *   1. Mỗi ý định khai báo 3 nhóm từ khóa với trọng số khác nhau:
 *        strong (3 điểm) - gần như chỉ xuất hiện ở ý định này ("tra cứu đơn hàng")
 *        normal (2 điểm) - khá đặc trưng ("giao hàng")
 *        weak   (1 điểm) - có thể xuất hiện ở nhiều ý định ("bao lâu")
 *   2. Cộng điểm, chia cho điểm tối đa có thể đạt -> độ tin cậy 0..1
 *   3. Ý định điểm cao nhất thắng; dưới ngưỡng thì trả về 'unknown'
 *
 * VÌ SAO KHÔNG DÙNG "CHỨA TỪ KHÓA LÀ ĂN NGAY"?
 * Câu "đơn hàng của tôi có được đổi trả không" chứa cả "đơn hàng" lẫn "đổi trả".
 * Nếu duyệt tuần tự và khớp cái đầu tiên thì kết quả phụ thuộc vào THỨ TỰ khai báo
 * chứ không phải vào câu hỏi. Chấm điểm rồi so sánh mới chọn đúng được.
 */
class IntentMatcher
{
    /** Dưới ngưỡng này coi như không hiểu -> chuyển sang khớp FAQ hoặc LLM */
    public const CONFIDENCE_THRESHOLD = 0.34;

    /**
     * Bảng tri thức về ý định.
     * Thứ tự khai báo KHÔNG ảnh hưởng kết quả vì đã chấm điểm toàn bộ.
     */
    private const INTENTS = [
        'greeting' => [
            'strong' => ['xin chao', 'chao shop', 'chao ban', 'hello', 'hi shop'],
            'normal' => ['chao', 'alo'],
            'weak' => ['hi', 'hey'],
        ],

        'thanks' => [
            'strong' => ['cam on', 'thanks', 'thank you', 'cam on ban', 'cam on shop'],
            'normal' => ['tks', 'thanks nhe'],
            'weak' => [],
        ],

        'order_status' => [
            'strong' => ['tra cuu don hang', 'don hang toi dau', 'kiem tra don hang', 'trang thai don hang', 'don hang cua toi'],
            'normal' => ['don hang', 'ma don', 'theo doi don', 'don da giao chua',
                         'khi nao nhan duoc', 'nhan duoc hang', 'nhan hang', 'van don'],
            'weak' => ['don', 'giao chua', 'toi dau', 'khi nao'],
        ],

        'order_list' => [
            'strong' => ['lich su don hang', 'cac don toi da dat', 'toi da mua gi', 'xem lai don hang', 'don hang da dat'],
            'normal' => ['danh sach don', 'nhung don da mua', 'don cu'],
            'weak' => [],
        ],

        // Nhóm từ khóa rộng nhất, vì khách hỏi mua hàng bằng vô số cách diễn đạt.
        // Ngoài từ khóa, ChatbotService còn dùng THỰC THỂ bóc tách được (tuổi, giá,
        // giới tính, hãng...) làm bằng chứng bổ sung - xem boostWithEntities().
        'product_search' => [
            'strong' => ['tim do choi', 'co do choi nao', 'goi y do choi', 'tim qua', 'muon mua do choi',
                         'tim mon hang', 'tim san pham', 'tim mon do', 'goi y mon', 'goi y san pham',
                         'nen mua gi', 'mua gi cho be', 'co gi cho be'],
            'normal' => ['do choi', 'san pham', 'mua qua', 'qua sinh nhat', 'qua tang', 'con gi', 'co ban',
                         'mon hang', 'mon do', 'mon nao', 'choi gi', 'phu hop voi be', 'danh cho be'],
            'weak' => ['tim', 'mua', 'qua', 'choi', 'cho be', 'cho tre', 'be gai', 'be trai'],
        ],

        'bestseller' => [
            'strong' => ['ban chay nhat', 'mon nao ban chay', 'hot nhat', 'nhieu nguoi mua nhat'],
            'normal' => ['ban chay', 'pho bien', 'duoc ua chuong'],
            'weak' => ['hot'],
        ],

        'shipping' => [
            'strong' => ['phi van chuyen', 'phi ship', 'phi giao hang', 'tien ship', 'bao lau thi nhan duoc hang'],
            'normal' => ['van chuyen', 'giao hang', 'ship', 'giao toi', 'freeship', 'mien phi ship'],
            'weak' => ['bao lau', 'may ngay'],
        ],

        'payment' => [
            'strong' => ['phuong thuc thanh toan', 'thanh toan the nao', 'tra tien kieu gi', 'co momo khong', 'thanh toan online'],
            'normal' => ['thanh toan', 'momo', 'cod', 'chuyen khoan', 'the atm', 'tra tien', 'vi dien tu'],
            'weak' => ['tien', 'quet ma', 'visa'],
        ],

        'return_policy' => [
            'strong' => ['chinh sach doi tra', 'doi tra hang', 'tra lai hang', 'hoan tien'],
            'normal' => ['doi tra', 'bao hanh', 'hang loi', 'doi hang'],
            'weak' => ['doi', 'tra'],
        ],

        'coupon' => [
            'strong' => ['ma giam gia', 'co khuyen mai gi', 'voucher', 'ma giam gia nao dang chay'],
            'normal' => ['giam gia', 'khuyen mai', 'uu dai', 'coupon', 'sale'],
            'weak' => ['giam', 'ma'],
        ],

        'loyalty' => [
            'strong' => ['diem thuong', 'hang thanh vien', 'tich diem', 'thanh vien vip'],
            'normal' => ['diem tich luy', 'uu dai thanh vien', 'len hang', 'hang dong', 'hang bac', 'hang vang'],
            'weak' => ['diem'],
        ],

        'store_info' => [
            'strong' => ['gio mo cua', 'dia chi cua hang', 'so dien thoai shop', 'lien he shop', 'shop o dau'],
            'normal' => ['lien he', 'hotline', 'dia chi', 'email shop', 'so dien thoai'],
            'weak' => ['o dau', 'gio lam viec'],
        ],

        'safety' => [
            'strong' => ['co an toan khong', 'an toan cho be', 'chat lieu co an toan', 'hang chinh hang khong', 'do choi co an toan'],
            'normal' => ['an toan', 'chinh hang', 'chat lieu', 'hang that', 'chung nhan', 'tieu chuan'],
            'weak' => ['that gia', 'nguon goc', 'xuat xu'],
        ],

        // Hỏi quà cho CHÍNH CON MÌNH - khác product_search ở chỗ bot sẽ tra hồ sơ
        // bé đã lưu thay vì bắt khách mô tả lại tuổi, giới tính, sở thích.
        'child_gift' => [
            'strong' => ['qua sinh nhat cho be', 'qua sinh nhat cho con', 'goi y qua cho con',
                         'be nha minh sap sinh nhat', 'sinh nhat con toi', 'ho so cua be', 'so sinh nhat'],
            'normal' => ['sinh nhat be', 'sinh nhat con', 'qua cho con', 'con toi', 'be nha minh',
                         'con nha minh', 'bao gio sinh nhat'],
            'weak' => ['sinh nhat'],
        ],

        'human_handoff' => [
            'strong' => ['gap nhan vien', 'noi chuyen voi nguoi that', 'tu van vien', 'gap admin', 'chat voi nhan vien'],
            'normal' => ['nhan vien', 'nguoi that', 'tu van truc tiep'],
            'weak' => ['tu van'],
        ],
    ];

    /** Điểm cho từng nhóm từ khóa */
    private const WEIGHTS = ['strong' => 3, 'normal' => 2, 'weak' => 1];

    /**
     * Mốc bão hòa: đạt tới đây là chắc chắn 100%.
     * 4 điểm = một từ khóa strong (3) + một weak (1), hoặc hai normal (2+2).
     * KHÔNG chia cho tổng điểm tối đa của cả 3 nhóm, vì như thế một câu khớp
     * đúng một từ khóa strong vẫn chỉ được 3/12 = 0.25 -> bị coi là không hiểu.
     */
    private const SATURATION = 4;

    /**
     * @return array{intent: string, confidence: float, scores: array}
     */
    public function match(string $message): array
    {
        $scores = [];
        $longest = [];   // độ dài từ khóa dài nhất khớp được - dùng để phá thế hòa

        foreach (self::INTENTS as $intent => $groups) {
            $score = 0;
            $longest[$intent] = 0;

            foreach (self::WEIGHTS as $group => $weight) {
                foreach ($groups[$group] ?? [] as $keyword) {
                    if (TextAnalyzer::containsAny($message, [$keyword])) {
                        $score += $weight;
                        $longest[$intent] = max($longest[$intent], mb_strlen($keyword));
                    }
                }
            }

            $scores[$intent] = round(min(1.0, $score / self::SATURATION), 4);
        }

        // Sắp xếp: điểm cao trước; bằng điểm thì CỤM TỪ KHỚP DÀI HƠN thắng.
        // "lịch sử đơn hàng" (order_list) phải thắng "đơn hàng" (order_status)
        // vì nó cụ thể hơn, chứ không phụ thuộc thứ tự khai báo trong mảng.
        uksort($scores, function ($a, $b) use ($scores, $longest) {
            return [$scores[$b], $longest[$b]] <=> [$scores[$a], $longest[$a]];
        });

        $best = array_key_first($scores);
        $confidence = $scores[$best];

        return [
            'intent' => $confidence >= self::CONFIDENCE_THRESHOLD ? $best : 'unknown',
            'confidence' => (float) $confidence,
            // Giữ 3 ứng viên hàng đầu để trang quản trị xem bot đã "phân vân" giữa những gì
            'scores' => array_slice($scores, 0, 3, true),
        ];
    }

    /** Danh sách mã ý định - dùng cho thống kê ở trang quản trị */
    public static function allIntents(): array
    {
        return array_keys(self::INTENTS);
    }

    /** Nhãn tiếng Việt để hiển thị trong trang quản trị */
    public static function label(string $intent): string
    {
        return [
            'greeting' => 'Chào hỏi',
            'thanks' => 'Cảm ơn',
            'order_status' => 'Tra cứu đơn hàng',
            'order_list' => 'Lịch sử đơn hàng',
            'product_search' => 'Tìm sản phẩm',
            'bestseller' => 'Hàng bán chạy',
            'shipping' => 'Vận chuyển',
            'payment' => 'Thanh toán',
            'return_policy' => 'Đổi trả - bảo hành',
            'coupon' => 'Mã giảm giá',
            'loyalty' => 'Điểm thưởng',
            'store_info' => 'Thông tin cửa hàng',
            'safety' => 'An toàn sản phẩm',
            'child_gift' => 'Quà cho bé nhà mình',
            'human_handoff' => 'Xin gặp nhân viên',
            'faq' => 'Khớp câu hỏi thường gặp',
            'llm' => 'Trả lời bằng AI',
            'off_topic' => 'Ngoài phạm vi (không gọi AI)',
            'llm_busy' => 'AI tạm hết lượt',
            'unknown' => 'Không hiểu',
        ][$intent] ?? $intent;
    }
}
