<?php

namespace Database\Seeders;

use App\Models\Coupon;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Dữ liệu mẫu cho các chức năng phụ: FAQ, trang tĩnh, bài viết blog và mã giảm giá.
 * Dùng firstOrCreate/updateOrCreate nên chạy lại seeder nhiều lần không bị trùng.
 */
class ContentSeeder extends Seeder
{
    public function run()
    {
        $this->seedFaqs();
        $this->seedPages();
        $this->seedPosts();
        $this->seedCoupons();
    }

    private function seedFaqs(): void
    {
        $faqs = [
            ['category' => 'Đặt hàng', 'sort_order' => 1, 'question' => 'Tôi đặt hàng như thế nào?', 'answer' => "Bạn chọn sản phẩm, bấm \"Thêm vào giỏ hàng\" rồi vào Giỏ hàng để thanh toán.\nHệ thống yêu cầu đăng nhập và xác thực email trước khi đặt hàng để đảm bảo liên lạc được với bạn."],
            ['category' => 'Đặt hàng', 'sort_order' => 2, 'question' => 'Tôi có thể hủy đơn sau khi đặt không?', 'answer' => "Có. Khi đơn còn ở trạng thái \"Chờ xác nhận\" hoặc \"Đã xác nhận\" và chưa thanh toán online, bạn vào Lịch sử đơn và bấm \"Hủy đơn hàng\".\nSản phẩm sẽ được trả lại kho ngay lập tức."],
            ['category' => 'Thanh toán', 'sort_order' => 1, 'question' => 'ToyLand hỗ trợ những hình thức thanh toán nào?', 'answer' => "ToyLand hỗ trợ 2 hình thức:\n- COD: thanh toán tiền mặt khi nhận hàng.\n- MoMo: ví điện tử/QR, thẻ ATM nội địa, thẻ quốc tế Visa/Master/JCB."],
            ['category' => 'Thanh toán', 'sort_order' => 2, 'question' => 'Thanh toán MoMo thất bại thì phải làm sao?', 'answer' => "Đơn hàng của bạn vẫn được giữ nguyên. Vào Lịch sử đơn, chọn lại hình thức MoMo mong muốn và bấm \"Thanh toán lại bằng MoMo\"."],
            ['category' => 'Vận chuyển', 'sort_order' => 1, 'question' => 'Phí vận chuyển được tính như thế nào?', 'answer' => "Phí vận chuyển được tính tự động qua hệ thống Giao Hàng Nhanh (GHN) dựa trên địa chỉ nhận hàng và khối lượng đơn.\nBạn sẽ thấy phí chính xác ngay ở bước chọn Tỉnh/Quận/Phường trong trang thanh toán."],
            ['category' => 'Vận chuyển', 'sort_order' => 2, 'question' => 'Bao lâu thì tôi nhận được hàng?', 'answer' => "Nội thành 1-2 ngày, các tỉnh thành khác 2-5 ngày làm việc kể từ khi đơn được xác nhận.\nBạn có thể theo dõi từng bước tại trang \"Theo dõi đơn hàng\"."],
            ['category' => 'Đổi trả & Bảo hành', 'sort_order' => 1, 'question' => 'Chính sách đổi trả của ToyLand ra sao?', 'answer' => "Đổi trả trong 7 ngày kể từ khi nhận hàng với sản phẩm còn nguyên tem mác, chưa qua sử dụng và còn đủ hộp/phụ kiện.\nSản phẩm lỗi do nhà sản xuất được đổi mới miễn phí."],
            ['category' => 'Sản phẩm', 'sort_order' => 1, 'question' => 'Đồ chơi ở ToyLand có an toàn cho bé không?', 'answer' => "Toàn bộ sản phẩm đều là hàng chính hãng, có nguồn gốc rõ ràng và đạt chuẩn an toàn cho trẻ em.\nMỗi sản phẩm đều ghi rõ độ tuổi phù hợp và chất liệu để phụ huynh dễ chọn."],
            ['category' => 'Tài khoản', 'sort_order' => 1, 'question' => 'Điểm thưởng và hạng thành viên hoạt động thế nào?', 'answer' => "Mỗi 10.000đ giá trị đơn hàng đã giao thành công được cộng 1 điểm.\nTổng chi tiêu quyết định hạng: Đồng (0%), Bạc từ 1 triệu (2%), Vàng từ 5 triệu (5%), Kim Cương từ 15 triệu (8%).\nƯu đãi của hạng được giảm tự động vào mọi đơn hàng."],
            ['category' => 'Tài khoản', 'sort_order' => 2, 'question' => 'Tôi quên mật khẩu hoặc muốn đăng nhập nhanh hơn?', 'answer' => "Bạn có thể dùng nút \"Đăng nhập bằng Google\" ở trang đăng nhập.\nSau khi vào được tài khoản, vào Hồ sơ > Đổi mật khẩu để đặt mật khẩu mới."],
        ];

        foreach ($faqs as $faq) {
            Faq::firstOrCreate(['question' => $faq['question']], $faq + ['is_active' => true]);
        }
    }

    private function seedPages(): void
    {
        $pages = [
            [
                'slug' => 've-chung-toi',
                'title' => 'Về chúng tôi',
                'sort_order' => 1,
                'content' => "ToyLand là cửa hàng đồ chơi trực tuyến dành cho trẻ em từ 0 đến 14 tuổi.\n\nChúng tôi tin rằng đồ chơi không chỉ để giải trí mà còn là công cụ giúp bé phát triển tư duy, vận động và cảm xúc. Vì vậy mỗi sản phẩm trên ToyLand đều được chọn lọc theo 3 tiêu chí:\n\n1. An toàn - chất liệu đạt chuẩn, không cạnh sắc, không chi tiết nhỏ gây hóc với nhóm tuổi nhỏ.\n2. Chính hãng - nguồn gốc rõ ràng, có hóa đơn và tem nhà phân phối.\n3. Có giá trị phát triển - mỗi món đồ chơi đều gắn với một kỹ năng cụ thể của bé.\n\nToyLand hiện phân phối các thương hiệu LEGO, Bandai, Fisher-Price, Barbie, Hot Wheels, Winwintoys và nhiều thương hiệu Việt Nam uy tín khác.",
            ],
            [
                'slug' => 'chinh-sach-doi-tra',
                'title' => 'Chính sách đổi trả',
                'sort_order' => 2,
                'content' => "1. THỜI GIAN ĐỔI TRẢ\nTrong vòng 7 ngày kể từ ngày nhận hàng.\n\n2. ĐIỀU KIỆN ĐỔI TRẢ\n- Sản phẩm còn nguyên tem mác, chưa qua sử dụng.\n- Còn đầy đủ hộp, phụ kiện và quà tặng kèm (nếu có).\n- Có hóa đơn hoặc mã đơn hàng của ToyLand.\n\n3. TRƯỜNG HỢP ĐƯỢC ĐỔI MỚI MIỄN PHÍ\n- Sản phẩm bị lỗi kỹ thuật từ nhà sản xuất.\n- Giao sai mẫu, sai số lượng so với đơn đặt.\n- Sản phẩm hư hỏng trong quá trình vận chuyển.\n\n4. TRƯỜNG HỢP KHÔNG ÁP DỤNG\n- Sản phẩm đã bóc tem, đã lắp ráp hoặc đã sử dụng.\n- Hư hỏng do lỗi người dùng.\n\n5. CÁCH THỰC HIỆN\nGửi yêu cầu hỗ trợ trong mục \"Yêu cầu hỗ trợ\" kèm mã đơn hàng và ảnh sản phẩm. ToyLand phản hồi trong vòng 24 giờ làm việc.",
            ],
            [
                'slug' => 'chinh-sach-bao-mat',
                'title' => 'Chính sách bảo mật',
                'sort_order' => 3,
                'content' => "ToyLand cam kết bảo vệ thông tin cá nhân của khách hàng.\n\n1. THÔNG TIN THU THẬP\nHọ tên, email, số điện thoại và địa chỉ nhận hàng - chỉ phục vụ việc xử lý và giao đơn hàng.\n\n2. BẢO MẬT THÔNG TIN\n- Mật khẩu được mã hóa một chiều, kể cả quản trị viên cũng không đọc được.\n- Giao dịch thanh toán được xử lý trực tiếp tại cổng MoMo; ToyLand KHÔNG lưu trữ số thẻ của khách hàng.\n- Mọi yêu cầu thanh toán đều được xác thực bằng chữ ký HMAC SHA256.\n\n3. CHIA SẺ THÔNG TIN\nToyLand chỉ chia sẻ địa chỉ và số điện thoại cho đơn vị vận chuyển (GHN) để giao hàng, không bán hoặc trao đổi dữ liệu với bên thứ ba nào khác.\n\n4. QUYỀN CỦA KHÁCH HÀNG\nBạn có thể xem, chỉnh sửa thông tin cá nhân bất cứ lúc nào trong mục Hồ sơ của tôi.",
            ],
            [
                'slug' => 'lien-he',
                'title' => 'Liên hệ',
                'sort_order' => 4,
                'content' => "ToyLand - Thế giới đồ chơi cho bé\n\nHotline: 1900 6868 (8:00 - 21:00 hàng ngày)\nEmail: hotro@toyland.vn\n\nKênh hỗ trợ nhanh nhất:\n- Khung chat trực tuyến ở góc phải màn hình (phản hồi trong giờ làm việc).\n- Mục \"Yêu cầu hỗ trợ\" trong tài khoản - phản hồi trong vòng 24 giờ làm việc và có lưu lại toàn bộ lịch sử trao đổi.\n\nGiờ làm việc: Thứ 2 - Chủ nhật, 8:00 - 21:00.",
            ],
        ];

        foreach ($pages as $page) {
            Page::updateOrCreate(['slug' => $page['slug']], $page + ['is_published' => true, 'show_in_footer' => true]);
        }
    }

    private function seedPosts(): void
    {
        $author = User::where('role', 'admin')->first();

        $posts = [
            [
                'title' => 'Chọn đồ chơi đúng độ tuổi: cẩm nang cho cha mẹ',
                'category' => 'Cẩm nang chọn đồ chơi',
                'excerpt' => 'Đồ chơi quá dễ khiến bé chán, quá khó lại làm bé nản. Đây là cách chọn đúng "vùng phát triển gần" của con.',
                'content' => "Mỗi giai đoạn, bé cần một loại kích thích khác nhau. Chọn sai độ tuổi, món đồ chơi đắt tiền vẫn sẽ nằm im trong góc nhà.\n\n0-12 THÁNG: GIÁC QUAN\nBé học bằng mắt, tai và tay. Ưu tiên thảm chơi có gọng treo, xúc xắc, đồ gặm nướu silicone. Màu sắc tương phản mạnh giúp bé luyện thị giác.\n\n1-3 TUỔI: VẬN ĐỘNG VÀ NHÂN QUẢ\nBé thích thứ gì \"làm A thì xảy ra B\". Xe chòi chân, khối xếp chồng, đồ chơi thả hình rất hợp. Tránh chi tiết nhỏ hơn 3cm vì bé hay cho vào miệng.\n\n3-6 TUỔI: TƯỞNG TƯỢNG VÀ NGÔN NGỮ\nGiai đoạn chơi đóng vai. Búp bê, nhà búp bê, bộ đồ hàng, bảng chữ cái nam châm giúp bé mở rộng vốn từ rất nhanh.\n\n6-12 TUỔI: TƯ DUY LOGIC\nBé đã đủ kiên nhẫn cho các bộ lắp ráp nhiều chi tiết, mô hình gỗ 3D, bộ thí nghiệm khoa học và robot lập trình.\n\nMỘT MẸO NHỎ\nChọn món đồ chơi khó hơn khả năng hiện tại của bé khoảng một bậc - đủ thử thách để bé thấy thú vị, nhưng vẫn làm được khi có chút trợ giúp từ cha mẹ.",
            ],
            [
                'title' => '5 dấu hiệu nhận biết đồ chơi an toàn cho trẻ',
                'category' => 'Cẩm nang chọn đồ chơi',
                'excerpt' => 'Không phải món đồ chơi nào nhìn dễ thương cũng an toàn. Kiểm tra 5 điểm này trước khi mua.',
                'content' => "1. CHẤT LIỆU CÓ GHI RÕ RÀNG\nNhựa ABS, PP, silicone y tế hay gỗ tự nhiên đều an toàn. Hãy cảnh giác với sản phẩm không ghi chất liệu hoặc có mùi nhựa hắc.\n\n2. KHÔNG CÓ CHI TIẾT NHỎ VỚI BÉ DƯỚI 3 TUỔI\nQuy tắc đơn giản: chi tiết nào lọt qua lõi giấy vệ sinh thì không an toàn với bé dưới 3 tuổi.\n\n3. KHÔNG CÓ CẠNH SẮC, GÓC NHỌN\nHãy sờ thử toàn bộ bề mặt. Đồ chơi tốt luôn được bo tròn và mài nhẵn các cạnh.\n\n4. SƠN VÀ MÀU KHÔNG BONG TRÓC\nDùng móng tay cào nhẹ. Sơn bong ra dễ dàng là dấu hiệu sơn kém chất lượng, có nguy cơ chứa chì.\n\n5. CÓ TEM NHÃN VÀ NGUỒN GỐC\nSản phẩm chính hãng luôn có tem nhà phân phối, hướng dẫn sử dụng và khuyến cáo độ tuổi.\n\nTại ToyLand, mọi sản phẩm đều ghi rõ độ tuổi phù hợp, chất liệu và xuất xứ ngay trên trang sản phẩm để cha mẹ dễ đối chiếu.",
            ],
            [
                'title' => 'Chơi cùng con 20 phút mỗi ngày: ít mà chất',
                'category' => 'Chơi cùng con',
                'excerpt' => 'Không cần cả buổi tối. 20 phút thật sự tập trung có giá trị hơn 2 tiếng ngồi cạnh con mà mắt dán vào điện thoại.',
                'content' => "ĐỂ ĐIỆN THOẠI RA XA\nTrẻ nhận ra rất nhanh khi cha mẹ chỉ \"có mặt\" chứ không thật sự chơi cùng. 20 phút không điện thoại có giá trị hơn nhiều so với 2 tiếng chia trí.\n\nĐỂ CON DẪN DẮT\nHãy hỏi \"tiếp theo mình làm gì?\" thay vì hướng dẫn con phải xếp thế nào. Việc tự quyết định giúp bé rèn khả năng lập kế hoạch.\n\nMÔ TẢ THAY VÌ KHEN CHUNG CHUNG\nThay vì \"con giỏi quá\", hãy nói \"con đã thử ba cách khác nhau mới xếp được cái tháp này\". Cách khen vào nỗ lực giúp bé bền bỉ hơn khi gặp việc khó.\n\nCHẤP NHẬN SỰ LỘN XỘN\nĐồ chơi bày khắp sàn là dấu hiệu bé đang chơi thật sự. Hãy biến việc dọn dẹp thành một trò chơi kết thúc buổi chơi thay vì một mệnh lệnh.\n\nLẶP LẠI LÀ BÌNH THƯỜNG\nBé có thể muốn chơi đúng một trò suốt hai tuần. Đó là cách não bộ củng cố kỹ năng, không phải dấu hiệu bé thiếu sáng tạo.",
            ],
        ];

        foreach ($posts as $post) {
            Post::updateOrCreate(
                ['slug' => Str::slug($post['title'])],
                $post + [
                    'slug' => Str::slug($post['title']),
                    'author_id' => $author->id ?? null,
                    'is_published' => true,
                    'published_at' => now()->subDays(rand(1, 20)),
                ]
            );
        }
    }

    private function seedCoupons(): void
    {
        $coupons = [
            [
                'code' => 'TOYLAND10',
                'description' => 'Giảm 10% cho đơn từ 500.000đ, tối đa 100.000đ',
                'type' => 'percent', 'value' => 10,
                'min_order_amount' => 500000, 'max_discount' => 100000,
                'usage_limit' => 200, 'usage_limit_per_user' => 1,
                'expires_at' => now()->addMonths(3),
            ],
            [
                'code' => 'FREESHIP50',
                'description' => 'Giảm thẳng 50.000đ cho đơn từ 300.000đ',
                'type' => 'fixed', 'value' => 50000,
                'min_order_amount' => 300000, 'max_discount' => null,
                'usage_limit' => null, 'usage_limit_per_user' => 3,
                'expires_at' => now()->addMonths(6),
            ],
            [
                'code' => 'CHAOBE',
                'description' => 'Ưu đãi khách hàng mới - giảm 15% đơn đầu tiên, tối đa 150.000đ',
                'type' => 'percent', 'value' => 15,
                'min_order_amount' => 200000, 'max_discount' => 150000,
                'usage_limit' => null, 'usage_limit_per_user' => 1,
                'expires_at' => now()->addYear(),
            ],
        ];

        foreach ($coupons as $coupon) {
            Coupon::updateOrCreate(
                ['code' => $coupon['code']],
                $coupon + ['is_active' => true, 'starts_at' => now()->subDay()]
            );
        }
    }
}
