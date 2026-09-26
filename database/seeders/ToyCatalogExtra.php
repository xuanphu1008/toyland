<?php

namespace Database\Seeders;

/**
 * KHO DỮ LIỆU MẪU MỞ RỘNG
 *
 * Tách riêng khỏi ToyCatalogSeeder vì lý do thuần thực dụng: seeder gốc lo phần
 * LOGIC (dựng cây danh mục, ghi sản phẩm, chống trùng), file này chỉ là DỮ LIỆU.
 * Trộn chung thì một file gần ba nghìn dòng, sửa một dòng giá cũng phải cuộn mãi.
 *
 * Mỗi danh mục con được nâng lên khoảng 8-9 món để bộ lọc (hãng, độ tuổi, nhân
 * vật, khoảng giá) có đủ dữ liệu mà hoạt động - lọc trên 2 món thì không thấy
 * được tác dụng của bộ lọc.
 *
 * Định dạng một dòng giống hệt seeder gốc, bắt buộc có:
 *   category, name, brand, price, qty, age_min, age_max, desc
 * Tùy chọn: character, gender, material, origin, pieces, battery, warranty,
 *           dims, weight, skills, safety, featured
 *
 * Tên sản phẩm là KHÓA chống trùng, nên đặt tên đủ cụ thể (kèm mã bộ, phiên bản).
 */
class ToyCatalogExtra
{
    public static function all(): array
    {
        return array_merge(
            self::blocks(),
            self::vehicles(),
            self::bikes(),
            self::movies(),
            self::dolls(),
            self::robots(),
            self::animals(),
            self::study(),
            self::baby(),
            self::outdoor(),
            self::boardGames()
        );
    }

    // ===================== 1. ĐỒ CHƠI LẮP GHÉP =====================

    private static function blocks(): array
    {
        return [
            // ---- LEGO & Xếp Hình ----
            ['category' => 'LEGO & Xếp Hình', 'name' => 'LEGO City Xe Cứu Hỏa Cứu Hộ 60374', 'brand' => 'LEGO', 'price' => 1450000, 'qty' => 18,
             'age_min' => 72, 'age_max' => 144, 'origin' => 'Đan Mạch', 'pieces' => 502, 'weight' => 980, 'skills' => 'Tư duy logic, Vận động tinh',
             'desc' => "Xe cứu hỏa thang nâng xoay được 360 độ, kèm vòi phun và 2 lính cứu hỏa mini.\nCabin mở ra cho bé bày trí bên trong, bánh xe lăn êm trên sàn nhà."],

            ['category' => 'LEGO & Xếp Hình', 'name' => 'LEGO Friends Nhà Trên Cây 41703', 'brand' => 'LEGO', 'price' => 1690000, 'qty' => 14,
             'age_min' => 96, 'age_max' => 168, 'origin' => 'Đan Mạch', 'pieces' => 1114, 'weight' => 1600, 'gender' => 'girl',
             'skills' => 'Sáng tạo, Tập trung & kiên nhẫn', 'featured' => true,
             'desc' => "Nhà trên cây ba tầng có cầu trượt, xích đu và góc cắm trại nhỏ.\nBộ lớn 1114 chi tiết, bé cần vài buổi mới lắp xong - rèn tính kiên nhẫn rất tốt."],

            ['category' => 'LEGO & Xếp Hình', 'name' => 'LEGO Duplo Hộp Gạch Lớn 10913', 'brand' => 'LEGO', 'price' => 890000, 'qty' => 26,
             'age_min' => 18, 'age_max' => 60, 'origin' => 'Đan Mạch', 'pieces' => 65, 'weight' => 1200, 'safety' => 'Viên gạch cỡ lớn, an toàn cho bé dưới 3 tuổi.',
             'skills' => 'Vận động tinh, Sáng tạo',
             'desc' => "Gạch Duplo to gấp đôi gạch LEGO thường nên bé nhỏ cầm chắc tay và không nuốt được.\nHộp nhựa có nắp, dọn dẹp sau khi chơi rất nhanh."],

            ['category' => 'LEGO & Xếp Hình', 'name' => 'Bộ Xếp Hình Nam Châm Magformers 62 Chi Tiết', 'brand' => 'Magformers', 'price' => 1350000, 'qty' => 12,
             'age_min' => 36, 'age_max' => 120, 'material' => 'Nhựa ABS + nam châm neodymium', 'origin' => 'Hàn Quốc', 'pieces' => 62, 'weight' => 900,
             'skills' => 'Tư duy logic, Toán học sớm, Sáng tạo',
             'desc' => "Các mảnh hình học hút nhau bằng nam châm, gập lên thành khối 3D chỉ trong một giây.\nCách trực quan nhất để bé hiểu hình vuông ghép thành hình lập phương."],

            ['category' => 'LEGO & Xếp Hình', 'name' => 'Bộ Gạch Xếp Hình Mega Bloks 80 Viên', 'brand' => 'Mega Bloks', 'price' => 420000, 'qty' => 35,
             'age_min' => 12, 'age_max' => 60, 'pieces' => 80, 'weight' => 1000, 'safety' => 'Viên gạch cỡ đại, an toàn cho bé từ 1 tuổi.',
             'skills' => 'Vận động tinh, Sáng tạo',
             'desc' => "80 viên gạch nhiều màu, cỡ đại vừa bàn tay bé tập đi.\nTúi vải kéo dây đi kèm, mang theo khi về quê hay đi du lịch rất gọn."],

            ['category' => 'LEGO & Xếp Hình', 'name' => 'LEGO Technic Xe Địa Hình 42139', 'brand' => 'LEGO', 'price' => 1590000, 'qty' => 10,
             'age_min' => 120, 'age_max' => null, 'origin' => 'Đan Mạch', 'pieces' => 764, 'weight' => 1300,
             'skills' => 'Tư duy logic, Tập trung & kiên nhẫn',
             'desc' => "Dòng Technic dùng trục và bánh răng thật, xe có hệ thống treo nhún được.\nDành cho bé lớn đã chán lắp ghép thông thường và muốn hiểu cơ cấu máy móc."],

            // ---- Mô Hình Lắp Ráp ----
            ['category' => 'Mô Hình Lắp Ráp', 'name' => 'Mô Hình Gundam RG 1/144 Nu Gundam', 'brand' => 'Bandai', 'price' => 980000, 'qty' => 15,
             'age_min' => 144, 'age_max' => null, 'origin' => 'Nhật Bản', 'character' => 'Gundam', 'pieces' => 280, 'weight' => 400,
             'skills' => 'Vận động tinh, Tập trung & kiên nhẫn', 'featured' => true,
             'desc' => "Dòng Real Grade tỉ lệ 1/144, khớp cử động linh hoạt và chi tiết sắc nét.\nLắp bằng tay không cần keo, nhưng cần nhíp cắt chân nhựa nên hợp với bé từ 12 tuổi."],

            ['category' => 'Mô Hình Lắp Ráp', 'name' => 'Mô Hình Gundam HG 1/144 Exia', 'brand' => 'Bandai', 'price' => 520000, 'qty' => 22,
             'age_min' => 96, 'age_max' => null, 'origin' => 'Nhật Bản', 'character' => 'Gundam', 'pieces' => 150, 'weight' => 280,
             'skills' => 'Vận động tinh, Tập trung & kiên nhẫn',
             'desc' => "Bản Exia dòng High Grade dễ lắp, ít chi tiết hơn Real Grade nên hợp cho bạn mới bắt đầu.\nGiá vừa phải, là lựa chọn quen thuộc cho mô hình Gundam đầu tiên."],

            ['category' => 'Mô Hình Lắp Ráp', 'name' => 'Mô Hình Máy Bay Chiến Đấu SU-27 Tỉ Lệ 1/72', 'brand' => 'Trumpeter', 'price' => 450000, 'qty' => 16,
             'age_min' => 144, 'age_max' => null, 'origin' => 'Trung Quốc', 'pieces' => 120, 'weight' => 350,
             'skills' => 'Tập trung & kiên nhẫn',
             'desc' => "Mô hình tĩnh cần sơn và dán keo, dành cho bạn đã quen chơi mô hình.\nHộp kèm decal nước và sơ đồ màu chuẩn của máy bay thật."],

            ['category' => 'Mô Hình Lắp Ráp', 'name' => 'Mô Hình Tàu Chiến Cổ Gỗ Cutty Sark', 'brand' => 'RoboTime', 'price' => 690000, 'qty' => 9,
             'age_min' => 144, 'age_max' => null, 'material' => 'Gỗ bạch dương cắt laser', 'pieces' => 210, 'weight' => 700,
             'skills' => 'Tập trung & kiên nhẫn, Vận động tinh',
             'desc' => "Thân tàu ghép từ các lát gỗ cắt laser, buồm bằng vải thật.\nLắp xong cao khoảng 40 cm, trưng trên kệ sách rất đẹp."],

            ['category' => 'Mô Hình Lắp Ráp', 'name' => 'Mô Hình Xe Ferrari F40 Lắp Ráp 1/24', 'brand' => 'Tamiya', 'price' => 780000, 'qty' => 11,
             'age_min' => 144, 'age_max' => null, 'origin' => 'Nhật Bản', 'pieces' => 95, 'weight' => 420,
             'skills' => 'Tập trung & kiên nhẫn',
             'desc' => "Bản sao tỉ lệ 1/24 của chiếc F40 huyền thoại, capo mở ra thấy nguyên khối động cơ.\nNhựa đã nhuộm sẵn màu đỏ đặc trưng, không sơn vẫn đẹp."],

            ['category' => 'Mô Hình Lắp Ráp', 'name' => 'Bộ Lắp Ráp Mô Hình Kiến Trúc Chùa Một Cột', 'brand' => 'ToyLand', 'price' => 320000, 'qty' => 20,
             'age_min' => 96, 'age_max' => null, 'material' => 'Gỗ ép cắt laser', 'origin' => 'Việt Nam', 'pieces' => 86, 'weight' => 300,
             'skills' => 'Tập trung & kiên nhẫn, Sáng tạo',
             'desc' => "Mô hình một công trình quen thuộc của Hà Nội, các mảnh gỗ ghép mộng không cần keo.\nKèm tờ giới thiệu ngắn về lịch sử ngôi chùa."],

            // ---- Lắp Ghép Gỗ 3D ----
            ['category' => 'Lắp Ghép Gỗ 3D', 'name' => 'Mô Hình Gỗ 3D Đồng Hồ Quả Lắc Cơ Học', 'brand' => 'RoboTime', 'price' => 850000, 'qty' => 13,
             'age_min' => 168, 'age_max' => null, 'material' => 'Gỗ bạch dương cắt laser', 'pieces' => 166, 'weight' => 800,
             'skills' => 'Tư duy logic, Tập trung & kiên nhẫn', 'featured' => true,
             'desc' => "Lắp xong là một chiếc đồng hồ chạy THẬT bằng dây cót, không cần pin.\nBánh răng gỗ ăn khớp nhau, bé nhìn thấy rõ cách một cỗ máy hoạt động."],

            ['category' => 'Lắp Ghép Gỗ 3D', 'name' => 'Mô Hình Gỗ 3D Hộp Nhạc Ngôi Nhà Cổ Tích', 'brand' => 'RoboTime', 'price' => 620000, 'qty' => 17,
             'age_min' => 120, 'age_max' => null, 'material' => 'Gỗ bạch dương cắt laser', 'pieces' => 132, 'weight' => 600,
             'skills' => 'Vận động tinh, Tập trung & kiên nhẫn',
             'desc' => "Vặn cót là ngôi nhà xoay tròn theo tiếng nhạc leng keng.\nLà món quà sinh nhật được các bé gái lớn thích vì vừa chơi vừa trưng được."],

            ['category' => 'Lắp Ghép Gỗ 3D', 'name' => 'Bộ Ghép Hình Gỗ Khủng Long T-Rex 3D', 'brand' => 'ToyLand', 'price' => 180000, 'qty' => 40,
             'age_min' => 60, 'age_max' => 120, 'material' => 'Gỗ ép an toàn', 'pieces' => 38, 'weight' => 200,
             'skills' => 'Tư duy logic, Vận động tinh',
             'desc' => "38 mảnh gỗ ghép thành bộ xương khủng long bạo chúa cao 25 cm.\nCác mảnh đã mài nhẵn cạnh, bé tự ghép được trong khoảng 20 phút."],

            ['category' => 'Lắp Ghép Gỗ 3D', 'name' => 'Mô Hình Gỗ 3D Xe Cần Cẩu Cơ Khí', 'brand' => 'UGears', 'price' => 790000, 'qty' => 10,
             'age_min' => 168, 'age_max' => null, 'material' => 'Gỗ bạch dương cắt laser', 'origin' => 'Ukraina', 'pieces' => 188, 'weight' => 750,
             'skills' => 'Tư duy logic, Tập trung & kiên nhẫn',
             'desc' => "Cần cẩu nâng hạ được bằng tay quay, hoàn toàn bằng cơ khí gỗ.\nKhông dùng keo hay đinh, các mảnh khớp vào nhau bằng mộng."],

            ['category' => 'Lắp Ghép Gỗ 3D', 'name' => 'Bộ Xếp Hình Gỗ Tháp Rùa Hồ Gươm', 'brand' => 'ToyLand', 'price' => 250000, 'qty' => 24,
             'age_min' => 84, 'age_max' => null, 'material' => 'Gỗ ép cắt laser', 'origin' => 'Việt Nam', 'pieces' => 64, 'weight' => 280,
             'skills' => 'Tập trung & kiên nhẫn',
             'desc' => "Tháp Rùa thu nhỏ ghép từ 64 mảnh gỗ, có đế trưng bày kèm theo.\nMón quà lưu niệm mang màu sắc Việt Nam cho bé và cả người lớn."],

            ['category' => 'Lắp Ghép Gỗ 3D', 'name' => 'Mô Hình Gỗ 3D Đàn Piano Mini Có Tiếng', 'brand' => 'RoboTime', 'price' => 720000, 'qty' => 12,
             'age_min' => 144, 'age_max' => null, 'material' => 'Gỗ bạch dương cắt laser', 'pieces' => 148, 'weight' => 650,
             'skills' => 'Vận động tinh, Tập trung & kiên nhẫn',
             'desc' => "Nắp đàn mở ra, phím đàn bấm xuống và phát ra tiếng nhạc thật.\nLắp mất khoảng 4-6 tiếng, là một buổi chiều cuối tuần trọn vẹn."],

            // ---- Đất Nặn & Sáng Tạo ----
            ['category' => 'Đất Nặn & Sáng Tạo', 'name' => 'Bộ Đất Nặn Play-Doh Tiệm Bánh Kem', 'brand' => 'Play-Doh', 'price' => 390000, 'qty' => 30,
             'age_min' => 36, 'age_max' => 96, 'material' => 'Bột nặn gốc thực phẩm', 'origin' => 'Hoa Kỳ', 'weight' => 700,
             'skills' => 'Vận động tinh, Sáng tạo', 'featured' => true,
             'desc' => "Khuôn bánh, máy ép kem và 5 hộp bột nặn nhiều màu.\nBột làm từ bột mì và muối, bé lỡ cho vào miệng cũng không độc - nhưng rất mặn nên bé sẽ tự nhả ra."],

            ['category' => 'Đất Nặn & Sáng Tạo', 'name' => 'Bộ Đất Nặn Play-Doh Nha Sĩ Vui Nhộn', 'brand' => 'Play-Doh', 'price' => 350000, 'qty' => 28,
             'age_min' => 36, 'age_max' => 84, 'material' => 'Bột nặn gốc thực phẩm', 'origin' => 'Hoa Kỳ', 'weight' => 600,
             'skills' => 'Vận động tinh, Kỹ năng xã hội',
             'desc' => "Bé đóng vai nha sĩ, dùng khoan đồ chơi và kẹp để chữa răng cho chú hề.\nRất hữu ích để bé bớt sợ khi bố mẹ đưa đi khám răng thật."],

            ['category' => 'Đất Nặn & Sáng Tạo', 'name' => 'Bộ Slime Tự Làm 12 Màu Kèm Hạt Trang Trí', 'brand' => 'ToyLand', 'price' => 220000, 'qty' => 45,
             'age_min' => 72, 'age_max' => 168, 'material' => 'Gel PVA không borax', 'weight' => 800,
             'skills' => 'Sáng tạo, Vận động tinh',
             'desc' => "12 hũ slime nhiều màu kèm hạt xốp, kim tuyến và charm nhựa để trộn.\nCông thức không borax nên không gây rát tay như slime trôi nổi."],

            ['category' => 'Đất Nặn & Sáng Tạo', 'name' => 'Bộ Đất Sét Nhẹ Nặn Thú Cưng 24 Màu', 'brand' => 'ToyLand', 'price' => 165000, 'qty' => 50,
             'age_min' => 48, 'age_max' => 144, 'material' => 'Đất sét siêu nhẹ', 'weight' => 500,
             'skills' => 'Sáng tạo, Vận động tinh',
             'desc' => "Đất sét siêu nhẹ, để ngoài không khí một ngày là khô cứng và giữ được lâu.\nKèm dụng cụ tạo hình và sách mẫu 20 con vật đơn giản."],

            ['category' => 'Đất Nặn & Sáng Tạo', 'name' => 'Bàn Cát Động Lực Kinetic Sand 1kg', 'brand' => 'Kinetic Sand', 'price' => 480000, 'qty' => 22,
             'age_min' => 36, 'age_max' => 120, 'material' => 'Cát động lực phủ silicone', 'origin' => 'Thụy Điển', 'weight' => 1200,
             'skills' => 'Vận động tinh, Sáng tạo',
             'desc' => "Cát dính vào nhau chứ không dính tay, đổ ra bàn không bay bụi.\nBóp nắm rồi thả cho cát chảy - trò này khiến bé ngồi yên được rất lâu."],

            ['category' => 'Đất Nặn & Sáng Tạo', 'name' => 'Bộ Làm Vòng Tay Hạt Cườm 1000 Hạt', 'brand' => 'ToyLand', 'price' => 190000, 'qty' => 38,
             'age_min' => 60, 'age_max' => 168, 'gender' => 'girl', 'weight' => 450,
             'skills' => 'Vận động tinh, Sáng tạo', 'safety' => 'Hạt nhỏ, không dành cho bé dưới 3 tuổi.',
             'desc' => "1000 hạt cườm nhiều hình dạng, dây thun co giãn và khóa cài kim loại.\nBé tự làm vòng tặng bạn - vừa khéo tay vừa là cái cớ để kết bạn."],
        ];
    }

    // ===================== 2. ĐỒ CHƠI PHƯƠNG TIỆN =====================

    private static function vehicles(): array
    {
        return [
            // ---- Xe Điều Khiển Từ Xa ----
            ['category' => 'Xe Điều Khiển Từ Xa', 'name' => 'Xe Điều Khiển Địa Hình Leo Dốc 4WD 1:16', 'brand' => 'Wltoys', 'price' => 890000, 'qty' => 20,
             'age_min' => 72, 'age_max' => null, 'battery' => 'Pin sạc Li-ion 7.4V, chơi 25 phút', 'warranty' => 6, 'weight' => 900,
             'dims' => '32 x 22 x 15 cm', 'skills' => 'Vận động thô, Tập trung & kiên nhẫn', 'featured' => true,
             'desc' => "Bốn bánh chủ động, lốp gai cao su leo được dốc 45 độ và chạy tốt trên cỏ, cát.\nSạc một lần chơi khoảng 25 phút, sóng 2.4GHz nên nhiều xe chơi cùng lúc không nhiễu."],

            ['category' => 'Xe Điều Khiển Từ Xa', 'name' => 'Xe Đua Điều Khiển Tốc Độ Cao 1:18', 'brand' => 'Wltoys', 'price' => 650000, 'qty' => 25,
             'age_min' => 72, 'age_max' => null, 'battery' => 'Pin sạc 3.7V, chơi 20 phút', 'warranty' => 6, 'weight' => 600,
             'skills' => 'Vận động tinh, Tập trung & kiên nhẫn',
             'desc' => "Tốc độ tối đa khoảng 25 km/h, nhẹ và bám đường tốt trên sân xi măng.\nVỏ xe bằng nhựa PVC dẻo nên đâm vào tường cũng khó vỡ."],

            ['category' => 'Xe Điều Khiển Từ Xa', 'name' => 'Xe Ben Điều Khiển Công Trường Có Đèn', 'brand' => 'Double E', 'price' => 720000, 'qty' => 18,
             'age_min' => 48, 'age_max' => 120, 'battery' => 'Pin sạc, chơi 30 phút', 'warranty' => 6, 'weight' => 1100,
             'skills' => 'Vận động tinh, Sáng tạo',
             'desc' => "Thùng ben nâng đổ được bằng nút riêng trên tay điều khiển, có đèn pha sáng thật.\nBé thích xe công trình sẽ chơi hàng giờ với một đống cát và chiếc xe này."],

            ['category' => 'Xe Điều Khiển Từ Xa', 'name' => 'Xe Lật 360 Độ Điều Khiển Hai Mặt', 'brand' => 'ToyLand', 'price' => 380000, 'qty' => 32,
             'age_min' => 48, 'age_max' => 120, 'battery' => 'Pin sạc, chơi 20 phút', 'weight' => 500,
             'skills' => 'Vận động tinh',
             'desc' => "Xe chạy được cả khi lật ngửa, bánh to nhảy qua chướng ngại vật dễ dàng.\nGiá mềm, hợp làm món xe điều khiển đầu tiên cho bé mẫu giáo."],

            ['category' => 'Xe Điều Khiển Từ Xa', 'name' => 'Xe Cẩu Điều Khiển 6 Kênh Hợp Kim', 'brand' => 'Double E', 'price' => 1150000, 'qty' => 12,
             'age_min' => 72, 'age_max' => null, 'material' => 'Hợp kim + nhựa ABS', 'battery' => 'Pin sạc, chơi 35 phút', 'warranty' => 12, 'weight' => 1600,
             'skills' => 'Tư duy logic, Vận động tinh',
             'desc' => "Sáu kênh điều khiển riêng: tiến lùi, xoay thân, nâng cần, thả móc.\nCabin và cần cẩu bằng hợp kim, nặng tay và chắc chắn hơn hẳn xe nhựa."],

            ['category' => 'Xe Điều Khiển Từ Xa', 'name' => 'Xe Tăng Điều Khiển Bắn Đạn Nước', 'brand' => 'ToyLand', 'price' => 560000, 'qty' => 20,
             'age_min' => 72, 'age_max' => 168, 'gender' => 'boy', 'battery' => 'Pin sạc, chơi 25 phút', 'weight' => 900,
             'skills' => 'Vận động tinh, Tập trung & kiên nhẫn', 'safety' => 'Không bắn vào mặt người và vật nuôi.',
             'desc' => "Tháp pháo xoay 320 độ, bắn đạn nước mềm và có khói mô phỏng.\nXích lăn bằng cao su, bò qua thảm và bậc thềm thấp được."],

            // ---- Xe Mô Hình Die-cast ----
            ['category' => 'Xe Mô Hình Die-cast', 'name' => 'Hot Wheels Hộp 20 Xe Sưu Tập', 'brand' => 'Hot Wheels', 'price' => 690000, 'qty' => 28,
             'age_min' => 36, 'age_max' => 144, 'material' => 'Hợp kim đúc', 'origin' => 'Thái Lan', 'weight' => 900,
             'skills' => 'Vận động tinh, Sáng tạo', 'featured' => true,
             'desc' => "20 mẫu xe hợp kim tỉ lệ 1:64, mỗi chiếc một kiểu dáng và màu sơn khác nhau.\nMón khởi đầu kinh điển cho bé mê sưu tầm xe."],

            ['category' => 'Xe Mô Hình Die-cast', 'name' => 'Hot Wheels Đường Đua Vòng Xoắn Ốc', 'brand' => 'Hot Wheels', 'price' => 850000, 'qty' => 16,
             'age_min' => 48, 'age_max' => 144, 'origin' => 'Thái Lan', 'weight' => 1400, 'dims' => '70 x 40 x 55 cm',
             'skills' => 'Tư duy logic, Vận động tinh',
             'desc' => "Đường đua có vòng xoắn 360 độ và bệ phóng cò bấm, kèm 2 xe hợp kim.\nRáp thêm các bộ khác được vì đầu nối theo chuẩn chung của Hot Wheels."],

            ['category' => 'Xe Mô Hình Die-cast', 'name' => 'Mô Hình Xe Lamborghini Aventador 1:24', 'brand' => 'Bburago', 'price' => 420000, 'qty' => 22,
             'age_min' => 36, 'age_max' => null, 'material' => 'Hợp kim đúc + nhựa', 'origin' => 'Ý', 'weight' => 450, 'dims' => '19 x 8 x 5 cm',
             'skills' => 'Vận động tinh',
             'desc' => "Tỉ lệ 1:24, cửa cánh chim mở lên được, vô lăng xoay và bánh đánh lái theo.\nThân xe bằng hợp kim nên cầm nặng tay, khác hẳn xe nhựa cùng cỡ."],

            ['category' => 'Xe Mô Hình Die-cast', 'name' => 'Mô Hình Xe Bus Hai Tầng London 1:32', 'brand' => 'Tomica', 'price' => 280000, 'qty' => 26,
             'age_min' => 36, 'age_max' => 120, 'material' => 'Hợp kim đúc', 'origin' => 'Nhật Bản', 'weight' => 300,
             'skills' => 'Vận động tinh, Ngôn ngữ',
             'desc' => "Xe bus đỏ hai tầng đặc trưng của London, cửa mở và ghế bên trong chi tiết.\nCái cớ hay để kể cho bé nghe về một thành phố ở rất xa."],

            ['category' => 'Xe Mô Hình Die-cast', 'name' => 'Bộ 6 Xe Công Trình Hợp Kim Mini', 'brand' => 'ToyLand', 'price' => 320000, 'qty' => 34,
             'age_min' => 36, 'age_max' => 108, 'material' => 'Hợp kim đúc', 'weight' => 700,
             'skills' => 'Vận động tinh, Sáng tạo',
             'desc' => "Xe xúc, xe ben, xe lu, xe trộn bê tông, xe cẩu và xe ủi - đủ một công trường.\nCần và gàu xúc cử động được bằng tay."],

            ['category' => 'Xe Mô Hình Die-cast', 'name' => 'Mô Hình Xe Cảnh Sát Việt Nam 1:32 Có Đèn Còi', 'brand' => 'ToyLand', 'price' => 195000, 'qty' => 40,
             'age_min' => 36, 'age_max' => 120, 'material' => 'Hợp kim đúc', 'battery' => '3 viên LR44 (kèm sẵn)', 'weight' => 280,
             'skills' => 'Vận động tinh, Kỹ năng xã hội',
             'desc' => "Xe sơn màu và phù hiệu theo xe cảnh sát Việt Nam, bấm nút là hú còi và nháy đèn.\nBánh đà kéo lùi rồi thả cho xe tự chạy."],

            // ---- Tàu Hỏa & Đường Ray ----
            ['category' => 'Tàu Hỏa & Đường Ray', 'name' => 'Bộ Đường Ray Gỗ 100 Chi Tiết Kèm Tàu', 'brand' => 'Hape', 'price' => 1250000, 'qty' => 14,
             'age_min' => 36, 'age_max' => 96, 'material' => 'Gỗ tự nhiên sơn gốc nước', 'origin' => 'Đức', 'pieces' => 100, 'weight' => 2600,
             'skills' => 'Tư duy logic, Sáng tạo', 'featured' => true,
             'desc' => "100 chi tiết gồm ray thẳng, ray cong, cầu vượt, ga tàu và đoàn tàu nối nam châm.\nRay theo chuẩn chung nên ghép được với đường ray gỗ của hãng khác."],

            ['category' => 'Tàu Hỏa & Đường Ray', 'name' => 'Tàu Hỏa Điện Chạy Pin Có Khói Và Còi', 'brand' => 'ToyLand', 'price' => 680000, 'qty' => 20,
             'age_min' => 36, 'age_max' => 108, 'battery' => '3 viên AA (mua riêng)', 'weight' => 1500,
             'skills' => 'Tư duy logic, Sáng tạo',
             'desc' => "Đầu tàu nhả khói nước thật, đèn pha sáng và hú còi khi chạy.\nBộ ray tròn dài 3.5 mét, lắp thêm được các bộ ray mở rộng."],

            ['category' => 'Tàu Hỏa & Đường Ray', 'name' => 'Bộ Tàu Điện Ngầm Đô Thị 3 Toa', 'brand' => 'ToyLand', 'price' => 450000, 'qty' => 24,
             'age_min' => 36, 'age_max' => 108, 'weight' => 900,
             'skills' => 'Sáng tạo, Ngôn ngữ',
             'desc' => "Ba toa tàu nối nam châm, kèm ke ga và biển báo.\nBé vừa chơi vừa học được cách xếp hàng lên tàu, nhường ghế cho người già."],

            ['category' => 'Tàu Hỏa & Đường Ray', 'name' => 'Đường Ray Gỗ Núi Và Hầm 60 Chi Tiết', 'brand' => 'Hape', 'price' => 790000, 'qty' => 18,
             'age_min' => 36, 'age_max' => 96, 'material' => 'Gỗ tự nhiên sơn gốc nước', 'origin' => 'Đức', 'pieces' => 60, 'weight' => 1800,
             'skills' => 'Tư duy logic, Vận động tinh',
             'desc' => "Có hầm xuyên núi và đoạn dốc cho tàu thả trôi tự do.\nGhép được nhiều bố cục khác nhau nên chơi mãi không chán."],

            ['category' => 'Tàu Hỏa & Đường Ray', 'name' => 'Tàu Hỏa Thomas Chạy Pin Kèm Ray', 'brand' => 'Fisher-Price', 'price' => 890000, 'qty' => 15,
             'age_min' => 36, 'age_max' => 84, 'character' => 'Thomas & Friends', 'battery' => '2 viên AA (mua riêng)', 'weight' => 1200,
             'skills' => 'Ngôn ngữ, Sáng tạo',
             'desc' => "Đầu tàu Thomas mặt cười quen thuộc, chạy tự động trên ray khép kín.\nBé nào xem hoạt hình Thomas sẽ nhận ra ngay từ xa."],

            ['category' => 'Tàu Hỏa & Đường Ray', 'name' => 'Bộ Ray Nhựa Lắp Ghép Phát Sáng 200 Mảnh', 'brand' => 'ToyLand', 'price' => 390000, 'qty' => 30,
             'age_min' => 36, 'age_max' => 108, 'pieces' => 200, 'battery' => '3 viên AAA (mua riêng)', 'weight' => 1100,
             'skills' => 'Tư duy logic, Sáng tạo',
             'desc' => "Ray nhựa trong phát sáng trong tối, xe chạy pin tự đổi hướng khi chạm tường.\nTắt đèn phòng là cả bộ ray sáng xanh lét, bé rất thích."],

            // ---- Playset Phương Tiện ----
            ['category' => 'Playset Phương Tiện', 'name' => 'Bộ Bãi Đỗ Xe 4 Tầng Có Thang Máy', 'brand' => 'ToyLand', 'price' => 750000, 'qty' => 18,
             'age_min' => 36, 'age_max' => 108, 'weight' => 1800, 'dims' => '50 x 35 x 48 cm',
             'skills' => 'Tư duy logic, Sáng tạo', 'featured' => true,
             'desc' => "Bốn tầng đỗ xe nối bằng đường dốc xoắn, thang máy quay tay đưa xe lên tầng.\nKèm 6 xe hợp kim, trạm xăng và trạm rửa xe."],

            ['category' => 'Playset Phương Tiện', 'name' => 'Bộ Sân Bay Mini Có Máy Bay Và Xe Thang', 'brand' => 'ToyLand', 'price' => 520000, 'qty' => 22,
             'age_min' => 36, 'age_max' => 108, 'weight' => 1200,
             'skills' => 'Sáng tạo, Kỹ năng xã hội',
             'desc' => "Nhà ga, đài kiểm soát không lưu, máy bay chở khách và xe thang lên máy bay.\nBé đóng vai phi công, tiếp viên - trò chơi giả vờ rèn ngôn ngữ rất tốt."],

            ['category' => 'Playset Phương Tiện', 'name' => 'Bộ Trạm Cứu Hỏa Có Cột Trượt', 'brand' => 'ToyLand', 'price' => 620000, 'qty' => 19,
             'age_min' => 36, 'age_max' => 108, 'weight' => 1500,
             'skills' => 'Sáng tạo, Kỹ năng xã hội',
             'desc' => "Trạm cứu hỏa hai tầng có cột trượt cho lính cứu hỏa tụt xuống, kèm xe thang.\nCòi báo động kêu thật khi bấm nút."],

            ['category' => 'Playset Phương Tiện', 'name' => 'Bộ Thành Phố Giao Thông Thảm Chơi 100x80cm', 'brand' => 'ToyLand', 'price' => 280000, 'qty' => 35,
             'age_min' => 24, 'age_max' => 96, 'material' => 'Vải nỉ không dệt', 'weight' => 400, 'dims' => '100 x 80 cm',
             'skills' => 'Sáng tạo, Kỹ năng xã hội',
             'desc' => "Thảm in bản đồ thành phố có đường, vạch kẻ, bãi đỗ và công viên.\nGấp gọn cất tủ, trải ra là bé có ngay một thành phố để lái xe khắp nơi."],

            ['category' => 'Playset Phương Tiện', 'name' => 'Bộ Công Trường Xây Dựng Có Cần Cẩu Tháp', 'brand' => 'ToyLand', 'price' => 690000, 'qty' => 16,
             'age_min' => 48, 'age_max' => 120, 'weight' => 1700,
             'skills' => 'Tư duy logic, Sáng tạo',
             'desc' => "Cần cẩu tháp cao 55 cm quay 360 độ, kèm xe xúc, xe ben và khối bê tông mini.\nBé dựng được cả một công trường trên nền nhà."],

            ['category' => 'Playset Phương Tiện', 'name' => 'Bộ Cảng Biển Tàu Container Và Cẩu Bờ', 'brand' => 'ToyLand', 'price' => 580000, 'qty' => 17,
             'age_min' => 48, 'age_max' => 120, 'weight' => 1300,
             'skills' => 'Tư duy logic, Sáng tạo',
             'desc' => "Tàu container nổi được trên nước, cẩu bờ nhấc thùng hàng lên xuống.\nChơi được cả trên cạn lẫn trong bồn tắm."],
        ];
    }

    // ===================== 3. XE ĐẠP & SCOOTER =====================

    private static function bikes(): array
    {
        return [
            // ---- Xe Đạp Trẻ Em ----
            ['category' => 'Xe Đạp Trẻ Em', 'name' => 'Xe Đạp Trẻ Em 12 Inch Khung Thấp Có Bánh Phụ', 'brand' => 'Royal Baby', 'price' => 1590000, 'qty' => 15,
             'age_min' => 24, 'age_max' => 60, 'material' => 'Khung thép sơn tĩnh điện', 'warranty' => 12, 'weight' => 7500,
             'dims' => 'Bánh 12 inch, yên cao 45-55 cm', 'skills' => 'Vận động thô',
             'desc' => "Bánh 12 inch hợp bé cao 85-105 cm, hai bánh phụ tháo ra được khi bé cứng cáp.\nCó chắn xích kín để không kẹp ống quần."],

            ['category' => 'Xe Đạp Trẻ Em', 'name' => 'Xe Đạp Trẻ Em 16 Inch Khung Thép', 'brand' => 'Royal Baby', 'price' => 1890000, 'qty' => 12,
             'age_min' => 48, 'age_max' => 96, 'material' => 'Khung thép sơn tĩnh điện', 'warranty' => 12, 'weight' => 9500,
             'dims' => 'Bánh 16 inch, yên cao 55-68 cm', 'skills' => 'Vận động thô', 'featured' => true,
             'desc' => "Bánh 16 inch cho bé cao 105-125 cm, phanh tay và phanh chân đều có.\nYên và ghi đông chỉnh cao thấp nên theo bé được 2-3 năm."],

            ['category' => 'Xe Đạp Trẻ Em', 'name' => 'Xe Đạp Bé Gái 14 Inch Có Giỏ Hoa', 'brand' => 'Xaming', 'price' => 1450000, 'qty' => 14,
             'age_min' => 36, 'age_max' => 84, 'gender' => 'girl', 'material' => 'Khung thép sơn tĩnh điện', 'warranty' => 12, 'weight' => 8200,
             'dims' => 'Bánh 14 inch', 'skills' => 'Vận động thô',
             'desc' => "Màu hồng pastel, giỏ mây trước ghi đông và yên sau chở được búp bê.\nBánh phụ có đèn nháy khi lăn."],

            ['category' => 'Xe Đạp Trẻ Em', 'name' => 'Xe Đạp Địa Hình Trẻ Em 20 Inch 6 Tốc Độ', 'brand' => 'Fornix', 'price' => 2690000, 'qty' => 9,
             'age_min' => 84, 'age_max' => 156, 'material' => 'Khung thép carbon', 'warranty' => 12, 'weight' => 11000,
             'dims' => 'Bánh 20 inch', 'skills' => 'Vận động thô',
             'desc' => "Sáu tốc độ và phuộc nhún trước, dành cho bé lớn đã đạp vững.\nPhanh đĩa trước sau ăn tốt kể cả khi đường ướt."],

            ['category' => 'Xe Đạp Trẻ Em', 'name' => 'Xe Đạp Ba Bánh Có Cần Đẩy Cho Bé', 'brand' => 'Broller', 'price' => 1180000, 'qty' => 20,
             'age_min' => 12, 'age_max' => 48, 'material' => 'Khung thép + nhựa', 'warranty' => 6, 'weight' => 6500,
             'skills' => 'Vận động thô',
             'desc' => "Có cần đẩy phía sau để bố mẹ điều khiển khi bé chưa tự đạp được.\nMái che nắng và đai an toàn tháo rời khi bé lớn hơn."],

            ['category' => 'Xe Đạp Trẻ Em', 'name' => 'Xe Đạp Trẻ Em 18 Inch Kiểu Thể Thao', 'brand' => 'Xaming', 'price' => 1750000, 'qty' => 11,
             'age_min' => 60, 'age_max' => 120, 'gender' => 'boy', 'material' => 'Khung thép sơn tĩnh điện', 'warranty' => 12, 'weight' => 9800,
             'dims' => 'Bánh 18 inch', 'skills' => 'Vận động thô',
             'desc' => "Kiểu dáng thể thao, chắn bùn và gác chân sau chắc chắn.\nCó bánh phụ tháo rời cho giai đoạn bé tập giữ thăng bằng."],

            // ---- Scooter & Ván Trượt ----
            ['category' => 'Scooter & Ván Trượt', 'name' => 'Xe Scooter 3 Bánh Phát Sáng Gập Gọn', 'brand' => 'Broller', 'price' => 690000, 'qty' => 28,
             'age_min' => 36, 'age_max' => 120, 'material' => 'Nhôm + nhựa PU', 'warranty' => 6, 'weight' => 2800,
             'skills' => 'Vận động thô', 'featured' => true,
             'desc' => "Ba bánh nên bé mới tập không bị đổ, bánh PU phát sáng khi lăn mà không cần pin.\nGập gọn cất cốp xe máy được, chỉnh 4 nấc chiều cao tay cầm."],

            ['category' => 'Scooter & Ván Trượt', 'name' => 'Xe Scooter 2 Bánh Cho Bé Lớn', 'brand' => 'Centosy', 'price' => 890000, 'qty' => 18,
             'age_min' => 72, 'age_max' => 168, 'material' => 'Nhôm hợp kim', 'warranty' => 6, 'weight' => 3500,
             'skills' => 'Vận động thô',
             'desc' => "Hai bánh lớn 200 mm chạy êm trên vỉa hè, phanh chân sau ăn nhạy.\nChịu tải tới 100 kg nên người lớn đi cùng bé cũng được."],

            ['category' => 'Scooter & Ván Trượt', 'name' => 'Ván Trượt Skateboard Gỗ Phong 22 Inch', 'brand' => 'Centosy', 'price' => 520000, 'qty' => 22,
             'age_min' => 72, 'age_max' => 168, 'material' => 'Gỗ phong 7 lớp', 'weight' => 1800,
             'skills' => 'Vận động thô', 'safety' => 'Phải đội mũ bảo hiểm và đeo bảo hộ khi chơi.',
             'desc' => "Mặt ván nhám chống trượt, trục hợp kim và bánh PU 60 mm chạy êm.\nCỡ 22 inch là cỡ mini, vừa tầm bé 6-12 tuổi."],

            ['category' => 'Scooter & Ván Trượt', 'name' => 'Ván Trượt Phát Sáng 4 Bánh LED', 'brand' => 'ToyLand', 'price' => 390000, 'qty' => 26,
             'age_min' => 60, 'age_max' => 144, 'material' => 'Nhựa PP + bánh PU', 'weight' => 1500,
             'skills' => 'Vận động thô', 'safety' => 'Phải đội mũ bảo hiểm và đeo bảo hộ khi chơi.',
             'desc' => "Bốn bánh LED sáng khi lăn, mặt ván trong suốt nhìn thấy trục.\nNhẹ hơn ván gỗ nên bé nhỏ xách đi lại dễ dàng."],

            ['category' => 'Scooter & Ván Trượt', 'name' => 'Xe Scooter Có Yên Ngồi 2 Trong 1', 'brand' => 'Broller', 'price' => 820000, 'qty' => 20,
             'age_min' => 24, 'age_max' => 84, 'material' => 'Nhôm + nhựa', 'warranty' => 6, 'weight' => 3200,
             'skills' => 'Vận động thô',
             'desc' => "Tháo yên ra là scooter đứng, lắp yên vào là xe chòi chân có chỗ ngồi.\nMột món dùng được từ lúc bé 2 tuổi tới khi 7 tuổi."],

            ['category' => 'Scooter & Ván Trượt', 'name' => 'Giày Trượt Patin Chỉnh Size Cho Bé', 'brand' => 'Centosy', 'price' => 750000, 'qty' => 16,
             'age_min' => 60, 'age_max' => 144, 'material' => 'Nhựa PP + bánh PU', 'weight' => 2200,
             'skills' => 'Vận động thô', 'safety' => 'Phải đội mũ bảo hiểm và đeo bảo hộ khi chơi.',
             'desc' => "Chỉnh được 4 cỡ giày nên đi theo bé được nhiều năm.\nBánh PU phát sáng, khóa cài ba lớp ôm chân chắc chắn."],

            // ---- Xe Chòi Chân & Xe Lắc ----
            ['category' => 'Xe Chòi Chân & Xe Lắc', 'name' => 'Xe Chòi Chân Thăng Bằng Khung Gỗ', 'brand' => 'Hape', 'price' => 1290000, 'qty' => 16,
             'age_min' => 18, 'age_max' => 48, 'material' => 'Gỗ bạch dương', 'origin' => 'Đức', 'warranty' => 12, 'weight' => 3500,
             'skills' => 'Vận động thô', 'featured' => true,
             'desc' => "Không bàn đạp, bé chòi chân đẩy đi và tập giữ thăng bằng.\nHọc xe này rồi lên xe đạp hai bánh thường bỏ qua luôn giai đoạn bánh phụ."],

            ['category' => 'Xe Chòi Chân & Xe Lắc', 'name' => 'Xe Chòi Chân Thăng Bằng Khung Nhôm', 'brand' => 'Broller', 'price' => 980000, 'qty' => 20,
             'age_min' => 24, 'age_max' => 60, 'material' => 'Nhôm hợp kim', 'warranty' => 12, 'weight' => 2800,
             'skills' => 'Vận động thô',
             'desc' => "Khung nhôm chỉ 2.8 kg, bé tự dựng xe lên được khi ngã.\nLốp hơi bơm được, chạy êm hơn lốp đặc."],

            ['category' => 'Xe Chòi Chân & Xe Lắc', 'name' => 'Xe Lắc Trẻ Em Có Nhạc Và Đèn', 'brand' => 'ToyLand', 'price' => 450000, 'qty' => 30,
             'age_min' => 18, 'age_max' => 60, 'battery' => '2 viên AA (mua riêng)', 'weight' => 3000,
             'skills' => 'Vận động thô',
             'desc' => "Bé ngồi lắc vô lăng sang hai bên là xe tự tiến về phía trước.\nKhông cần pin vẫn chạy được, pin chỉ dùng cho đèn và nhạc."],

            ['category' => 'Xe Chòi Chân & Xe Lắc', 'name' => 'Xe Chòi Chân Hình Ô Tô Có Cốp', 'brand' => 'ToyLand', 'price' => 520000, 'qty' => 25,
             'age_min' => 12, 'age_max' => 48, 'weight' => 3200,
             'skills' => 'Vận động thô, Sáng tạo',
             'desc' => "Dáng ô tô con có cốp mở ra cất đồ chơi, còi bóp kêu bíp bíp.\nBánh cao su chạy êm, không xước sàn gỗ."],

            ['category' => 'Xe Chòi Chân & Xe Lắc', 'name' => 'Xe Chòi Chân Hình Con Hươu Cao Cổ', 'brand' => 'Hape', 'price' => 890000, 'qty' => 18,
             'age_min' => 12, 'age_max' => 42, 'material' => 'Gỗ bạch dương', 'origin' => 'Đức', 'weight' => 2600,
             'skills' => 'Vận động thô',
             'desc' => "Tạo hình hươu cao cổ ngộ nghĩnh, cổ hươu chính là tay cầm.\nBánh xe bọc cao su, đẩy trên sàn gần như không có tiếng."],

            ['category' => 'Xe Chòi Chân & Xe Lắc', 'name' => 'Xe Đẩy Tập Đi Kiêm Xe Chòi Chân 2in1', 'brand' => 'Broller', 'price' => 680000, 'qty' => 22,
             'age_min' => 9, 'age_max' => 36, 'weight' => 3400,
             'skills' => 'Vận động thô',
             'desc' => "Giai đoạn đầu bé vịn đẩy để tập đi, lớn hơn thì ngồi lên chòi chân.\nBánh có khóa hãm để bé mới tập không bị xe trôi."],

            // ---- Mũ & Đồ Bảo Hộ ----
            ['category' => 'Mũ & Đồ Bảo Hộ', 'name' => 'Mũ Bảo Hiểm Trẻ Em Có Đèn Hậu', 'brand' => 'Royal Baby', 'price' => 320000, 'qty' => 40,
             'age_min' => 36, 'age_max' => 144, 'material' => 'Vỏ PC + xốp EPS', 'weight' => 280, 'dims' => 'Vòng đầu 50-56 cm',
             'skills' => 'Vận động thô', 'featured' => true,
             'desc' => "Vỏ PC cứng, lõi xốp EPS hấp thụ lực đúng chuẩn mũ đi xe đạp.\nNúm vặn sau gáy chỉnh ôm đầu, đèn hậu nháy khi trời tối."],

            ['category' => 'Mũ & Đồ Bảo Hộ', 'name' => 'Bộ Bảo Hộ 6 Món Tay Chân Cho Bé', 'brand' => 'Centosy', 'price' => 280000, 'qty' => 45,
             'age_min' => 36, 'age_max' => 144, 'material' => 'Nhựa PP + vải lưới', 'weight' => 400,
             'skills' => 'Vận động thô',
             'desc' => "Gồm 2 bảo vệ khuỷu tay, 2 đầu gối và 2 cổ tay.\nMặt trong lót lưới thoáng, đeo lâu không hầm như loại nỉ."],

            ['category' => 'Mũ & Đồ Bảo Hộ', 'name' => 'Mũ Bảo Hiểm Trẻ Em Hình Khủng Long', 'brand' => 'ToyLand', 'price' => 290000, 'qty' => 35,
             'age_min' => 24, 'age_max' => 96, 'material' => 'Vỏ PC + xốp EPS', 'weight' => 260, 'dims' => 'Vòng đầu 48-54 cm',
             'skills' => 'Vận động thô',
             'desc' => "Có gai lưng khủng long trên đỉnh mũ - mẹo hay để bé chịu đội mũ mà không mè nheo.\nQuai cài nhả nhanh một chạm."],

            ['category' => 'Mũ & Đồ Bảo Hộ', 'name' => 'Mũ Bảo Hiểm Trẻ Em Hình Kỳ Lân', 'brand' => 'ToyLand', 'price' => 290000, 'qty' => 32,
             'age_min' => 24, 'age_max' => 96, 'gender' => 'girl', 'material' => 'Vỏ PC + xốp EPS', 'weight' => 260, 'dims' => 'Vòng đầu 48-54 cm',
             'skills' => 'Vận động thô',
             'desc' => "Sừng kỳ lân và bờm cầu vồng gắn trên mũ, tháo ra được khi bé không thích nữa.\nCùng chuẩn an toàn với các mũ khác, không phải mũ trang trí."],

            ['category' => 'Mũ & Đồ Bảo Hộ', 'name' => 'Găng Tay Đi Xe Đạp Trẻ Em Hở Ngón', 'brand' => 'Centosy', 'price' => 95000, 'qty' => 60,
             'age_min' => 48, 'age_max' => 144, 'material' => 'Vải lưới + đệm gel', 'weight' => 80,
             'skills' => 'Vận động thô',
             'desc' => "Lòng bàn tay có đệm gel chống chai và chống trượt tay lái.\nHở ngón nên bé vẫn bóp phanh và cầm nắm bình thường."],

            ['category' => 'Mũ & Đồ Bảo Hộ', 'name' => 'Đèn Xe Đạp Trẻ Em Gắn Ghi Đông', 'brand' => 'ToyLand', 'price' => 120000, 'qty' => 50,
             'age_min' => 48, 'age_max' => null, 'battery' => 'Pin sạc USB', 'weight' => 90,
             'skills' => 'Vận động thô',
             'desc' => "Đèn trước trắng, đèn sau đỏ nháy, sạc USB dùng được khoảng 6 giờ.\nKẹp cao su gắn vào ghi đông không cần vít."],
        ];
    }

    // ===================== 4. ĐỒ CHƠI THEO PHIM / NHÂN VẬT =====================
    // Nhóm được đầu tư nhiều nhất vì bộ lọc "nhân vật bé thích" sống bằng dữ liệu
    // ở đây. Mỗi giá trị 'character' khác nhau sẽ thành một chip lọc trên trang chủ,
    // nên tên nhân vật phải viết THỐNG NHẤT (luôn là 'Spider-Man', không lúc
    // 'Spiderman' lúc 'Người Nhện') - sai một chữ là thành hai chip riêng.

    private static function movies(): array
    {
        return [
            // ---- Siêu Anh Hùng Marvel & DC ----
            ['category' => 'Siêu Anh Hùng Marvel & DC', 'name' => 'Mô Hình Spider-Man Khớp Động 30cm', 'brand' => 'Hasbro', 'price' => 590000, 'qty' => 30,
             'age_min' => 48, 'age_max' => 144, 'character' => 'Spider-Man', 'gender' => 'boy', 'weight' => 400, 'dims' => 'Cao 30 cm',
             'skills' => 'Sáng tạo, Kỹ năng xã hội', 'featured' => true,
             'desc' => "Mô hình cao 30 cm với 22 khớp cử động, tạo được đủ tư thế bám tường quen thuộc.\nIn họa tiết mạng nhện nổi trên bộ đồ thay vì chỉ dán decal."],

            ['category' => 'Siêu Anh Hùng Marvel & DC', 'name' => 'Mô Hình Iron Man Mark 85 Phát Sáng', 'brand' => 'Hasbro', 'price' => 790000, 'qty' => 22,
             'age_min' => 72, 'age_max' => null, 'character' => 'Iron Man', 'gender' => 'boy', 'battery' => '2 viên LR44 (kèm sẵn)', 'weight' => 500,
             'skills' => 'Sáng tạo', 'desc' => "Lò phản ứng hồ quang ở ngực và lòng bàn tay phát sáng xanh khi bấm nút.\nGiáp sơn hai lớp đỏ - vàng kim, đứng vững nhờ đế trưng bày kèm theo."],

            ['category' => 'Siêu Anh Hùng Marvel & DC', 'name' => 'Bộ 6 Mô Hình Avengers Mini 10cm', 'brand' => 'Hasbro', 'price' => 690000, 'qty' => 26,
             'age_min' => 48, 'age_max' => 144, 'character' => 'Marvel', 'gender' => 'boy', 'weight' => 600,
             'skills' => 'Sáng tạo, Kỹ năng xã hội',
             'desc' => "Sáu nhân vật Iron Man, Captain America, Thor, Hulk, Black Widow và Hawkeye.\nCỡ 10 cm vừa tay bé, mang theo trong balo đi học được."],

            ['category' => 'Siêu Anh Hùng Marvel & DC', 'name' => 'Mô Hình Captain America Kèm Khiên', 'brand' => 'Hasbro', 'price' => 620000, 'qty' => 24,
             'age_min' => 48, 'age_max' => 144, 'character' => 'Captain America', 'gender' => 'boy', 'weight' => 420,
             'skills' => 'Sáng tạo',
             'desc' => "Khiên tháo rời gắn được vào tay hoặc sau lưng, cánh tay xoay để tạo tư thế ném.\nCao 28 cm, cùng cỡ với các mô hình Avengers khác."],

            ['category' => 'Siêu Anh Hùng Marvel & DC', 'name' => 'Mô Hình Hulk Cơ Bắp 33cm', 'brand' => 'Hasbro', 'price' => 680000, 'qty' => 20,
             'age_min' => 48, 'age_max' => 144, 'character' => 'Hulk', 'gender' => 'boy', 'weight' => 700,
             'skills' => 'Sáng tạo',
             'desc' => "Khối cơ bắp tạo hình chi tiết, tay to nắm được các mô hình nhỏ hơn.\nNhựa dày và nặng tay, chịu được kiểu chơi mạnh của bé trai."],

            ['category' => 'Siêu Anh Hùng Marvel & DC', 'name' => 'Mô Hình Thor Kèm Búa Mjolnir', 'brand' => 'Hasbro', 'price' => 640000, 'qty' => 21,
             'age_min' => 48, 'age_max' => 144, 'character' => 'Thor', 'gender' => 'boy', 'weight' => 450,
             'skills' => 'Sáng tạo',
             'desc' => "Búa Mjolnir tháo rời, áo choàng bằng vải thật chứ không phải nhựa đúc liền.\nTóc và râu tạo khối riêng, nhìn gần vẫn sắc nét."],

            ['category' => 'Siêu Anh Hùng Marvel & DC', 'name' => 'Mô Hình Black Panther Tư Thế Chiến Đấu', 'brand' => 'Hasbro', 'price' => 610000, 'qty' => 18,
             'age_min' => 48, 'age_max' => 144, 'character' => 'Black Panther', 'gender' => 'boy', 'weight' => 400,
             'skills' => 'Sáng tạo',
             'desc' => "Bộ giáp vibranium phủ lớp sơn ánh tím đặc trưng, móng vuốt tạo hình riêng.\nKhớp hông và gối gập sâu nên đặt được tư thế ngồi rình."],

            ['category' => 'Siêu Anh Hùng Marvel & DC', 'name' => 'Mô Hình Batman Kèm Xe Batmobile', 'brand' => 'Spin Master', 'price' => 890000, 'qty' => 19,
             'age_min' => 48, 'age_max' => 144, 'character' => 'Batman', 'gender' => 'boy', 'weight' => 900,
             'skills' => 'Sáng tạo', 'featured' => true,
             'desc' => "Mô hình Batman 10 cm kèm chiếc Batmobile dài 25 cm, bánh lăn thật.\nCabin mở ra cho nhân vật ngồi vào trong."],

            ['category' => 'Siêu Anh Hùng Marvel & DC', 'name' => 'Mô Hình Superman Áo Choàng Vải', 'brand' => 'Spin Master', 'price' => 580000, 'qty' => 20,
             'age_min' => 48, 'age_max' => 144, 'character' => 'Superman', 'gender' => 'boy', 'weight' => 400,
             'skills' => 'Sáng tạo',
             'desc' => "Áo choàng đỏ bằng vải bay theo khi bé cầm chạy.\nĐế trưng bày trong suốt giúp dựng được tư thế đang bay."],

            ['category' => 'Siêu Anh Hùng Marvel & DC', 'name' => 'Mô Hình Wonder Woman Kèm Dây Thòng Lọng', 'brand' => 'Spin Master', 'price' => 600000, 'qty' => 17,
             'age_min' => 48, 'age_max' => 144, 'character' => 'Wonder Woman', 'weight' => 400,
             'skills' => 'Sáng tạo',
             'desc' => "Kèm kiếm, khiên và sợi dây thòng lọng vàng tháo lắp được.\nMột trong số ít mô hình siêu anh hùng nữ, bé gái mê phim cũng chơi được."],

            ['category' => 'Siêu Anh Hùng Marvel & DC', 'name' => 'Găng Tay Bắn Tơ Spider-Man Có Âm Thanh', 'brand' => 'Hasbro', 'price' => 350000, 'qty' => 35,
             'age_min' => 48, 'age_max' => 120, 'character' => 'Spider-Man', 'gender' => 'boy', 'battery' => '2 viên AAA (mua riêng)', 'weight' => 250,
             'skills' => 'Sáng tạo, Vận động thô',
             'desc' => "Đeo vào cổ tay, gập cổ tay là phát ra tiếng bắn tơ và bắn ra sợi dây mềm.\nDây bắn bằng vải mềm, không gây đau khi trúng người."],

            ['category' => 'Siêu Anh Hùng Marvel & DC', 'name' => 'Bộ Trang Phục Hóa Trang Spider-Man Kèm Mặt Nạ', 'brand' => 'ToyLand', 'price' => 280000, 'qty' => 40,
             'age_min' => 36, 'age_max' => 120, 'character' => 'Spider-Man', 'gender' => 'boy', 'material' => 'Vải thun co giãn', 'weight' => 300,
             'skills' => 'Sáng tạo, Kỹ năng xã hội',
             'desc' => "Bộ liền thân co giãn kèm mặt nạ trùm đầu có lưới mắt nhìn xuyên được.\nHợp cho Halloween, sinh nhật hay ngày hội hóa trang ở trường."],

            // ---- Công Chúa & Disney ----
            ['category' => 'Công Chúa & Disney', 'name' => 'Búp Bê Elsa Frozen Váy Dạ Quang', 'brand' => 'Disney', 'price' => 690000, 'qty' => 28,
             'age_min' => 36, 'age_max' => 120, 'character' => 'Frozen', 'gender' => 'girl', 'weight' => 400, 'dims' => 'Cao 30 cm',
             'skills' => 'Sáng tạo, Kỹ năng xã hội', 'featured' => true,
             'desc' => "Váy xanh băng đính kim tuyến, phát sáng nhẹ trong bóng tối sau khi phơi đèn.\nTóc tết dài chải được, kèm lược và vương miện nhỏ."],

            ['category' => 'Công Chúa & Disney', 'name' => 'Búp Bê Anna Frozen Kèm Áo Choàng', 'brand' => 'Disney', 'price' => 650000, 'qty' => 26,
             'age_min' => 36, 'age_max' => 120, 'character' => 'Frozen', 'gender' => 'girl', 'weight' => 400, 'dims' => 'Cao 30 cm',
             'skills' => 'Sáng tạo, Kỹ năng xã hội',
             'desc' => "Anna trong bộ váy mùa đông kèm áo choàng lông và ủng tháo rời.\nGhép cùng búp bê Elsa thành cặp chị em như trong phim."],

            ['category' => 'Công Chúa & Disney', 'name' => 'Bộ 6 Búp Bê Công Chúa Disney Mini', 'brand' => 'Disney', 'price' => 890000, 'qty' => 20,
             'age_min' => 36, 'age_max' => 120, 'character' => 'Disney Princess', 'gender' => 'girl', 'weight' => 700,
             'skills' => 'Sáng tạo, Kỹ năng xã hội',
             'desc' => "Sáu nàng công chúa quen thuộc, mỗi nàng một bộ váy và phụ kiện riêng.\nCỡ 10 cm, xếp thành bộ trưng trên kệ rất xinh."],

            ['category' => 'Công Chúa & Disney', 'name' => 'Lâu Đài Công Chúa Disney Có Đèn Nhạc', 'brand' => 'Disney', 'price' => 1450000, 'qty' => 12,
             'age_min' => 36, 'age_max' => 108, 'character' => 'Disney Princess', 'gender' => 'girl', 'battery' => '3 viên AA (mua riêng)', 'weight' => 2200,
             'dims' => '60 x 35 x 70 cm', 'skills' => 'Sáng tạo, Kỹ năng xã hội', 'featured' => true,
             'desc' => "Lâu đài ba tầng có cầu thang xoắn, ban công và phòng ngủ đầy đủ nội thất.\nBấm nút là đèn tháp sáng lên kèm nhạc nền của phim."],

            ['category' => 'Công Chúa & Disney', 'name' => 'Búp Bê Nàng Tiên Cá Ariel Bơi Được', 'brand' => 'Disney', 'price' => 720000, 'qty' => 22,
             'age_min' => 36, 'age_max' => 108, 'character' => 'Disney Princess', 'gender' => 'girl', 'battery' => '2 viên AAA (mua riêng)', 'weight' => 450,
             'skills' => 'Sáng tạo',
             'desc' => "Thả vào bồn tắm là đuôi cá vẫy và búp bê tự bơi tới.\nTóc đỏ chống thấm, chải lại sau khi khô vẫn mượt."],

            ['category' => 'Công Chúa & Disney', 'name' => 'Mô Hình Mickey Mouse Cổ Điển 25cm', 'brand' => 'Disney', 'price' => 420000, 'qty' => 30,
             'age_min' => 24, 'age_max' => 108, 'character' => 'Mickey Mouse', 'weight' => 350,
             'skills' => 'Sáng tạo, Ngôn ngữ',
             'desc' => "Tạo hình Mickey cổ điển quần đỏ cúc vàng, tay chân xoay được.\nNhân vật mà cả bố mẹ lẫn con đều nhận ra ngay."],

            ['category' => 'Công Chúa & Disney', 'name' => 'Bộ Đồ Chơi Toy Story Woody Và Buzz', 'brand' => 'Disney', 'price' => 950000, 'qty' => 18,
             'age_min' => 36, 'age_max' => 120, 'character' => 'Toy Story', 'weight' => 800,
             'skills' => 'Sáng tạo, Kỹ năng xã hội',
             'desc' => "Cặp đôi Woody và Buzz Lightyear cao 25 cm, Buzz bung được cánh sau lưng.\nDây kéo sau lưng Woody phát ra câu thoại quen thuộc."],

            ['category' => 'Công Chúa & Disney', 'name' => 'Xe Mô Hình Lightning McQueen Cars', 'brand' => 'Disney', 'price' => 380000, 'qty' => 32,
             'age_min' => 24, 'age_max' => 108, 'character' => 'Cars', 'gender' => 'boy', 'material' => 'Hợp kim đúc', 'weight' => 300,
             'skills' => 'Sáng tạo',
             'desc' => "Chiếc xe đua số 95 màu đỏ, mắt in trên kính lái đúng như trong phim.\nThân hợp kim nặng tay, lăn bánh êm trên sàn."],

            ['category' => 'Công Chúa & Disney', 'name' => 'Thú Bông Stitch Ôm Ngủ 40cm', 'brand' => 'Disney', 'price' => 450000, 'qty' => 35,
             'age_min' => 12, 'age_max' => null, 'character' => 'Stitch', 'material' => 'Vải nhung + bông PP', 'weight' => 450, 'dims' => 'Cao 40 cm',
             'skills' => 'Kỹ năng xã hội', 'featured' => true,
             'desc' => "Chú Stitch xanh tai to, vải nhung mềm và đường may kín không lộ chỉ.\nGiặt máy được ở chế độ nhẹ, phơi nắng một buổi là khô."],

            ['category' => 'Công Chúa & Disney', 'name' => 'Bộ Nhân Vật Encanto Gia Đình Madrigal', 'brand' => 'Disney', 'price' => 780000, 'qty' => 16,
             'age_min' => 36, 'age_max' => 120, 'character' => 'Encanto', 'gender' => 'girl', 'weight' => 600,
             'skills' => 'Sáng tạo, Kỹ năng xã hội',
             'desc' => "Mirabel, Isabela, Luisa và bà Alma trong trang phục rực rỡ của phim.\nMỗi nhân vật kèm một phụ kiện nhỏ thể hiện phép màu riêng."],

            ['category' => 'Công Chúa & Disney', 'name' => 'Búp Bê Moana Kèm Thuyền Buồm', 'brand' => 'Disney', 'price' => 820000, 'qty' => 15,
             'age_min' => 36, 'age_max' => 120, 'character' => 'Moana', 'gender' => 'girl', 'weight' => 700,
             'skills' => 'Sáng tạo',
             'desc' => "Búp bê Moana 28 cm kèm chiếc thuyền buồm nổi được trên nước.\nBuồm bằng vải, cột buồm dựng lên hạ xuống được."],

            ['category' => 'Công Chúa & Disney', 'name' => 'Bộ Trang Điểm Công Chúa Disney An Toàn', 'brand' => 'Disney', 'price' => 390000, 'qty' => 28,
             'age_min' => 60, 'age_max' => 144, 'character' => 'Disney Princess', 'gender' => 'girl', 'material' => 'Mỹ phẩm gốc nước cho trẻ em', 'weight' => 500,
             'skills' => 'Vận động tinh, Sáng tạo', 'safety' => 'Thử lên cổ tay trước khi dùng cho bé có da nhạy cảm.',
             'desc' => "Phấn má, son dưỡng và sơn móng bóc được - đều gốc nước, rửa bằng nước ấm là sạch.\nHộp gương gập kiểu hộp trang sức của công chúa."],

            // ---- Anime & Manga ----
            ['category' => 'Anime & Manga', 'name' => 'Mô Hình Doraemon Chuông Cổ 20cm', 'brand' => 'Bandai', 'price' => 350000, 'qty' => 34,
             'age_min' => 36, 'age_max' => 144, 'character' => 'Doraemon', 'origin' => 'Nhật Bản', 'weight' => 300,
             'skills' => 'Sáng tạo, Ngôn ngữ', 'featured' => true,
             'desc' => "Chú mèo máy xanh quen thuộc, tay chân xoay và túi thần kỳ mở ra được.\nKèm 3 bảo bối mini: chong chóng tre, cánh cửa thần kỳ và bánh mì trí nhớ."],

            ['category' => 'Anime & Manga', 'name' => 'Bộ 5 Mô Hình Doraemon Biểu Cảm Mini', 'brand' => 'Bandai', 'price' => 280000, 'qty' => 30,
             'age_min' => 36, 'age_max' => 144, 'character' => 'Doraemon', 'origin' => 'Nhật Bản', 'weight' => 250,
             'skills' => 'Sáng tạo',
             'desc' => "Năm chú Doraemon cỡ 6 cm với năm biểu cảm khác nhau: cười, khóc, giận, sợ, ngủ.\nBày trên bàn học làm bé vui cả ngày."],

            ['category' => 'Anime & Manga', 'name' => 'Mô Hình Naruto Uzumaki Tư Thế Rasengan', 'brand' => 'Banpresto', 'price' => 620000, 'qty' => 20,
             'age_min' => 96, 'age_max' => null, 'character' => 'Naruto', 'origin' => 'Nhật Bản', 'weight' => 450, 'dims' => 'Cao 18 cm',
             'skills' => 'Sáng tạo',
             'desc' => "Tư thế tung chiêu Rasengan, quả cầu chakra bằng nhựa trong xanh.\nĐế trưng bày tạo hình mặt đất nứt, dựng rất vững."],

            ['category' => 'Anime & Manga', 'name' => 'Mô Hình Luffy One Piece Gear 5', 'brand' => 'Banpresto', 'price' => 750000, 'qty' => 18,
             'age_min' => 96, 'age_max' => null, 'character' => 'One Piece', 'origin' => 'Nhật Bản', 'weight' => 500, 'dims' => 'Cao 20 cm',
             'skills' => 'Sáng tạo',
             'desc' => "Luffy ở dạng Gear 5 tóc trắng, tạo hình cuộn khói quanh chân.\nSơn thủ công nhiều lớp, chi tiết khuôn mặt sắc nét."],

            ['category' => 'Anime & Manga', 'name' => 'Mô Hình Songoku Dragon Ball Super Saiyan', 'brand' => 'Banpresto', 'price' => 690000, 'qty' => 22,
             'age_min' => 96, 'age_max' => null, 'character' => 'Dragon Ball', 'origin' => 'Nhật Bản', 'weight' => 480, 'dims' => 'Cao 19 cm',
             'skills' => 'Sáng tạo',
             'desc' => "Songoku tóc vàng Super Saiyan, hào quang quanh người bằng nhựa trong.\nKèm 7 viên ngọc rồng mini bày quanh đế."],

            ['category' => 'Anime & Manga', 'name' => 'Mô Hình Tanjiro Demon Slayer Kèm Kiếm', 'brand' => 'Banpresto', 'price' => 640000, 'qty' => 19,
             'age_min' => 120, 'age_max' => null, 'character' => 'Demon Slayer', 'origin' => 'Nhật Bản', 'weight' => 420,
             'skills' => 'Sáng tạo',
             'desc' => "Tanjiro trong bộ haori kẻ ô đặc trưng, kiếm Nichirin tháo rời.\nÁo choàng đúc riêng rồi lắp vào nên nếp gấp tự nhiên."],

            ['category' => 'Anime & Manga', 'name' => 'Mô Hình Pikachu Điện Giật Phát Sáng', 'brand' => 'Bandai', 'price' => 420000, 'qty' => 32,
             'age_min' => 36, 'age_max' => 144, 'character' => 'Pokémon', 'origin' => 'Nhật Bản', 'battery' => '2 viên LR44 (kèm sẵn)', 'weight' => 300,
             'skills' => 'Sáng tạo', 'featured' => true,
             'desc' => "Hai má Pikachu sáng vàng và phát ra tiếng khi bấm vào đuôi.\nCao 18 cm, đứng vững nhờ đuôi hình tia chớp làm chân thứ ba."],

            ['category' => 'Anime & Manga', 'name' => 'Bộ 12 Mô Hình Pokémon Mini Sưu Tập', 'brand' => 'Bandai', 'price' => 550000, 'qty' => 26,
             'age_min' => 36, 'age_max' => 144, 'character' => 'Pokémon', 'origin' => 'Nhật Bản', 'weight' => 500,
             'skills' => 'Sáng tạo, Ngôn ngữ',
             'desc' => "Mười hai Pokémon quen thuộc cỡ 5 cm kèm 2 quả bóng Poké Ball mở ra được.\nBé vừa chơi vừa học thuộc tên và hệ của từng con."],

            ['category' => 'Anime & Manga', 'name' => 'Mô Hình Thám Tử Conan Kèm Phụ Kiện', 'brand' => 'Bandai', 'price' => 480000, 'qty' => 20,
             'age_min' => 96, 'age_max' => null, 'character' => 'Conan', 'origin' => 'Nhật Bản', 'weight' => 350,
             'skills' => 'Sáng tạo, Tư duy logic',
             'desc' => "Conan kèm kính, đồng hồ gây mê và ván trượt năng lượng mặt trời.\nHai mặt thay thế: mặt bình thường và mặt đang suy luận."],

            ['category' => 'Anime & Manga', 'name' => 'Mô Hình Ultraman Biến Hình Phát Sáng', 'brand' => 'Bandai', 'price' => 520000, 'qty' => 24,
             'age_min' => 48, 'age_max' => 144, 'character' => 'Ultraman', 'gender' => 'boy', 'origin' => 'Nhật Bản', 'battery' => '3 viên LR44 (kèm sẵn)', 'weight' => 400,
             'skills' => 'Sáng tạo',
             'desc' => "Đèn ngực đỏ nhấp nháy và phát tiếng biến hình khi bấm nút.\nCao 25 cm, khớp vai và hông xoay để tạo tư thế bắn chùm tia."],

            ['category' => 'Anime & Manga', 'name' => 'Thú Bông Totoro Ôm Ngủ 45cm', 'brand' => 'Ghibli', 'price' => 520000, 'qty' => 22,
             'age_min' => 12, 'age_max' => null, 'character' => 'Totoro', 'material' => 'Vải nhung + bông PP', 'origin' => 'Nhật Bản', 'weight' => 500,
             'skills' => 'Kỹ năng xã hội',
             'desc' => "Chú Totoro bụng tròn, lông xám mềm và mắt thêu chỉ chứ không gắn nhựa.\nAn toàn cho cả bé nhỏ vì không có chi tiết rời nào."],

            ['category' => 'Anime & Manga', 'name' => 'Mô Hình Gundam SD Chibi Dễ Lắp', 'brand' => 'Bandai', 'price' => 290000, 'qty' => 30,
             'age_min' => 72, 'age_max' => null, 'character' => 'Gundam', 'origin' => 'Nhật Bản', 'pieces' => 68, 'weight' => 180,
             'skills' => 'Vận động tinh, Tập trung & kiên nhẫn',
             'desc' => "Dòng SD đầu to thân ngắn, chỉ 68 chi tiết nên bé 7 tuổi lắp được trong một buổi tối.\nCửa ngõ dễ nhất để bước vào thế giới mô hình Gundam."],

            // ---- Nhân Vật Game ----
            ['category' => 'Nhân Vật Game', 'name' => 'Bộ Mô Hình Minecraft Steve Và Alex', 'brand' => 'Mattel', 'price' => 580000, 'qty' => 26,
             'age_min' => 72, 'age_max' => 168, 'character' => 'Minecraft', 'weight' => 400,
             'skills' => 'Sáng tạo, Tư duy logic', 'featured' => true,
             'desc' => "Hai nhân vật chính cao 8 cm, khớp vuông cử động đúng kiểu nhân vật trong game.\nKèm cuốc, kiếm và khối đất - đủ để tái hiện một cảnh đào hầm."],

            ['category' => 'Nhân Vật Game', 'name' => 'Mô Hình Creeper Minecraft Phát Sáng', 'brand' => 'Mattel', 'price' => 390000, 'qty' => 28,
             'age_min' => 72, 'age_max' => 168, 'character' => 'Minecraft', 'battery' => '3 viên AAA (mua riêng)', 'weight' => 350,
             'skills' => 'Sáng tạo',
             'desc' => "Con Creeper xanh quen thuộc, bấm nút là nháy đỏ và phát tiếng xì xì trước khi nổ.\nCao 20 cm, dùng làm đèn ngủ cho bé mê game cũng được."],

            ['category' => 'Nhân Vật Game', 'name' => 'Bộ Khối Xếp Hình Minecraft 300 Chi Tiết', 'brand' => 'Mattel', 'price' => 690000, 'qty' => 20,
             'age_min' => 72, 'age_max' => 168, 'character' => 'Minecraft', 'pieces' => 300, 'weight' => 900,
             'skills' => 'Tư duy logic, Sáng tạo',
             'desc' => "Xếp lại ngôi nhà, hầm mỏ và ruộng trong game bằng khối nhựa vuông.\nTương thích với gạch LEGO nên trộn chung bộ được."],

            ['category' => 'Nhân Vật Game', 'name' => 'Mô Hình Super Mario Kèm Nấm Và Ống Cống', 'brand' => 'Nintendo', 'price' => 620000, 'qty' => 24,
             'age_min' => 48, 'age_max' => 168, 'character' => 'Super Mario', 'origin' => 'Nhật Bản', 'weight' => 400,
             'skills' => 'Sáng tạo', 'featured' => true,
             'desc' => "Mario cao 12 cm kèm nấm đỏ, đồng xu và ống cống xanh chui vào được.\nGhép thêm các bộ playset khác để dựng nguyên một màn chơi."],

            ['category' => 'Nhân Vật Game', 'name' => 'Bộ 4 Mô Hình Mario Luigi Peach Yoshi', 'brand' => 'Nintendo', 'price' => 850000, 'qty' => 18,
             'age_min' => 48, 'age_max' => 168, 'character' => 'Super Mario', 'origin' => 'Nhật Bản', 'weight' => 600,
             'skills' => 'Sáng tạo, Kỹ năng xã hội',
             'desc' => "Đủ bộ tứ quen thuộc, mỗi nhân vật có khớp cử động riêng.\nYoshi há miệng thè lưỡi được - chi tiết bé nào cũng thích nghịch."],

            ['category' => 'Nhân Vật Game', 'name' => 'Mô Hình Sonic Tốc Độ Kèm Vòng Vàng', 'brand' => 'Jakks Pacific', 'price' => 450000, 'qty' => 25,
             'age_min' => 48, 'age_max' => 168, 'character' => 'Sonic', 'gender' => 'boy', 'weight' => 300,
             'skills' => 'Sáng tạo',
             'desc' => "Chú nhím xanh cao 15 cm, gai lưng và giày đỏ tạo hình đúng bản game.\nKèm 3 vòng vàng nhựa trong để bày quanh."],

            ['category' => 'Nhân Vật Game', 'name' => 'Bộ Mô Hình Roblox 6 Nhân Vật Kèm Mã', 'brand' => 'Jazwares', 'price' => 690000, 'qty' => 22,
             'age_min' => 72, 'age_max' => 168, 'character' => 'Roblox', 'weight' => 400,
             'skills' => 'Sáng tạo',
             'desc' => "Sáu nhân vật khối vuông tháo lắp đầu, thân, tay chân để trộn thành nhân vật mới.\nMỗi hộp kèm một mã vật phẩm ảo dùng trong game."],

            ['category' => 'Nhân Vật Game', 'name' => 'Mô Hình Among Us Phi Hành Gia 6 Màu', 'brand' => 'Toikido', 'price' => 350000, 'qty' => 30,
             'age_min' => 60, 'age_max' => 168, 'character' => 'Among Us', 'weight' => 250,
             'skills' => 'Sáng tạo, Kỹ năng xã hội',
             'desc' => "Sáu phi hành gia đủ màu, cỡ 6 cm, có ba lô tháo rời sau lưng.\nBé chơi trò tìm kẻ mạo danh ngay trên bàn học."],

            ['category' => 'Nhân Vật Game', 'name' => 'Bộ Bắn Chim Angry Birds Kèm Bia', 'brand' => 'Rovio', 'price' => 320000, 'qty' => 28,
             'age_min' => 48, 'age_max' => 144, 'character' => 'Angry Birds', 'weight' => 500,
             'skills' => 'Vận động tinh, Tư duy logic',
             'desc' => "Súng cao su nhựa bắn các chú chim bông vào tháp khối gỗ.\nBé phải tính góc và lực bắn - vật lý cơ bản trá hình thành trò chơi."],

            ['category' => 'Nhân Vật Game', 'name' => 'Mô Hình Pokémon Charizard Cỡ Lớn 30cm', 'brand' => 'Bandai', 'price' => 890000, 'qty' => 14,
             'age_min' => 72, 'age_max' => null, 'character' => 'Pokémon', 'origin' => 'Nhật Bản', 'weight' => 700, 'dims' => 'Sải cánh 35 cm',
             'skills' => 'Sáng tạo', 'featured' => true,
             'desc' => "Charizard cao 30 cm sải cánh 35 cm, cánh gập mở và đuôi lửa bằng nhựa trong.\nMón trưng bày được các bé sưu tập Pokémon săn lùng nhất."],
        ];
    }

    // ===================== 5. BÚP BÊ & NHÀ BÚP BÊ =====================

    private static function dolls(): array
    {
        return [
            // ---- Búp Bê Thời Trang ----
            ['category' => 'Búp Bê Thời Trang', 'name' => 'Búp Bê Barbie Nghề Nghiệp Giáo Viên', 'brand' => 'Barbie', 'price' => 520000, 'qty' => 30,
             'age_min' => 36, 'age_max' => 144, 'character' => 'Barbie', 'gender' => 'girl', 'weight' => 350,
             'skills' => 'Sáng tạo, Kỹ năng xã hội', 'featured' => true,
             'desc' => "Barbie đứng lớp kèm bảng nhỏ, phấn, sổ điểm và hai bé học sinh mini.\nDòng nghề nghiệp giúp bé hình dung công việc của người lớn."],

            ['category' => 'Búp Bê Thời Trang', 'name' => 'Búp Bê Barbie Phi Hành Gia', 'brand' => 'Barbie', 'price' => 540000, 'qty' => 24,
             'age_min' => 36, 'age_max' => 144, 'character' => 'Barbie', 'gender' => 'girl', 'weight' => 360,
             'skills' => 'Sáng tạo, Kỹ năng xã hội',
             'desc' => "Bộ đồ phi hành gia trắng kèm mũ bảo hiểm trong suốt tháo rời.\nKèm lá cờ nhỏ và mảnh đá mặt trăng để bé dựng cảnh."],

            ['category' => 'Búp Bê Thời Trang', 'name' => 'Búp Bê Barbie Tủ Đồ 20 Bộ Trang Phục', 'brand' => 'Barbie', 'price' => 890000, 'qty' => 18,
             'age_min' => 48, 'age_max' => 144, 'character' => 'Barbie', 'gender' => 'girl', 'weight' => 900,
             'skills' => 'Vận động tinh, Sáng tạo',
             'desc' => "Một búp bê kèm 20 bộ váy áo, 8 đôi giày và túi xách nhỏ.\nBé tự phối đồ - trò này rèn mắt thẩm mỹ và sự khéo tay hơn là chỉ ngắm."],

            ['category' => 'Búp Bê Thời Trang', 'name' => 'Búp Bê LOL Surprise Hộp Bóc Bất Ngờ', 'brand' => 'MGA', 'price' => 390000, 'qty' => 40,
             'age_min' => 48, 'age_max' => 144, 'character' => 'LOL Surprise', 'gender' => 'girl', 'weight' => 300,
             'skills' => 'Vận động tinh, Sáng tạo',
             'desc' => "Quả bóng bóc ra 7 lớp, mỗi lớp một món phụ kiện và cuối cùng là búp bê.\nBúp bê đổi màu khi nhúng vào nước lạnh."],

            ['category' => 'Búp Bê Thời Trang', 'name' => 'Búp Bê Tóc Dài Tạo Kiểu Kèm Máy Uốn', 'brand' => 'ToyLand', 'price' => 450000, 'qty' => 28,
             'age_min' => 60, 'age_max' => 144, 'gender' => 'girl', 'weight' => 500,
             'skills' => 'Vận động tinh, Sáng tạo',
             'desc' => "Tóc dài tới gối, kèm lược, kẹp, thun và máy uốn đồ chơi không nóng.\nSợi tóc chịu được chải nhiều lần mà không xù."],

            ['category' => 'Búp Bê Thời Trang', 'name' => 'Búp Bê Công Chúa Váy Dạ Hội Phát Sáng', 'brand' => 'ToyLand', 'price' => 420000, 'qty' => 32,
             'age_min' => 36, 'age_max' => 120, 'gender' => 'girl', 'battery' => '3 viên LR44 (kèm sẵn)', 'weight' => 400,
             'skills' => 'Sáng tạo',
             'desc' => "Váy dạ hội xòe có đèn LED nhỏ bên trong, tắt đèn là váy sáng lung linh.\nVương miện và đôi hài thủy tinh tháo rời."],

            // ---- Nhà Búp Bê & Phụ Kiện ----
            ['category' => 'Nhà Búp Bê & Phụ Kiện', 'name' => 'Nhà Búp Bê Gỗ 3 Tầng Kèm Nội Thất', 'brand' => 'Hape', 'price' => 2290000, 'qty' => 10,
             'age_min' => 36, 'age_max' => 120, 'gender' => 'girl', 'material' => 'Gỗ tự nhiên sơn gốc nước', 'origin' => 'Đức', 'weight' => 6500,
             'dims' => '60 x 30 x 85 cm', 'skills' => 'Sáng tạo, Kỹ năng xã hội', 'featured' => true,
             'desc' => "Ba tầng mở hai mặt, kèm 25 món nội thất gỗ và 4 nhân vật gia đình.\nSơn gốc nước không mùi, cạnh bo tròn toàn bộ."],

            ['category' => 'Nhà Búp Bê & Phụ Kiện', 'name' => 'Bộ Phòng Bếp Búp Bê Mini Có Đèn', 'brand' => 'ToyLand', 'price' => 490000, 'qty' => 24,
             'age_min' => 36, 'age_max' => 120, 'gender' => 'girl', 'battery' => '2 viên AAA (mua riêng)', 'weight' => 800,
             'skills' => 'Sáng tạo, Kỹ năng xã hội',
             'desc' => "Tủ bếp, bồn rửa, lò nướng mở cửa được và hơn 30 món đồ ăn mini.\nĐèn bếp sáng và vòi nước có tiếng chảy róc rách."],

            ['category' => 'Nhà Búp Bê & Phụ Kiện', 'name' => 'Bộ Phòng Ngủ Búp Bê Có Giường Tầng', 'brand' => 'ToyLand', 'price' => 420000, 'qty' => 26,
             'age_min' => 36, 'age_max' => 120, 'gender' => 'girl', 'weight' => 700,
             'skills' => 'Sáng tạo',
             'desc' => "Giường tầng có thang, chăn gối vải thật và tủ quần áo mở cánh.\nCỡ vừa cho búp bê 15-20 cm."],

            ['category' => 'Nhà Búp Bê & Phụ Kiện', 'name' => 'Xe Hơi Mui Trần Cho Búp Bê', 'brand' => 'Barbie', 'price' => 690000, 'qty' => 20,
             'age_min' => 36, 'age_max' => 120, 'character' => 'Barbie', 'gender' => 'girl', 'weight' => 900, 'dims' => 'Dài 40 cm',
             'skills' => 'Sáng tạo',
             'desc' => "Xe mui trần hồng chở được 2 búp bê cỡ 30 cm, bánh lăn êm.\nCốp sau mở ra đựng vừa một vali đồ nhỏ."],

            ['category' => 'Nhà Búp Bê & Phụ Kiện', 'name' => 'Nhà Búp Bê Vali Xách Tay Gấp Gọn', 'brand' => 'ToyLand', 'price' => 580000, 'qty' => 22,
             'age_min' => 36, 'age_max' => 108, 'gender' => 'girl', 'weight' => 1400, 'dims' => 'Gấp lại 35 x 28 x 10 cm',
             'skills' => 'Sáng tạo, Kỹ năng xã hội',
             'desc' => "Gập lại thành vali có quai xách, mở ra là ngôi nhà hai tầng đủ nội thất.\nMang sang nhà bạn chơi hay về quê đều gọn."],

            ['category' => 'Nhà Búp Bê & Phụ Kiện', 'name' => 'Bộ Tiệm Bánh Mini Cho Búp Bê', 'brand' => 'ToyLand', 'price' => 450000, 'qty' => 25,
             'age_min' => 36, 'age_max' => 120, 'gender' => 'girl', 'weight' => 750,
             'skills' => 'Sáng tạo, Kỹ năng xã hội',
             'desc' => "Quầy bánh có tủ kính, máy tính tiền và hơn 20 loại bánh mini.\nBé đóng vai chủ tiệm - tập đếm tiền và nói lời cảm ơn khách."],

            // ---- Búp Bê Vải & Sơ Sinh ----
            ['category' => 'Búp Bê Vải & Sơ Sinh', 'name' => 'Búp Bê Vải Hữu Cơ Cho Bé Sơ Sinh', 'brand' => 'Hape', 'price' => 380000, 'qty' => 30,
             'age_min' => 0, 'age_max' => 36, 'material' => 'Vải cotton hữu cơ', 'origin' => 'Đức', 'weight' => 150,
             'safety' => 'Không có chi tiết rời, an toàn cho bé sơ sinh.', 'skills' => 'Kỹ năng xã hội',
             'desc' => "Toàn thân bằng vải cotton hữu cơ, mắt mũi thêu chỉ nên không có gì bong ra.\nGiặt máy thoải mái, càng giặt vải càng mềm."],

            ['category' => 'Búp Bê Vải & Sơ Sinh', 'name' => 'Búp Bê Em Bé Biết Khóc Và Uống Sữa', 'brand' => 'ToyLand', 'price' => 490000, 'qty' => 26,
             'age_min' => 24, 'age_max' => 84, 'gender' => 'girl', 'battery' => '3 viên AA (mua riêng)', 'weight' => 700,
             'skills' => 'Kỹ năng xã hội, Sáng tạo', 'featured' => true,
             'desc' => "Uống được bình sữa nước, khóc khi tã ướt và nín khi được bế vỗ về.\nKèm bình sữa, tã và ti giả - bé học cách chăm em từ đây."],

            ['category' => 'Búp Bê Vải & Sơ Sinh', 'name' => 'Búp Bê Vải Gấu Bông Ôm Ngủ 35cm', 'brand' => 'ToyLand', 'price' => 250000, 'qty' => 40,
             'age_min' => 0, 'age_max' => 60, 'material' => 'Vải nhung + bông PP', 'weight' => 300,
             'safety' => 'Không có chi tiết rời, an toàn cho bé sơ sinh.', 'skills' => 'Kỹ năng xã hội',
             'desc' => "Thân mềm oặt, bé ôm ngủ không bị cấn.\nMắt thêu, không dùng hạt nhựa nên không lo bé bứt ra."],

            ['category' => 'Búp Bê Vải & Sơ Sinh', 'name' => 'Bộ Chăm Em Bé Kèm Xe Đẩy Búp Bê', 'brand' => 'ToyLand', 'price' => 690000, 'qty' => 20,
             'age_min' => 24, 'age_max' => 84, 'gender' => 'girl', 'weight' => 1800,
             'skills' => 'Kỹ năng xã hội, Vận động thô',
             'desc' => "Xe đẩy gấp gọn cho búp bê kèm búp bê em bé, bình sữa và chăn nhỏ.\nBé đẩy đi khắp nhà - vừa vận động vừa tập chăm sóc."],

            ['category' => 'Búp Bê Vải & Sơ Sinh', 'name' => 'Búp Bê Vải Thổ Cẩm Tây Bắc', 'brand' => 'ToyLand', 'price' => 290000, 'qty' => 24,
             'age_min' => 12, 'age_max' => null, 'material' => 'Vải thổ cẩm dệt tay', 'origin' => 'Việt Nam', 'weight' => 200,
             'skills' => 'Kỹ năng xã hội, Ngôn ngữ',
             'desc' => "Búp bê mặc trang phục thổ cẩm dệt tay của đồng bào vùng cao.\nMón quà giúp bé làm quen với sự đa dạng văn hóa ngay trong nước mình."],

            ['category' => 'Búp Bê Vải & Sơ Sinh', 'name' => 'Búp Bê Em Bé Tắm Được Kèm Bồn Tắm', 'brand' => 'ToyLand', 'price' => 420000, 'qty' => 28,
             'age_min' => 24, 'age_max' => 72, 'material' => 'Nhựa PVC chống thấm', 'weight' => 900,
             'skills' => 'Kỹ năng xã hội, Vận động tinh',
             'desc' => "Búp bê nhựa kín nước ngâm nước thoải mái, kèm bồn tắm, vòi sen và bọt biển.\nBiến giờ tắm của bé thành giờ chơi, hết cảnh khóc lóc."],

            // ---- Búp Bê Nhân Vật ----
            ['category' => 'Búp Bê Nhân Vật', 'name' => 'Búp Bê Hello Kitty Váy Hồng 30cm', 'brand' => 'Sanrio', 'price' => 390000, 'qty' => 32,
             'age_min' => 24, 'age_max' => 120, 'character' => 'Hello Kitty', 'gender' => 'girl', 'material' => 'Vải nhung + bông PP', 'origin' => 'Nhật Bản', 'weight' => 350,
             'skills' => 'Kỹ năng xã hội', 'featured' => true,
             'desc' => "Cô mèo nơ đỏ quen thuộc trong bộ váy hồng thay ra được.\nVải nhung mềm, đường may chắc, giặt tay được."],

            ['category' => 'Búp Bê Nhân Vật', 'name' => 'Thú Bông Peppa Pig Gia Đình 4 Con', 'brand' => 'Hasbro', 'price' => 490000, 'qty' => 28,
             'age_min' => 18, 'age_max' => 84, 'character' => 'Peppa Pig', 'material' => 'Vải nhung + bông PP', 'weight' => 450,
             'skills' => 'Ngôn ngữ, Kỹ năng xã hội',
             'desc' => "Đủ bốn thành viên Peppa, George, bố và mẹ heo.\nBé xem hoạt hình rồi diễn lại bằng thú bông - cách luyện nói rất tự nhiên."],

            ['category' => 'Búp Bê Nhân Vật', 'name' => 'Bộ 6 Nhân Vật Paw Patrol Kèm Xe', 'brand' => 'Spin Master', 'price' => 790000, 'qty' => 22,
             'age_min' => 36, 'age_max' => 96, 'character' => 'Paw Patrol', 'weight' => 800,
             'skills' => 'Sáng tạo, Kỹ năng xã hội', 'featured' => true,
             'desc' => "Sáu chú chó cứu hộ Chase, Marshall, Skye, Rubble, Rocky, Zuma và xe riêng.\nMỗi xe có bánh lăn và ba lô cứu hộ bung ra được."],

            ['category' => 'Búp Bê Nhân Vật', 'name' => 'Thú Bông Baby Shark Phát Nhạc', 'brand' => 'Pinkfong', 'price' => 350000, 'qty' => 35,
             'age_min' => 12, 'age_max' => 60, 'character' => 'Baby Shark', 'material' => 'Vải nhung + bông PP', 'battery' => '3 viên AAA (kèm sẵn)', 'weight' => 300,
             'skills' => 'Ngôn ngữ',
             'desc' => "Bóp bụng là phát bài Baby Shark quen thuộc, đèn mắt nháy theo nhạc.\nHộp pin bắt vít kín, bé không tự mở được."],

            ['category' => 'Búp Bê Nhân Vật', 'name' => 'Thú Bông Bluey Và Bingo 30cm', 'brand' => 'Moose Toys', 'price' => 460000, 'qty' => 24,
             'age_min' => 18, 'age_max' => 84, 'character' => 'Bluey', 'material' => 'Vải nhung + bông PP', 'weight' => 400,
             'skills' => 'Ngôn ngữ, Kỹ năng xã hội',
             'desc' => "Cặp chị em chó xanh trong bộ phim hoạt hình Úc được yêu thích.\nĐường may chắc, tai và đuôi đính kỹ không bung."],

            ['category' => 'Búp Bê Nhân Vật', 'name' => 'Bộ Nhân Vật Cocomelon JJ Và Gia Đình', 'brand' => 'Jazwares', 'price' => 520000, 'qty' => 26,
             'age_min' => 12, 'age_max' => 60, 'character' => 'Cocomelon', 'weight' => 500,
             'skills' => 'Ngôn ngữ, Kỹ năng xã hội',
             'desc' => "Bé JJ cùng bố mẹ và anh chị, cỡ 10 cm vừa tay bé nhỏ.\nNhựa mềm bo tròn, không có cạnh sắc."],
        ];
    }

    // ===================== 6. ROBOT & ĐỒ CHƠI CÔNG NGHỆ =====================

    private static function robots(): array
    {
        return [
            // ---- Robot Biến Hình ----
            ['category' => 'Robot Biến Hình', 'name' => 'Robot Biến Hình Optimus Prime 25cm', 'brand' => 'Transformers', 'price' => 890000, 'qty' => 22,
             'age_min' => 72, 'age_max' => 168, 'character' => 'Transformers', 'gender' => 'boy', 'weight' => 700,
             'skills' => 'Tư duy logic, Vận động tinh', 'featured' => true,
             'desc' => "Biến từ xe đầu kéo thành robot cao 25 cm qua 22 bước gấp.\nBản hướng dẫn có hình từng bước, bé 7 tuổi làm được sau vài lần thử."],

            ['category' => 'Robot Biến Hình', 'name' => 'Robot Biến Hình Bumblebee Kèm Âm Thanh', 'brand' => 'Transformers', 'price' => 790000, 'qty' => 20,
             'age_min' => 72, 'age_max' => 168, 'character' => 'Transformers', 'gender' => 'boy', 'battery' => '3 viên LR44 (kèm sẵn)', 'weight' => 600,
             'skills' => 'Tư duy logic, Vận động tinh',
             'desc' => "Xe thể thao vàng biến thành robot, phát tiếng động cơ và tiếng biến hình.\nKhớp gấp bằng nhựa dày, chịu được cảnh bé gập đi gập lại hàng trăm lần."],

            ['category' => 'Robot Biến Hình', 'name' => 'Bộ 5 Robot Cứu Hộ Biến Hình Ghép Hợp Thể', 'brand' => 'ToyLand', 'price' => 620000, 'qty' => 24,
             'age_min' => 60, 'age_max' => 144, 'gender' => 'boy', 'weight' => 800,
             'skills' => 'Tư duy logic, Sáng tạo',
             'desc' => "Năm robot nhỏ mỗi con biến thành một loại xe, ghép lại thành một robot lớn 35 cm.\nÝ tưởng cũ mà vẫn hấp dẫn: năm món đồ chơi trong một hộp."],

            ['category' => 'Robot Biến Hình', 'name' => 'Robot Biến Hình Khủng Long Bạo Chúa', 'brand' => 'ToyLand', 'price' => 450000, 'qty' => 30,
             'age_min' => 48, 'age_max' => 120, 'gender' => 'boy', 'weight' => 500,
             'skills' => 'Tư duy logic, Vận động tinh',
             'desc' => "Khủng long T-Rex gập lại thành robot chiến binh, chỉ 8 bước nên bé mẫu giáo làm được.\nRăng và vuốt bằng nhựa mềm, không đâm vào tay."],

            ['category' => 'Robot Biến Hình', 'name' => 'Robot Biến Hình Xe Cảnh Sát Có Đèn', 'brand' => 'ToyLand', 'price' => 390000, 'qty' => 32,
             'age_min' => 48, 'age_max' => 120, 'gender' => 'boy', 'battery' => '3 viên AAA (mua riêng)', 'weight' => 450,
             'skills' => 'Tư duy logic, Sáng tạo',
             'desc' => "Xe cảnh sát hú còi nháy đèn, gập ra thành robot cầm khiên.\nGiá vừa túi, hợp làm quà cho bạn cùng lớp của bé."],

            ['category' => 'Robot Biến Hình', 'name' => 'Robot Biến Hình Máy Bay Chiến Đấu', 'brand' => 'Transformers', 'price' => 690000, 'qty' => 18,
             'age_min' => 72, 'age_max' => 168, 'character' => 'Transformers', 'gender' => 'boy', 'weight' => 550,
             'skills' => 'Tư duy logic, Vận động tinh',
             'desc' => "Tiêm kích gập thành robot, cánh máy bay trở thành tấm chắn sau lưng.\nCó càng đáp thả xuống được khi ở dạng máy bay."],

            // ---- Robot Điều Khiển ----
            ['category' => 'Robot Điều Khiển', 'name' => 'Robot Thông Minh Điều Khiển Cử Chỉ Tay', 'brand' => 'JJRC', 'price' => 950000, 'qty' => 18,
             'age_min' => 60, 'age_max' => 144, 'battery' => 'Pin sạc USB, chơi 60 phút', 'warranty' => 6, 'weight' => 900,
             'skills' => 'Tư duy logic, Sáng tạo', 'featured' => true,
             'desc' => "Đeo cảm biến lên tay, vẫy tay là robot đi theo hướng đó.\nCó chế độ nhảy theo nhạc và ghi âm rồi phát lại bằng giọng robot."],

            ['category' => 'Robot Điều Khiển', 'name' => 'Robot Chó Điện Tử Biết Sủa Và Nhào Lộn', 'brand' => 'JJRC', 'price' => 780000, 'qty' => 20,
             'age_min' => 48, 'age_max' => 120, 'battery' => 'Pin sạc USB, chơi 45 phút', 'warranty' => 6, 'weight' => 700,
             'skills' => 'Sáng tạo, Kỹ năng xã hội',
             'desc' => "Chó robot biết đi, sủa, ngồi và lộn nhào khi ra lệnh bằng tay điều khiển.\nLựa chọn thay thế cho bé thích chó mà nhà không nuôi được."],

            ['category' => 'Robot Điều Khiển', 'name' => 'Robot Đối Kháng Đấm Bốc 2 Người Chơi', 'brand' => 'ToyLand', 'price' => 690000, 'qty' => 22,
             'age_min' => 60, 'age_max' => 144, 'battery' => '6 viên AA (mua riêng)', 'weight' => 1200,
             'skills' => 'Vận động tinh, Kỹ năng xã hội',
             'desc' => "Hai robot, hai tay điều khiển - ai đấm rụng đầu đối thủ trước thì thắng.\nTrò chơi hai người, hợp cho hai anh em hoặc bố chơi cùng con."],

            ['category' => 'Robot Điều Khiển', 'name' => 'Robot Phun Khói Nhảy Múa Có Đèn LED', 'brand' => 'ToyLand', 'price' => 520000, 'qty' => 26,
             'age_min' => 36, 'age_max' => 108, 'battery' => '4 viên AA (mua riêng)', 'weight' => 800,
             'skills' => 'Sáng tạo',
             'desc' => "Robot đi tới lui, xoay người nhảy theo nhạc, ngực phun sương và nháy đèn.\nTự đổi hướng khi gặp tường nên không cần trông chừng."],

            ['category' => 'Robot Điều Khiển', 'name' => 'Robot Biến Hình Điều Khiển Thành Xe Đua', 'brand' => 'JJRC', 'price' => 890000, 'qty' => 16,
             'age_min' => 60, 'age_max' => 144, 'battery' => 'Pin sạc USB, chơi 40 phút', 'warranty' => 6, 'weight' => 950,
             'skills' => 'Tư duy logic, Vận động tinh',
             'desc' => "Bấm một nút trên tay điều khiển là robot tự gập thành xe đua rồi phóng đi.\nKhông cần bé tự gập - cơ cấu biến hình chạy bằng mô tơ."],

            ['category' => 'Robot Điều Khiển', 'name' => 'Robot Lau Nhà Mini Đồ Chơi Cho Bé', 'brand' => 'ToyLand', 'price' => 350000, 'qty' => 28,
             'age_min' => 36, 'age_max' => 96, 'battery' => '3 viên AA (mua riêng)', 'weight' => 400,
             'skills' => 'Kỹ năng xã hội, Sáng tạo',
             'desc' => "Robot hút bụi thu nhỏ chạy vòng quanh, có khăn lau thật ở đáy.\nBé bắt chước việc nhà của bố mẹ - và sàn nhà cũng sạch hơn một chút."],

            // ---- Lập Trình & STEM ----
            ['category' => 'Lập Trình & STEM', 'name' => 'Robot Lập Trình Thẻ Lệnh Cho Bé Mẫu Giáo', 'brand' => 'Botzees', 'price' => 1290000, 'qty' => 14,
             'age_min' => 48, 'age_max' => 96, 'battery' => 'Pin sạc USB', 'warranty' => 12, 'weight' => 900,
             'skills' => 'Tư duy logic, Toán học sớm', 'featured' => true,
             'desc' => "Bé xếp các thẻ mũi tên thành chuỗi lệnh, robot chạy đúng đường đã xếp.\nHọc lập trình mà không cần biết chữ và không cần máy tính."],

            ['category' => 'Lập Trình & STEM', 'name' => 'Bộ Mạch Điện Tử Lắp Ghép 100 Thí Nghiệm', 'brand' => 'Snap Circuits', 'price' => 1450000, 'qty' => 12,
             'age_min' => 96, 'age_max' => null, 'pieces' => 60, 'weight' => 1400,
             'skills' => 'Tư duy logic, Tập trung & kiên nhẫn',
             'desc' => "Các linh kiện gắn nút bấm vào đế, không cần hàn và không dùng điện lưới.\nSách 100 thí nghiệm từ bật đèn tới làm còi báo động và radio AM."],

            ['category' => 'Lập Trình & STEM', 'name' => 'Bộ Robot STEM Năng Lượng Mặt Trời 14in1', 'brand' => 'ToyLand', 'price' => 490000, 'qty' => 26,
             'age_min' => 96, 'age_max' => 168, 'pieces' => 190, 'weight' => 600,
             'skills' => 'Tư duy logic, Vận động tinh',
             'desc' => "Một bộ chi tiết lắp được 14 mô hình khác nhau, chạy bằng tấm pin mặt trời.\nKhông cần pin - mang ra sân nắng là robot tự chạy."],

            ['category' => 'Lập Trình & STEM', 'name' => 'Bộ Lắp Ghép Kỹ Thuật Bánh Răng 200 Chi Tiết', 'brand' => 'Gigo', 'price' => 890000, 'qty' => 18,
             'age_min' => 72, 'age_max' => 168, 'pieces' => 200, 'weight' => 1100,
             'skills' => 'Tư duy logic, Vận động tinh',
             'desc' => "Bánh răng, trục và thanh nối lắp thành cối xay gió, cần cẩu, xe kéo.\nBé thấy tận mắt vì sao bánh răng to quay chậm hơn bánh răng nhỏ."],

            ['category' => 'Lập Trình & STEM', 'name' => 'Bộ Kit Học Arduino Cho Người Mới', 'brand' => 'Elegoo', 'price' => 690000, 'qty' => 20,
             'age_min' => 144, 'age_max' => null, 'pieces' => 120, 'weight' => 800,
             'skills' => 'Tư duy logic, Tập trung & kiên nhẫn',
             'desc' => "Bo mạch tương thích Arduino Uno kèm cảm biến, màn hình LCD và hơn 20 bài mẫu.\nBước đệm thật sự cho bạn nào muốn học lập trình phần cứng."],

            ['category' => 'Lập Trình & STEM', 'name' => 'Bộ Thí Nghiệm Thủy Lực Cánh Tay Robot', 'brand' => 'Gigo', 'price' => 620000, 'qty' => 19,
             'age_min' => 96, 'age_max' => 168, 'pieces' => 229, 'weight' => 900,
             'skills' => 'Tư duy logic, Vận động tinh',
             'desc' => "Cánh tay robot gắp đồ vật, điều khiển bằng 6 xi-lanh bơm nước - không dùng điện.\nMinh họa nguyên lý thủy lực rõ ràng hơn bất cứ bài giảng nào."],

            // ---- Drone & Máy Bay ----
            ['category' => 'Drone & Máy Bay', 'name' => 'Drone Mini Có Camera 1080p Gập Gọn', 'brand' => 'JJRC', 'price' => 1290000, 'qty' => 15,
             'age_min' => 120, 'age_max' => null, 'battery' => 'Pin sạc, bay 15 phút', 'warranty' => 6, 'weight' => 300,
             'skills' => 'Vận động tinh, Tập trung & kiên nhẫn', 'featured' => true,
             'desc' => "Camera 1080p truyền hình ảnh về điện thoại, giữ độ cao tự động nên dễ bay.\nCánh gập vào thân, bỏ vừa túi áo khoác."],

            ['category' => 'Drone & Máy Bay', 'name' => 'Drone Cảm Biến Tay Bay Trong Nhà', 'brand' => 'ToyLand', 'price' => 390000, 'qty' => 32,
             'age_min' => 72, 'age_max' => 144, 'battery' => 'Pin sạc USB, bay 8 phút', 'weight' => 100,
             'skills' => 'Vận động tinh',
             'desc' => "Bật lên tung nhẹ là tự bay, đưa tay dưới đáy là drone bay lên tránh.\nCó lồng bảo vệ quanh cánh quạt, đụng vào tường cũng không hỏng."],

            ['category' => 'Drone & Máy Bay', 'name' => 'Máy Bay Điều Khiển Cánh Bằng Xốp EPP', 'brand' => 'Wltoys', 'price' => 890000, 'qty' => 16,
             'age_min' => 96, 'age_max' => null, 'material' => 'Xốp EPP siêu nhẹ', 'battery' => 'Pin sạc, bay 20 phút', 'warranty' => 6, 'weight' => 400,
             'dims' => 'Sải cánh 75 cm', 'skills' => 'Vận động tinh, Tập trung & kiên nhẫn',
             'desc' => "Thân xốp EPP dẻo, rơi xuống cỏ gần như không hỏng.\nCó chế độ tự cân bằng cho người mới, tắt đi khi đã bay quen."],

            ['category' => 'Drone & Máy Bay', 'name' => 'Trực Thăng Điều Khiển Hợp Kim 3.5 Kênh', 'brand' => 'Syma', 'price' => 690000, 'qty' => 20,
             'age_min' => 84, 'age_max' => null, 'material' => 'Khung hợp kim', 'battery' => 'Pin sạc, bay 8 phút', 'warranty' => 6, 'weight' => 350,
             'skills' => 'Vận động tinh, Tập trung & kiên nhẫn',
             'desc' => "Con quay hồi chuyển giữ thân ổn định, bay trong phòng khách không chao đảo.\nKhung hợp kim chịu va đập tốt hơn hẳn trực thăng nhựa."],

            ['category' => 'Drone & Máy Bay', 'name' => 'Máy Bay Ném Tay Xốp Dạ Quang 2 Chiếc', 'brand' => 'ToyLand', 'price' => 95000, 'qty' => 60,
             'age_min' => 36, 'age_max' => 120, 'material' => 'Xốp EPP', 'weight' => 80, 'dims' => 'Sải cánh 48 cm',
             'skills' => 'Vận động thô',
             'desc' => "Không pin không điều khiển - chỉ ném tay, lượn được 20-30 mét.\nPhát sáng nhẹ trong tối, chơi ở sân chung cư buổi chiều tối rất vui."],

            ['category' => 'Drone & Máy Bay', 'name' => 'Drone Đua Mini Có Đèn LED Nhào Lộn', 'brand' => 'JJRC', 'price' => 590000, 'qty' => 24,
             'age_min' => 96, 'age_max' => null, 'battery' => 'Pin sạc, bay 10 phút', 'warranty' => 6, 'weight' => 150,
             'skills' => 'Vận động tinh, Tập trung & kiên nhẫn',
             'desc' => "Bấm một nút là drone lộn vòng 360 độ trên không.\nBa mức tốc độ, đèn LED đổi màu nên bay buổi tối vẫn thấy rõ."],
        ];
    }

    // ===================== 7. THẾ GIỚI ĐỘNG VẬT =====================

    private static function animals(): array
    {
        return [
            // ---- Mô Hình Động Vật ----
            ['category' => 'Mô Hình Động Vật', 'name' => 'Bộ 12 Mô Hình Động Vật Hoang Dã Schleich', 'brand' => 'Schleich', 'price' => 1190000, 'qty' => 16,
             'age_min' => 36, 'age_max' => 144, 'material' => 'Nhựa PVC không phthalate', 'origin' => 'Đức', 'weight' => 900,
             'skills' => 'Ngôn ngữ, Sáng tạo', 'featured' => true,
             'desc' => "Mười hai con thú sơn tay thủ công, tỉ lệ và màu sắc đúng với con vật thật.\nSư tử, voi, hươu cao cổ, tê giác, hổ, gấu... mỗi con một tư thế riêng."],

            ['category' => 'Mô Hình Động Vật', 'name' => 'Bộ Nông Trại 20 Con Vật Kèm Chuồng', 'brand' => 'ToyLand', 'price' => 420000, 'qty' => 30,
             'age_min' => 24, 'age_max' => 96, 'weight' => 800,
             'skills' => 'Ngôn ngữ, Sáng tạo',
             'desc' => "Bò, lợn, gà, vịt, cừu, ngựa kèm chuồng, hàng rào và máy kéo mini.\nDạy bé gọi tên con vật và tiếng kêu của chúng."],

            ['category' => 'Mô Hình Động Vật', 'name' => 'Mô Hình Voi Châu Phi Cỡ Lớn Schleich', 'brand' => 'Schleich', 'price' => 350000, 'qty' => 22,
             'age_min' => 36, 'age_max' => 144, 'material' => 'Nhựa PVC không phthalate', 'origin' => 'Đức', 'weight' => 350, 'dims' => 'Dài 18 cm',
             'skills' => 'Ngôn ngữ',
             'desc' => "Da voi tạo nếp nhăn chi tiết tới từng đường, ngà và tai đúng tỉ lệ thật.\nNặng tay và đứng rất vững, không đổ khi bé đẩy."],

            ['category' => 'Mô Hình Động Vật', 'name' => 'Bộ Thú Rừng Nhiệt Đới 15 Con Mini', 'brand' => 'ToyLand', 'price' => 280000, 'qty' => 35,
             'age_min' => 36, 'age_max' => 120, 'weight' => 500,
             'skills' => 'Ngôn ngữ, Sáng tạo',
             'desc' => "Khỉ, vẹt, báo đốm, trăn, lười và các loài rừng mưa khác.\nKèm bản đồ nhỏ in nơi sinh sống của từng loài."],

            ['category' => 'Mô Hình Động Vật', 'name' => 'Bộ Động Vật Bắc Cực 10 Con Kèm Tảng Băng', 'brand' => 'ToyLand', 'price' => 320000, 'qty' => 28,
             'age_min' => 36, 'age_max' => 120, 'weight' => 600,
             'skills' => 'Ngôn ngữ, Sáng tạo',
             'desc' => "Gấu trắng, chim cánh cụt, hải cẩu, cá voi kèm các mảng băng nhựa trong.\nThả vào chậu nước là thành một vùng cực thu nhỏ."],

            ['category' => 'Mô Hình Động Vật', 'name' => 'Bộ Thú Cưng 8 Con Kèm Phụ Kiện Chăm Sóc', 'brand' => 'ToyLand', 'price' => 290000, 'qty' => 32,
             'age_min' => 36, 'age_max' => 108, 'weight' => 450,
             'skills' => 'Kỹ năng xã hội, Sáng tạo',
             'desc' => "Chó, mèo, thỏ, hamster kèm bát ăn, lược chải và dây dắt mini.\nBé tập chăm sóc trước khi nhà quyết định nuôi thú thật."],

            // ---- Khủng Long ----
            ['category' => 'Khủng Long', 'name' => 'Bộ 12 Mô Hình Khủng Long Kèm Thảm Chơi', 'brand' => 'Jurassic World', 'price' => 490000, 'qty' => 30,
             'age_min' => 36, 'age_max' => 120, 'character' => 'Jurassic World', 'gender' => 'boy', 'weight' => 900,
             'skills' => 'Ngôn ngữ, Sáng tạo', 'featured' => true,
             'desc' => "Mười hai loài khủng long kèm thảm rừng nguyên thủy, cây cọ và tảng đá.\nMỗi con có tên khoa học in dưới bụng - bé học thuộc lúc nào không hay."],

            ['category' => 'Khủng Long', 'name' => 'Khủng Long T-Rex Phun Khói Biết Đi', 'brand' => 'ToyLand', 'price' => 620000, 'qty' => 22,
             'age_min' => 36, 'age_max' => 108, 'gender' => 'boy', 'battery' => '4 viên AA (mua riêng)', 'weight' => 1100,
             'skills' => 'Sáng tạo',
             'desc' => "T-Rex dài 40 cm tự bước đi, gầm vang, mắt đỏ và phun sương từ miệng.\nĐầu ngửa lên khi gầm, chuyển động khá giống phim."],

            ['category' => 'Khủng Long', 'name' => 'Bộ Khai Quật Hóa Thạch Khủng Long 3 Khối Cỡ Lớn', 'brand' => 'ToyLand', 'price' => 250000, 'qty' => 40,
             'age_min' => 60, 'age_max' => 144, 'weight' => 700,
             'skills' => 'Tập trung & kiên nhẫn, Vận động tinh',
             'desc' => "Ba khối thạch cao cỡ lớn giấu ba bộ xương khác nhau, bé đục bằng búa và chổi kèm theo.\nĐào xong ghép lại thành bộ xương hoàn chỉnh - mất khoảng một tiếng."],

            ['category' => 'Khủng Long', 'name' => 'Trứng Khủng Long Nở Trong Nước', 'brand' => 'ToyLand', 'price' => 120000, 'qty' => 60,
             'age_min' => 48, 'age_max' => 120, 'weight' => 300,
             'skills' => 'Tập trung & kiên nhẫn', 'safety' => 'Không cho vào miệng. Ngâm trong chậu riêng, không ngâm trong bồn tắm.',
             'desc' => "Hộp 12 quả trứng, ngâm nước 24-48 tiếng là vỏ nứt và khủng long chui ra.\nBài học nhỏ về sự chờ đợi cho bé vốn thiếu kiên nhẫn."],

            ['category' => 'Khủng Long', 'name' => 'Bộ Khủng Long Điều Khiển Từ Xa Biết Gầm', 'brand' => 'Jurassic World', 'price' => 790000, 'qty' => 18,
             'age_min' => 48, 'age_max' => 120, 'character' => 'Jurassic World', 'gender' => 'boy', 'battery' => 'Pin sạc, chơi 30 phút', 'warranty' => 6, 'weight' => 1200,
             'skills' => 'Vận động tinh, Sáng tạo',
             'desc' => "Điều khiển cho khủng long tiến lùi, quay đầu, gầm và nháy mắt.\nChân bước thật chứ không phải bánh xe giấu dưới bụng."],

            ['category' => 'Khủng Long', 'name' => 'Mô Hình Công Viên Khủng Long Có Núi Lửa', 'brand' => 'ToyLand', 'price' => 690000, 'qty' => 20,
             'age_min' => 48, 'age_max' => 120, 'gender' => 'boy', 'weight' => 1500,
             'skills' => 'Sáng tạo, Kỹ năng xã hội',
             'desc' => "Núi lửa phun khói, hàng rào điện, cổng công viên và 8 con khủng long.\nDựng được cả một cảnh phim trên bàn."],

            // ---- Thú Bông ----
            ['category' => 'Thú Bông', 'name' => 'Gấu Bông Teddy Lông Xoăn 60cm', 'brand' => 'ToyLand', 'price' => 420000, 'qty' => 35,
             'age_min' => 0, 'age_max' => null, 'material' => 'Vải lông xoăn + bông PP', 'weight' => 800, 'dims' => 'Cao 60 cm',
             'safety' => 'Không có chi tiết rời, an toàn cho bé sơ sinh.', 'skills' => 'Kỹ năng xã hội', 'featured' => true,
             'desc' => "Gấu Teddy cổ điển lông xoăn mềm, nơ cổ tháo ra giặt được.\nMắt mũi đều thêu chỉ, không dùng hạt nhựa gắn."],

            ['category' => 'Thú Bông', 'name' => 'Thú Bông Gấu Trúc Ôm Ngủ 45cm', 'brand' => 'ToyLand', 'price' => 350000, 'qty' => 38,
             'age_min' => 0, 'age_max' => null, 'material' => 'Vải nhung + bông PP', 'weight' => 500,
             'safety' => 'Không có chi tiết rời, an toàn cho bé sơ sinh.', 'skills' => 'Kỹ năng xã hội',
             'desc' => "Thân dài ôm vừa tay, bé kẹp giữa hai chân ngủ rất ngon.\nVải nhung hai mặt đen trắng, giặt máy không phai."],

            ['category' => 'Thú Bông', 'name' => 'Thú Bông Kỳ Lân Cầu Vồng Có Cánh', 'brand' => 'ToyLand', 'price' => 390000, 'qty' => 34,
             'age_min' => 12, 'age_max' => null, 'gender' => 'girl', 'material' => 'Vải nhung + bông PP', 'weight' => 450,
             'skills' => 'Kỹ năng xã hội, Sáng tạo',
             'desc' => "Bờm và đuôi bảy màu, sừng kim tuyến và đôi cánh mềm sau lưng.\nMón thú bông được các bé gái chọn nhiều nhất ở ToyLand."],

            ['category' => 'Thú Bông', 'name' => 'Thú Bông Mèo Nằm Dài 80cm Ôm Ngủ', 'brand' => 'ToyLand', 'price' => 490000, 'qty' => 26,
             'age_min' => 12, 'age_max' => null, 'material' => 'Vải nhung + bông PP', 'weight' => 900, 'dims' => 'Dài 80 cm',
             'skills' => 'Kỹ năng xã hội',
             'desc' => "Dáng mèo nằm duỗi dài, dùng thay gối ôm cho bé tiểu học.\nRuột bông PP đàn hồi, vỗ lại là phồng như mới."],

            ['category' => 'Thú Bông', 'name' => 'Bộ 4 Thú Bông Mini Móc Khóa Ba Lô', 'brand' => 'ToyLand', 'price' => 180000, 'qty' => 50,
             'age_min' => 36, 'age_max' => null, 'material' => 'Vải nhung + bông PP', 'weight' => 150,
             'skills' => 'Kỹ năng xã hội',
             'desc' => "Bốn con thú cỡ 10 cm có móc gắn vào ba lô hay hộp bút.\nMón quà nhỏ dễ tặng khi bé đi sinh nhật bạn."],

            ['category' => 'Thú Bông', 'name' => 'Thú Bông Voi Xám Kèm Chăn Nhỏ', 'brand' => 'ToyLand', 'price' => 320000, 'qty' => 30,
             'age_min' => 0, 'age_max' => 60, 'material' => 'Vải cotton + bông PP', 'weight' => 400,
             'safety' => 'Không có chi tiết rời, an toàn cho bé sơ sinh.', 'skills' => 'Kỹ năng xã hội',
             'desc' => "Voi bông kèm chiếc chăn mỏng cùng chất liệu, bé vừa ôm vừa đắp.\nCotton thoáng, hợp với thời tiết nóng ẩm ở Việt Nam."],

            // ---- Đại Dương & Côn Trùng ----
            ['category' => 'Đại Dương & Côn Trùng', 'name' => 'Bộ 16 Mô Hình Sinh Vật Biển Sâu', 'brand' => 'ToyLand', 'price' => 350000, 'qty' => 32,
             'age_min' => 36, 'age_max' => 120, 'weight' => 700,
             'skills' => 'Ngôn ngữ, Sáng tạo', 'featured' => true,
             'desc' => "Cá mập đầu búa, cá mập voi, mực khổng lồ, cá đèn lồng và nhiều loài biển sâu khác.\nNhựa đặc kín nước, thả vào bồn tắm chơi thoải mái."],

            ['category' => 'Đại Dương & Côn Trùng', 'name' => 'Bộ Mô Hình Côn Trùng 12 Con Kèm Kính Lúp', 'brand' => 'ToyLand', 'price' => 280000, 'qty' => 30,
             'age_min' => 48, 'age_max' => 120, 'weight' => 400,
             'skills' => 'Ngôn ngữ, Tập trung & kiên nhẫn',
             'desc' => "Bọ cánh cứng, bướm, chuồn chuồn, bọ ngựa... kèm kính lúp phóng đại 3 lần.\nSoi kỹ thấy được cả gân cánh - chi tiết hơn bé tưởng nhiều."],

            ['category' => 'Đại Dương & Côn Trùng', 'name' => 'Bể Cá Đồ Chơi Có Cá Bơi Và Đèn', 'brand' => 'ToyLand', 'price' => 450000, 'qty' => 24,
             'age_min' => 36, 'age_max' => 96, 'battery' => '3 viên AA (mua riêng)', 'weight' => 900,
             'skills' => 'Tập trung & kiên nhẫn',
             'desc' => "Đổ nước vào, bật lên là đàn cá nhựa bơi vòng quanh dưới ánh đèn đổi màu.\nBé ngồi ngắm rất lâu mà không cần nuôi cá thật."],

            ['category' => 'Đại Dương & Côn Trùng', 'name' => 'Bộ Nuôi Kiến Quan Sát Gel Trong Suốt', 'brand' => 'ToyLand', 'price' => 320000, 'qty' => 22,
             'age_min' => 72, 'age_max' => 168, 'weight' => 600,
             'skills' => 'Tập trung & kiên nhẫn, Ngôn ngữ', 'safety' => 'Không tự ý bắt kiến ngoài tự nhiên thả vào khi chưa hỏi người lớn.',
             'desc' => "Hộp gel dinh dưỡng trong suốt để quan sát kiến đào đường hầm.\nMột dự án quan sát kéo dài nhiều tuần, rất hợp làm bài tập khoa học."],

            ['category' => 'Đại Dương & Côn Trùng', 'name' => 'Bộ Câu Cá Nam Châm Kèm Cần Câu', 'brand' => 'ToyLand', 'price' => 190000, 'qty' => 45,
             'age_min' => 24, 'age_max' => 72, 'weight' => 400,
             'skills' => 'Vận động tinh, Toán học sớm',
             'desc' => "Hồ nhựa, 26 con cá gắn nam châm in chữ cái và 2 cần câu.\nCâu được con nào thì đọc chữ trên lưng con đó - vừa chơi vừa học."],

            ['category' => 'Đại Dương & Côn Trùng', 'name' => 'Mô Hình Cá Mập Trắng Khổng Lồ 35cm', 'brand' => 'Schleich', 'price' => 420000, 'qty' => 20,
             'age_min' => 36, 'age_max' => 144, 'material' => 'Nhựa PVC không phthalate', 'origin' => 'Đức', 'weight' => 500, 'dims' => 'Dài 35 cm',
             'skills' => 'Ngôn ngữ',
             'desc' => "Cá mập trắng sơn tay, hàm răng tạo hình từng chiếc một.\nMiệng há cố định, không cắn vào tay bé."],
        ];
    }

    // ===================== 8. ĐỒ DÙNG HỌC TẬP =====================

    private static function study(): array
    {
        return [
            // ---- Bảng & Bút Viết ----
            ['category' => 'Bảng & Bút Viết', 'name' => 'Bảng Vẽ Điện Tử LCD 12 Inch Xóa Một Chạm', 'brand' => 'ToyLand', 'price' => 290000, 'qty' => 45,
             'age_min' => 36, 'age_max' => 144, 'battery' => 'Pin CR2025 (kèm sẵn)', 'weight' => 250, 'dims' => '12 inch',
             'skills' => 'Sáng tạo, Vận động tinh', 'featured' => true,
             'desc' => "Viết bằng bút từ, bấm một nút là xóa sạch - không mực, không bụi phấn.\nMột viên pin dùng được khoảng 100.000 lần xóa."],

            ['category' => 'Bảng & Bút Viết', 'name' => 'Bảng Từ Hai Mặt Có Chân Đứng', 'brand' => 'ToyLand', 'price' => 690000, 'qty' => 22,
             'age_min' => 36, 'age_max' => 120, 'material' => 'Khung gỗ + mặt từ', 'weight' => 4500, 'dims' => 'Cao 120 cm',
             'skills' => 'Sáng tạo, Ngôn ngữ',
             'desc' => "Một mặt bảng trắng viết bút lông, mặt kia bảng đen viết phấn.\nChỉnh cao thấp theo chiều cao bé, kèm khay đựng bút và nam châm chữ cái."],

            ['category' => 'Bảng & Bút Viết', 'name' => 'Bộ Bút Lông Màu Không Độc 36 Màu', 'brand' => 'Colokit', 'price' => 165000, 'qty' => 50,
             'age_min' => 36, 'age_max' => 144, 'material' => 'Mực gốc nước', 'origin' => 'Việt Nam', 'weight' => 400,
             'skills' => 'Sáng tạo, Vận động tinh',
             'desc' => "Mực gốc nước rửa sạch khỏi da và quần áo bằng nước ấm.\nNgòi bút có nắp chống ngạt theo chuẩn an toàn đồ chơi."],

            ['category' => 'Bảng & Bút Viết', 'name' => 'Bảng Vẽ Nước Thần Kỳ Khổ Lớn', 'brand' => 'ToyLand', 'price' => 220000, 'qty' => 40,
             'age_min' => 18, 'age_max' => 72, 'material' => 'Vải tráng phủ', 'weight' => 500, 'dims' => '100 x 70 cm',
             'skills' => 'Sáng tạo, Vận động tinh',
             'desc' => "Chỉ cần đổ nước vào bút, vẽ lên thảm là hiện màu; khô là tự biến mất.\nKhông mực nên bé vẽ ra sàn ra tường cũng không sao."],

            ['category' => 'Bảng & Bút Viết', 'name' => 'Bộ Bút Sáp Màu Tam Giác 24 Màu', 'brand' => 'Colokit', 'price' => 95000, 'qty' => 60,
             'age_min' => 24, 'age_max' => 96, 'material' => 'Sáp gốc thực vật', 'origin' => 'Việt Nam', 'weight' => 300,
             'skills' => 'Vận động tinh, Sáng tạo',
             'desc' => "Thân tam giác nên bút không lăn khỏi bàn và bé cầm đúng ba ngón tự nhiên.\nSáp gốc thực vật, không mùi hắc."],

            ['category' => 'Bảng & Bút Viết', 'name' => 'Bảng Vẽ Ma Thuật Xóa Bằng Thanh Gạt', 'brand' => 'ToyLand', 'price' => 140000, 'qty' => 48,
             'age_min' => 24, 'age_max' => 84, 'weight' => 350,
             'skills' => 'Vận động tinh, Sáng tạo',
             'desc' => "Bảng bột từ, viết bằng bút nam châm và kéo thanh gạt là sạch.\nKèm 3 con dấu hình, bé nhỏ thích đóng dấu hơn cả vẽ."],

            // ---- Học Chữ & Học Số ----
            ['category' => 'Học Chữ & Học Số', 'name' => 'Bảng Chữ Cái Tiếng Việt Điện Tử Phát Âm', 'brand' => 'ToyLand', 'price' => 350000, 'qty' => 35,
             'age_min' => 24, 'age_max' => 84, 'battery' => '3 viên AA (mua riêng)', 'weight' => 600,
             'skills' => 'Ngôn ngữ, Toán học sớm', 'featured' => true,
             'desc' => "Bấm vào chữ là loa đọc đúng âm tiếng Việt, có cả dấu thanh và vần ghép.\nGiọng đọc chuẩn, không phải giọng máy cứng đơ."],

            ['category' => 'Học Chữ & Học Số', 'name' => 'Bộ Thẻ Học Thông Minh 416 Thẻ Song Ngữ', 'brand' => 'ToyLand', 'price' => 280000, 'qty' => 40,
             'age_min' => 12, 'age_max' => 72, 'material' => 'Giấy bồi phủ màng', 'weight' => 900,
             'skills' => 'Ngôn ngữ',
             'desc' => "416 thẻ chia 16 chủ đề, mỗi thẻ một hình kèm từ tiếng Việt và tiếng Anh.\nGóc bo tròn, phủ màng lau được khi bé làm bẩn."],

            ['category' => 'Học Chữ & Học Số', 'name' => 'Bàn Tính Gỗ Soroban Học Đếm', 'brand' => 'Hape', 'price' => 320000, 'qty' => 30,
             'age_min' => 36, 'age_max' => 108, 'material' => 'Gỗ tự nhiên sơn gốc nước', 'origin' => 'Đức', 'weight' => 700,
             'skills' => 'Toán học sớm, Vận động tinh',
             'desc' => "Bàn tính gỗ 10 hàng hạt màu, học đếm và cộng trừ bằng tay.\nChạm vào và di chuyển được nên con số trở thành thứ sờ thấy."],

            ['category' => 'Học Chữ & Học Số', 'name' => 'Bộ Ghép Chữ Nam Châm Tiếng Việt 100 Mảnh', 'brand' => 'ToyLand', 'price' => 190000, 'qty' => 42,
             'age_min' => 36, 'age_max' => 96, 'pieces' => 100, 'weight' => 400,
             'skills' => 'Ngôn ngữ, Vận động tinh',
             'desc' => "Đủ chữ cái, dấu thanh và các chữ có dấu riêng như ă, â, ê, ô, ơ, ư, đ.\nDán lên tủ lạnh là bé ghép chữ mỗi khi đi ngang qua bếp."],

            ['category' => 'Học Chữ & Học Số', 'name' => 'Đồng Hồ Gỗ Học Xem Giờ Có Số La Mã', 'brand' => 'Hape', 'price' => 250000, 'qty' => 34,
             'age_min' => 48, 'age_max' => 108, 'material' => 'Gỗ tự nhiên sơn gốc nước', 'origin' => 'Đức', 'weight' => 500,
             'skills' => 'Toán học sớm, Tư duy logic',
             'desc' => "Kim giờ kim phút xoay được, mặt số in cả số Ả Rập lẫn số La Mã.\nKèm 12 khối gỗ hình rút ra lắp vào đúng vị trí giờ."],

            ['category' => 'Học Chữ & Học Số', 'name' => 'Máy Tính Bảng Học Tập Song Ngữ Cho Bé', 'brand' => 'ToyLand', 'price' => 420000, 'qty' => 28,
             'age_min' => 36, 'age_max' => 96, 'battery' => 'Pin sạc USB', 'weight' => 400,
             'skills' => 'Ngôn ngữ, Toán học sớm',
             'desc' => "Mười chế độ: học chữ, học số, học từ vựng, nghe nhạc và làm bài kiểm tra nhỏ.\nCó công tắc giới hạn âm lượng để không hại tai bé."],

            // ---- Thí Nghiệm Khoa Học ----
            ['category' => 'Thí Nghiệm Khoa Học', 'name' => 'Bộ Thí Nghiệm Hóa Học 60 Thí Nghiệm An Toàn', 'brand' => 'ToyLand', 'price' => 590000, 'qty' => 24,
             'age_min' => 84, 'age_max' => 168, 'weight' => 1200,
             'skills' => 'Tư duy logic, Tập trung & kiên nhẫn', 'featured' => true,
             'safety' => 'Phải có người lớn ngồi cùng khi bé làm thí nghiệm.',
             'desc' => "Ống nghiệm, giá đỡ, kính bảo hộ và hóa chất đều loại an toàn cho gia đình.\nSáu mươi thí nghiệm: núi lửa baking soda, mực tàng hình, tinh thể muối..."],

            ['category' => 'Thí Nghiệm Khoa Học', 'name' => 'Kính Hiển Vi Trẻ Em Phóng Đại 1200 Lần', 'brand' => 'ToyLand', 'price' => 890000, 'qty' => 18,
             'age_min' => 84, 'age_max' => null, 'material' => 'Nhựa ABS + thấu kính thủy tinh', 'battery' => '2 viên AA (mua riêng)', 'weight' => 1100,
             'skills' => 'Tập trung & kiên nhẫn, Tư duy logic',
             'desc' => "Ba mức phóng đại 100x, 400x và 1200x, đèn LED chiếu từ dưới.\nKèm 12 tiêu bản làm sẵn và bộ dụng cụ để bé tự làm tiêu bản mới."],

            ['category' => 'Thí Nghiệm Khoa Học', 'name' => 'Bộ Trồng Cây Quan Sát Rễ Trong Suốt', 'brand' => 'ToyLand', 'price' => 220000, 'qty' => 35,
             'age_min' => 60, 'age_max' => 144, 'weight' => 500,
             'skills' => 'Tập trung & kiên nhẫn, Ngôn ngữ',
             'desc' => "Chậu trong suốt để nhìn rễ mọc dài ra mỗi ngày, kèm hạt đậu và giá thể.\nCó sổ nhật ký để bé ghi chiều cao cây từng ngày."],

            ['category' => 'Thí Nghiệm Khoa Học', 'name' => 'Kính Thiên Văn Khúc Xạ 50mm Cho Bé', 'brand' => 'ToyLand', 'price' => 1290000, 'qty' => 12,
             'age_min' => 96, 'age_max' => null, 'material' => 'Nhôm + thấu kính thủy tinh', 'weight' => 2200,
             'skills' => 'Tập trung & kiên nhẫn, Tư duy logic',
             'desc' => "Vật kính 50 mm, đủ để nhìn rõ các hố trên Mặt Trăng và 4 vệ tinh của Sao Mộc.\nChân tam giác nhôm chỉnh cao thấp, kèm bản đồ sao in sẵn."],

            ['category' => 'Thí Nghiệm Khoa Học', 'name' => 'Bộ Thí Nghiệm Núi Lửa Và Khảo Cổ', 'brand' => 'ToyLand', 'price' => 290000, 'qty' => 30,
             'age_min' => 60, 'age_max' => 144, 'weight' => 800,
             'skills' => 'Tư duy logic, Sáng tạo',
             'desc' => "Tự đắp mô hình núi lửa bằng thạch cao rồi cho phun trào bằng giấm và baking soda.\nPhản ứng diễn ra ngay trước mắt - lời giải thích về axit và bazơ đi kèm."],

            ['category' => 'Thí Nghiệm Khoa Học', 'name' => 'Bộ Thí Nghiệm Điện Và Nam Châm 30 Bài', 'brand' => 'Gigo', 'price' => 520000, 'qty' => 22,
             'age_min' => 84, 'age_max' => 168, 'pieces' => 45, 'weight' => 700,
             'skills' => 'Tư duy logic, Tập trung & kiên nhẫn',
             'desc' => "Nam châm, cuộn dây, la bàn và mô tơ nhỏ cho 30 bài thí nghiệm.\nBé tự quấn được một chiếc mô tơ đơn giản và thấy nó quay thật."],

            // ---- Dụng Cụ Mỹ Thuật ----
            ['category' => 'Dụng Cụ Mỹ Thuật', 'name' => 'Hộp Màu Vẽ Tổng Hợp 150 Món', 'brand' => 'Colokit', 'price' => 420000, 'qty' => 32,
             'age_min' => 48, 'age_max' => 168, 'origin' => 'Việt Nam', 'weight' => 1500,
             'skills' => 'Sáng tạo, Vận động tinh', 'featured' => true,
             'desc' => "Chì màu, sáp, bút lông, màu nước, cọ và giấy vẽ trong một va li nhựa có quai.\nMón quà sinh nhật gọn gàng cho bé thích vẽ."],

            ['category' => 'Dụng Cụ Mỹ Thuật', 'name' => 'Bộ Màu Nước 24 Màu Kèm Cọ Và Giấy', 'brand' => 'Colokit', 'price' => 180000, 'qty' => 45,
             'age_min' => 48, 'age_max' => 168, 'material' => 'Màu gốc nước không độc', 'origin' => 'Việt Nam', 'weight' => 500,
             'skills' => 'Sáng tạo, Vận động tinh',
             'desc' => "Màu bánh 24 ô kèm 3 cọ cỡ khác nhau và 20 tờ giấy vẽ định lượng dày.\nRửa cọ bằng nước lã, không cần dung môi."],

            ['category' => 'Dụng Cụ Mỹ Thuật', 'name' => 'Giá Vẽ Gỗ Đứng Hai Mặt Cho Bé', 'brand' => 'Hape', 'price' => 890000, 'qty' => 18,
             'age_min' => 36, 'age_max' => 120, 'material' => 'Gỗ thông sơn gốc nước', 'origin' => 'Đức', 'weight' => 4000, 'dims' => 'Cao 105 cm',
             'skills' => 'Sáng tạo, Vận động thô',
             'desc' => "Một mặt kẹp giấy vẽ, một mặt bảng đen; có khay đựng màu và cuộn giấy phía trên.\nVẽ đứng giúp bé cử động cả cánh tay chứ không chỉ cổ tay."],

            ['category' => 'Dụng Cụ Mỹ Thuật', 'name' => 'Bộ Tranh Cát Tự Dán 12 Bức', 'brand' => 'ToyLand', 'price' => 150000, 'qty' => 50,
             'age_min' => 36, 'age_max' => 108, 'weight' => 600,
             'skills' => 'Vận động tinh, Tập trung & kiên nhẫn',
             'desc' => "Bóc từng mảng keo trên tranh rồi rắc cát màu lên, không cần hồ dán.\nHoàn thành một bức mất khoảng 15 phút - vừa đủ sức tập trung của bé."],

            ['category' => 'Dụng Cụ Mỹ Thuật', 'name' => 'Bộ Tô Tượng Thạch Cao 10 Tượng', 'brand' => 'ToyLand', 'price' => 190000, 'qty' => 40,
             'age_min' => 36, 'age_max' => 120, 'weight' => 1200,
             'skills' => 'Sáng tạo, Vận động tinh',
             'desc' => "Mười tượng thạch cao hình con vật kèm màu nước và cọ.\nTô xong để khô là có món quà tự tay bé làm để tặng ông bà."],

            ['category' => 'Dụng Cụ Mỹ Thuật', 'name' => 'Bộ Làm Vòng Cổ Đất Sét Nung Lò Vi Sóng', 'brand' => 'ToyLand', 'price' => 260000, 'qty' => 28,
             'age_min' => 84, 'age_max' => 168, 'gender' => 'girl', 'weight' => 500,
             'skills' => 'Sáng tạo, Vận động tinh', 'safety' => 'Bước nung phải do người lớn thực hiện.',
             'desc' => "Nặn hình rồi nung 3 phút trong lò vi sóng là đất cứng lại như gốm.\nKèm dây da, khóa và sơn bóng để hoàn thiện món trang sức."],
        ];
    }

    // ===================== 9. ĐỒ DÙNG & ĐỒ CHƠI CHO BÉ =====================

    private static function baby(): array
    {
        return [
            // ---- Đồ Chơi Sơ Sinh ----
            ['category' => 'Đồ Chơi Sơ Sinh', 'name' => 'Treo Nôi Nhạc Xoay Có Đèn Ngủ', 'brand' => 'Fisher-Price', 'price' => 690000, 'qty' => 26,
             'age_min' => 0, 'age_max' => 12, 'battery' => '3 viên AA (mua riêng)', 'weight' => 800,
             'safety' => 'Lắp cách tầm với của bé ít nhất 30 cm.', 'skills' => 'Ngôn ngữ', 'featured' => true,
             'desc' => "Bốn thú bông xoay tròn theo 12 bản nhạc ru, đèn ngủ vàng dịu.\nCó hẹn giờ tự tắt sau 20 phút để không làm bé tỉnh giấc."],

            ['category' => 'Đồ Chơi Sơ Sinh', 'name' => 'Sách Vải Mềm Cho Bé Sơ Sinh 6 Trang', 'brand' => 'ToyLand', 'price' => 150000, 'qty' => 50,
             'age_min' => 0, 'age_max' => 24, 'material' => 'Vải cotton in mực an toàn', 'weight' => 120,
             'safety' => 'Không có chi tiết rời, an toàn cho bé sơ sinh.', 'skills' => 'Ngôn ngữ, Vận động tinh',
             'desc' => "Sáu trang vải in hình con vật, có trang kêu sột soạt và trang gương an toàn.\nBé gặm bao nhiêu cũng được, giặt máy rồi phơi là sạch."],

            ['category' => 'Đồ Chơi Sơ Sinh', 'name' => 'Gương Treo Cũi Không Vỡ Cho Bé', 'brand' => 'ToyLand', 'price' => 190000, 'qty' => 40,
             'age_min' => 0, 'age_max' => 18, 'material' => 'Nhựa acrylic tráng bạc', 'weight' => 200,
             'safety' => 'Không có chi tiết rời, an toàn cho bé sơ sinh.', 'skills' => 'Kỹ năng xã hội',
             'desc' => "Gương acrylic không vỡ, bé nằm sấp nhìn thấy mặt mình và thích thú ngóc đầu lên.\nMột cách dụ bé chịu tập nằm sấp mà không khóc."],

            ['category' => 'Đồ Chơi Sơ Sinh', 'name' => 'Bộ 6 Thú Bông Treo Xe Đẩy', 'brand' => 'ToyLand', 'price' => 220000, 'qty' => 38,
             'age_min' => 0, 'age_max' => 18, 'material' => 'Vải cotton + bông PP', 'weight' => 250,
             'safety' => 'Không có chi tiết rời, an toàn cho bé sơ sinh.', 'skills' => 'Vận động tinh',
             'desc' => "Sáu con thú có dây vòng gắn vào xe đẩy, nôi hay ghế ăn.\nMỗi con một kiểu tiếng: lục lạc, kêu bíp, sột soạt."],

            ['category' => 'Đồ Chơi Sơ Sinh', 'name' => 'Đồ Chơi Vòng Xếp Chồng 7 Tầng Cầu Vồng', 'brand' => 'Fisher-Price', 'price' => 280000, 'qty' => 42,
             'age_min' => 6, 'age_max' => 36, 'weight' => 400,
             'safety' => 'Vòng cỡ lớn, an toàn cho bé dưới 3 tuổi.', 'skills' => 'Vận động tinh, Toán học sớm',
             'desc' => "Bảy vòng to nhỏ khác nhau, xếp đúng thứ tự thành hình tháp cầu vồng.\nMón kinh điển dạy bé khái niệm to - nhỏ trước cả khi biết nói."],

            ['category' => 'Đồ Chơi Sơ Sinh', 'name' => 'Thảm Nằm Sấp Có Gối Kê Ngực', 'brand' => 'ToyLand', 'price' => 390000, 'qty' => 30,
             'age_min' => 0, 'age_max' => 12, 'material' => 'Vải cotton + mút', 'weight' => 600,
             'safety' => 'Luôn có người lớn ngồi cạnh khi bé nằm sấp.', 'skills' => 'Vận động thô',
             'desc' => "Gối kê ngực giúp bé ngóc đầu dễ hơn, xung quanh là các món treo để bé với.\nTập nằm sấp mỗi ngày giúp bé cứng cổ và lật sớm hơn."],

            // ---- Xúc Xắc & Gặm Nướu ----
            ['category' => 'Xúc Xắc & Gặm Nướu', 'name' => 'Bộ Gặm Nướu Silicone Hình Hoa Quả', 'brand' => 'ToyLand', 'price' => 160000, 'qty' => 55,
             'age_min' => 3, 'age_max' => 18, 'material' => 'Silicone y tế', 'weight' => 120,
             'safety' => 'Tiệt trùng bằng nước sôi trước lần dùng đầu tiên.', 'skills' => 'Vận động tinh', 'featured' => true,
             'desc' => "Silicone y tế không BPA, bề mặt có gai massage nướu khi bé mọc răng.\nBỏ tủ lạnh 15 phút trước khi cho bé gặm để làm dịu cơn ngứa lợi."],

            ['category' => 'Xúc Xắc & Gặm Nướu', 'name' => 'Xúc Xắc Gỗ Hình Con Vật 5 Món', 'brand' => 'Hape', 'price' => 290000, 'qty' => 35,
             'age_min' => 3, 'age_max' => 24, 'material' => 'Gỗ tự nhiên sơn gốc nước', 'origin' => 'Đức', 'weight' => 250,
             'safety' => 'Không có chi tiết rời, an toàn cho bé sơ sinh.', 'skills' => 'Vận động tinh, Ngôn ngữ',
             'desc' => "Năm chiếc xúc xắc gỗ, mỗi chiếc một hình con vật và một kiểu tiếng kêu.\nSơn gốc nước đạt chuẩn châu Âu, bé ngậm không sao."],

            ['category' => 'Xúc Xắc & Gặm Nướu', 'name' => 'Vòng Gặm Nướu Làm Mát Chứa Nước', 'brand' => 'ToyLand', 'price' => 120000, 'qty' => 60,
             'age_min' => 3, 'age_max' => 18, 'material' => 'Nhựa PP + nước tinh khiết', 'weight' => 90,
             'safety' => 'Không để ngăn đá, chỉ để ngăn mát.', 'skills' => 'Vận động tinh',
             'desc' => "Bên trong chứa nước tinh khiết, để ngăn mát rồi cho bé gặm sẽ mát lợi rất lâu.\nTay cầm to bản, bé nhỏ cũng nắm chắc."],

            ['category' => 'Xúc Xắc & Gặm Nướu', 'name' => 'Lục Lạc Tay Chân Cho Bé 4 Món', 'brand' => 'ToyLand', 'price' => 140000, 'qty' => 50,
             'age_min' => 0, 'age_max' => 12, 'material' => 'Vải cotton + bông PP', 'weight' => 100,
             'safety' => 'Không có chi tiết rời, an toàn cho bé sơ sinh.', 'skills' => 'Vận động thô, Vận động tinh',
             'desc' => "Đeo vào cổ tay và cổ chân, bé cựa quậy là có tiếng kêu.\nGiúp bé phát hiện ra tay chân của mình - cột mốc đầu đời đáng nhớ."],

            ['category' => 'Xúc Xắc & Gặm Nướu', 'name' => 'Bộ Chuỗi Vòng Nối Nhựa 24 Mắt', 'brand' => 'ToyLand', 'price' => 110000, 'qty' => 55,
             'age_min' => 3, 'age_max' => 24, 'weight' => 200,
             'safety' => 'Mắt vòng cỡ lớn, an toàn cho bé dưới 3 tuổi.', 'skills' => 'Vận động tinh',
             'desc' => "Nối thành chuỗi dài treo lên xe đẩy hay tháo rời cho bé tập cầm nắm.\nNhiều màu tươi, giúp bé tập nhìn theo vật chuyển động."],

            ['category' => 'Xúc Xắc & Gặm Nướu', 'name' => 'Gặm Nướu Bàn Chải Silicone Tập Đánh Răng', 'brand' => 'ToyLand', 'price' => 95000, 'qty' => 60,
             'age_min' => 6, 'age_max' => 30, 'material' => 'Silicone y tế', 'weight' => 60,
             'safety' => 'Tiệt trùng bằng nước sôi trước lần dùng đầu tiên.', 'skills' => 'Vận động tinh',
             'desc' => "Vừa là đồ gặm nướu vừa là bàn chải đầu tiên, có vành chặn không cho thọc sâu.\nTập thói quen đánh răng từ trước khi bé mọc đủ răng."],

            // ---- Xe Tập Đi & Thảm Chơi ----
            ['category' => 'Xe Tập Đi & Thảm Chơi', 'name' => 'Xe Đẩy Tập Đi Gỗ Có Khối Xếp Hình', 'brand' => 'Hape', 'price' => 1190000, 'qty' => 18,
             'age_min' => 9, 'age_max' => 36, 'material' => 'Gỗ tự nhiên sơn gốc nước', 'origin' => 'Đức', 'weight' => 4200,
             'skills' => 'Vận động thô, Vận động tinh', 'featured' => true,
             'desc' => "Xe gỗ chắc chắn để bé vịn tập đi, thùng xe chứa 30 khối gỗ xếp hình.\nBánh có vòng cao su hãm tốc, xe không trôi tuột khi bé đẩy mạnh."],

            ['category' => 'Xe Tập Đi & Thảm Chơi', 'name' => 'Thảm Xốp Ghép 12 Tấm Có Chữ Số', 'brand' => 'ToyLand', 'price' => 320000, 'qty' => 40,
             'age_min' => 0, 'age_max' => 72, 'material' => 'Xốp EVA không mùi', 'weight' => 2000, 'dims' => '30 x 30 cm mỗi tấm',
             'skills' => 'Vận động thô, Toán học sớm',
             'desc' => "Mười hai tấm xốp ghép lại thành 1.08 m², dày 1 cm êm chân.\nSố ở giữa tháo ra được, vừa là thảm vừa là đồ chơi ghép hình."],

            ['category' => 'Xe Tập Đi & Thảm Chơi', 'name' => 'Thảm Chơi Nhạc Đạp Chân Cho Bé', 'brand' => 'Fisher-Price', 'price' => 790000, 'qty' => 22,
             'age_min' => 0, 'age_max' => 24, 'battery' => '3 viên AA (mua riêng)', 'weight' => 1200,
             'skills' => 'Vận động thô, Ngôn ngữ',
             'desc' => "Bé nằm ngửa đạp chân vào bàn phím là phát ra nhạc và đèn nháy.\nGập lên thành đàn piano khi bé lớn hơn và biết ngồi."],

            ['category' => 'Xe Tập Đi & Thảm Chơi', 'name' => 'Xe Tập Đi Đa Năng Có Mái Che', 'brand' => 'Broller', 'price' => 890000, 'qty' => 20,
             'age_min' => 6, 'age_max' => 18, 'warranty' => 6, 'weight' => 5000,
             'safety' => 'Không dùng gần cầu thang. Luôn có người lớn trông chừng.', 'skills' => 'Vận động thô',
             'desc' => "Ba nấc chiều cao, bánh xoay 360 độ và bàn đồ chơi có nhạc phía trước.\nKhung thép chân đế rộng, khó lật khi bé đẩy mạnh."],

            ['category' => 'Xe Tập Đi & Thảm Chơi', 'name' => 'Thảm Chơi Gấp Gọn Hai Mặt Khổ Lớn', 'brand' => 'ToyLand', 'price' => 690000, 'qty' => 24,
             'age_min' => 0, 'age_max' => 72, 'material' => 'Xốp XPE không mùi', 'weight' => 2500, 'dims' => '200 x 180 x 1 cm',
             'skills' => 'Vận động thô',
             'desc' => "Hai mặt hai hình khác nhau, chống thấm nên bé làm đổ sữa chỉ cần lau.\nGấp gọn thành tấm nhỏ dựng sau cánh tủ."],

            ['category' => 'Xe Tập Đi & Thảm Chơi', 'name' => 'Xe Đẩy Tập Đi Hình Ô Tô Có Vô Lăng', 'brand' => 'ToyLand', 'price' => 520000, 'qty' => 28,
             'age_min' => 9, 'age_max' => 30, 'battery' => '2 viên AA (mua riêng)', 'weight' => 3200,
             'skills' => 'Vận động thô, Sáng tạo',
             'desc' => "Bảng điều khiển có vô lăng xoay, còi, chìa khóa vặn và cần số gạt được.\nBé vừa tập đi vừa nghịch một lúc rất lâu ở phần vô lăng."],

            // ---- Đồ Chơi Nhà Tắm ----
            ['category' => 'Đồ Chơi Nhà Tắm', 'name' => 'Bộ Vịt Vàng Nổi Nước 10 Con', 'brand' => 'ToyLand', 'price' => 120000, 'qty' => 60,
             'age_min' => 6, 'age_max' => 60, 'material' => 'Nhựa PVC không phthalate', 'weight' => 200,
             'safety' => 'Không có lỗ thoát nước nên không bị mốc bên trong.', 'skills' => 'Vận động tinh', 'featured' => true,
             'desc' => "Mười chú vịt vàng nổi cân bằng, bóp kêu chít chít.\nThân đúc liền không có lỗ nên nước không vào trong gây mốc - khác hẳn vịt rẻ tiền."],

            ['category' => 'Đồ Chơi Nhà Tắm', 'name' => 'Bộ Cốc Xếp Chồng Lọc Nước 8 Cái', 'brand' => 'Hape', 'price' => 190000, 'qty' => 45,
             'age_min' => 6, 'age_max' => 48, 'origin' => 'Đức', 'weight' => 250,
             'safety' => 'Cốc cỡ lớn, an toàn cho bé dưới 3 tuổi.', 'skills' => 'Vận động tinh, Toán học sớm',
             'desc' => "Tám cốc to nhỏ dần, đáy đục lỗ khác nhau nên nước chảy ra thành các kiểu mưa.\nXếp chồng lên hoặc lồng vào nhau - hai cách chơi trong một bộ."],

            ['category' => 'Đồ Chơi Nhà Tắm', 'name' => 'Bộ Chữ Cái Xốp Dính Tường Nhà Tắm', 'brand' => 'ToyLand', 'price' => 140000, 'qty' => 48,
             'age_min' => 24, 'age_max' => 84, 'material' => 'Xốp EVA', 'weight' => 300,
             'skills' => 'Ngôn ngữ, Vận động tinh',
             'desc' => "Chữ cái và số bằng xốp, nhúng nước là dính lên tường gạch men.\nGiờ tắm thành giờ ghép chữ, bé không đòi ra sớm nữa."],

            ['category' => 'Đồ Chơi Nhà Tắm', 'name' => 'Vòi Sen Đồ Chơi Chạy Pin Cho Bé', 'brand' => 'ToyLand', 'price' => 250000, 'qty' => 35,
             'age_min' => 12, 'age_max' => 60, 'battery' => '2 viên AA (mua riêng)', 'weight' => 350,
             'safety' => 'Hộp pin chống nước, vẫn nên lau khô sau khi chơi.', 'skills' => 'Vận động tinh, Kỹ năng xã hội',
             'desc' => "Thả vào chậu là máy bơm hút nước phun ra như vòi sen thật.\nBé tự tắm cho búp bê - bước đệm để bé chịu gội đầu."],

            ['category' => 'Đồ Chơi Nhà Tắm', 'name' => 'Bộ Tàu Thủy Và Bánh Xe Nước Gắn Tường', 'brand' => 'Hape', 'price' => 390000, 'qty' => 28,
             'age_min' => 18, 'age_max' => 72, 'origin' => 'Đức', 'weight' => 500,
             'skills' => 'Tư duy logic, Vận động tinh',
             'desc' => "Giác hút gắn lên tường, đổ nước từ trên xuống là các bánh xe quay dây chuyền.\nBé thấy được nước làm quay bánh xe như thế nào."],

            ['category' => 'Đồ Chơi Nhà Tắm', 'name' => 'Bộ Cá Bơi Chạy Pin Kèm Vợt Vớt', 'brand' => 'ToyLand', 'price' => 180000, 'qty' => 42,
             'age_min' => 18, 'age_max' => 72, 'battery' => '2 viên AAA (kèm sẵn)', 'weight' => 300,
             'skills' => 'Vận động tinh',
             'desc' => "Ba con cá tự bơi trong chậu, bé dùng vợt vớt lên.\nVừa chơi vừa luyện phối hợp tay - mắt mà không nhận ra đang học."],
        ];
    }

    // ===================== 10. ĐỒ CHƠI VẬN ĐỘNG NGOÀI TRỜI =====================

    private static function outdoor(): array
    {
        return [
            // ---- Bóng & Thể Thao ----
            ['category' => 'Bóng & Thể Thao', 'name' => 'Bộ Khung Thành Bóng Đá Mini Cho Bé', 'brand' => 'ToyLand', 'price' => 420000, 'qty' => 30,
             'age_min' => 36, 'age_max' => 120, 'weight' => 2000, 'dims' => '120 x 80 cm',
             'skills' => 'Vận động thô, Kỹ năng xã hội', 'featured' => true,
             'desc' => "Khung nhựa lắp nhanh không cần dụng cụ, lưới dù bền, kèm bóng số 2 và bơm.\nTháo ra bỏ túi đựng, mang ra công viên cuối tuần rất gọn."],

            ['category' => 'Bóng & Thể Thao', 'name' => 'Bộ Bóng Rổ Chỉnh Độ Cao 1.6m', 'brand' => 'ToyLand', 'price' => 690000, 'qty' => 24,
             'age_min' => 36, 'age_max' => 120, 'weight' => 3500, 'dims' => 'Cao 90-160 cm',
             'skills' => 'Vận động thô',
             'desc' => "Chỉnh 6 nấc chiều cao, đế đổ nước hoặc cát cho vững.\nTheo bé từ lúc 3 tuổi tới khi 10 tuổi mới phải đổi."],

            ['category' => 'Bóng & Thể Thao', 'name' => 'Bộ Vợt Cầu Lông Trẻ Em Kèm Cầu', 'brand' => 'ToyLand', 'price' => 250000, 'qty' => 38,
             'age_min' => 48, 'age_max' => 144, 'material' => 'Khung nhôm', 'weight' => 400,
             'skills' => 'Vận động thô',
             'desc' => "Hai vợt nhẹ 75 g cán ngắn vừa tay bé, kèm 3 quả cầu nhựa.\nĐánh trong sân chung cư hay hành lang đều được."],

            ['category' => 'Bóng & Thể Thao', 'name' => 'Bóng Nhún Có Tay Cầm 45cm', 'brand' => 'ToyLand', 'price' => 190000, 'qty' => 45,
             'age_min' => 36, 'age_max' => 96, 'material' => 'Nhựa PVC dày', 'weight' => 600,
             'skills' => 'Vận động thô', 'safety' => 'Chỉ nhún trên sàn phẳng, tránh xa bàn ghế góc nhọn.',
             'desc' => "Bé ngồi lên nắm tay cầm và nhún nhảy đi khắp nhà.\nRèn cơ chân và khả năng giữ thăng bằng, lại xả được năng lượng ngày mưa."],

            ['category' => 'Bóng & Thể Thao', 'name' => 'Bộ Bowling Nhựa 10 Chai Cho Bé', 'brand' => 'ToyLand', 'price' => 160000, 'qty' => 48,
             'age_min' => 24, 'age_max' => 84, 'weight' => 700,
             'skills' => 'Vận động thô, Toán học sớm',
             'desc' => "Mười chai nhựa nhẹ in số và 2 quả bóng, đổ cát vào chai cho khó hơn.\nBé tự cộng điểm sau mỗi lượt - toán học lén lút."],

            ['category' => 'Bóng & Thể Thao', 'name' => 'Dây Nhảy Đếm Số Điện Tử Cho Bé', 'brand' => 'ToyLand', 'price' => 120000, 'qty' => 50,
             'age_min' => 60, 'age_max' => 168, 'battery' => '1 viên AAA (kèm sẵn)', 'weight' => 200,
             'skills' => 'Vận động thô, Toán học sớm',
             'desc' => "Tay cầm có màn hình đếm số lần nhảy và ước tính calo.\nChỉnh được độ dài dây theo chiều cao bé."],

            // ---- Đồ Chơi Bãi Biển & Nước ----
            ['category' => 'Đồ Chơi Bãi Biển & Nước', 'name' => 'Bộ Súng Nước Áp Lực Cao 2 Khẩu', 'brand' => 'ToyLand', 'price' => 280000, 'qty' => 40,
             'age_min' => 48, 'age_max' => 144, 'weight' => 600, 'dims' => 'Tầm bắn 8 mét',
             'skills' => 'Vận động thô, Kỹ năng xã hội', 'safety' => 'Không bắn vào mặt, mắt và tai.',
             'desc' => "Bình chứa 600 ml, bơm tay tạo áp lực bắn xa 8 mét.\nHai khẩu trong một hộp để bé có bạn chơi cùng ngay."],

            ['category' => 'Đồ Chơi Bãi Biển & Nước', 'name' => 'Bể Bơi Phao 3 Tầng Cho Bé 150cm', 'brand' => 'Intex', 'price' => 490000, 'qty' => 26,
             'age_min' => 12, 'age_max' => 84, 'material' => 'Nhựa PVC dày', 'weight' => 2200, 'dims' => '150 x 150 x 56 cm',
             'skills' => 'Vận động thô', 'safety' => 'Luôn có người lớn trông chừng khi bé ở trong bể.', 'featured' => true,
             'desc' => "Ba tầng phao độc lập, đáy có lớp đệm khí nên bé ngồi không bị lạnh lưng.\nCó van xả đáy, tháo nước không cần lật bể."],

            ['category' => 'Đồ Chơi Bãi Biển & Nước', 'name' => 'Bộ Đồ Chơi Cát Xô Xẻng 10 Món', 'brand' => 'ToyLand', 'price' => 150000, 'qty' => 50,
             'age_min' => 18, 'age_max' => 84, 'weight' => 500,
             'skills' => 'Vận động tinh, Sáng tạo',
             'desc' => "Xô, xẻng, cào, phễu và 6 khuôn hình con vật, đựng trong lưới thoát cát.\nNhựa dày, không gãy khi bé xúc cát ướt."],

            ['category' => 'Đồ Chơi Bãi Biển & Nước', 'name' => 'Phao Bơi Đeo Tay Cho Bé Tập Bơi', 'brand' => 'Intex', 'price' => 95000, 'qty' => 60,
             'age_min' => 12, 'age_max' => 72, 'material' => 'Nhựa PVC dày', 'weight' => 150,
             'skills' => 'Vận động thô', 'safety' => 'Phao trợ giúp, KHÔNG thay thế việc trông chừng bé.',
             'desc' => "Hai ngăn khí riêng biệt, một ngăn xì thì ngăn kia vẫn nổi.\nVan chống xì ngược, bơm bằng miệng khoảng một phút."],

            ['category' => 'Đồ Chơi Bãi Biển & Nước', 'name' => 'Bàn Chơi Cát Và Nước Có Chân Đứng', 'brand' => 'ToyLand', 'price' => 890000, 'qty' => 18,
             'age_min' => 24, 'age_max' => 84, 'weight' => 3000, 'dims' => '85 x 55 x 50 cm',
             'skills' => 'Vận động tinh, Sáng tạo',
             'desc' => "Hai khoang riêng: một bên đổ cát, một bên đổ nước, có nắp đậy khi không chơi.\nBé đứng chơi ở ban công mà không phải ngồi bệt xuống sàn."],

            ['category' => 'Đồ Chơi Bãi Biển & Nước', 'name' => 'Bộ 100 Bóng Bay Nước Tự Buộc', 'brand' => 'ToyLand', 'price' => 85000, 'qty' => 70,
             'age_min' => 48, 'age_max' => 144, 'weight' => 200,
             'skills' => 'Vận động thô, Kỹ năng xã hội', 'safety' => 'Nhặt hết vỏ bóng sau khi chơi, tránh bé nhỏ nuốt phải.',
             'desc' => "Gắn vào vòi nước, mở van là 100 quả tự bơm đầy và tự buộc trong 60 giây.\nMón không thể thiếu cho buổi chiều hè ở sân nhà."],

            // ---- Nhà Bóng & Lều ----
            ['category' => 'Nhà Bóng & Lều', 'name' => 'Lều Công Chúa Hình Lâu Đài Có Đèn', 'brand' => 'ToyLand', 'price' => 690000, 'qty' => 22,
             'age_min' => 24, 'age_max' => 96, 'gender' => 'girl', 'material' => 'Vải polyester + khung sợi thủy tinh', 'weight' => 1800,
             'dims' => '135 x 105 cm', 'skills' => 'Sáng tạo, Kỹ năng xã hội', 'featured' => true,
             'desc' => "Lều hình lâu đài hồng có cửa sổ lưới và dây đèn LED chạy pin bên trong.\nBật khung là dựng xong trong 30 giây, gấp lại vừa túi tròn dẹt."],

            ['category' => 'Nhà Bóng & Lều', 'name' => 'Lều Bóng Kèm Ống Chui Và 100 Bóng', 'brand' => 'ToyLand', 'price' => 790000, 'qty' => 20,
             'age_min' => 18, 'age_max' => 72, 'weight' => 2500,
             'skills' => 'Vận động thô, Sáng tạo',
             'desc' => "Hai lều nối nhau bằng ống chui dài 1.2 m, kèm 100 quả bóng nhựa.\nBé bò chui qua lại cả buổi - vận động thô mà không cần ra ngoài trời nắng."],

            ['category' => 'Nhà Bóng & Lều', 'name' => 'Lều Vũ Trụ Hình Tàu Không Gian', 'brand' => 'ToyLand', 'price' => 620000, 'qty' => 24,
             'age_min' => 24, 'age_max' => 96, 'gender' => 'boy', 'material' => 'Vải polyester + khung sợi thủy tinh', 'weight' => 1600,
             'skills' => 'Sáng tạo',
             'desc' => "Tàu vũ trụ bạc in hành tinh, có ô cửa tròn nhìn ra ngoài.\nDán được sao dạ quang lên trần trong lều cho bé chơi buổi tối."],

            ['category' => 'Nhà Bóng & Lều', 'name' => 'Nhà Bóng Nhựa Lắp Ghép Có Cửa Ra Vào', 'brand' => 'ToyLand', 'price' => 1290000, 'qty' => 12,
             'age_min' => 24, 'age_max' => 84, 'weight' => 6000, 'dims' => '120 x 110 x 130 cm',
             'skills' => 'Vận động thô, Sáng tạo',
             'desc' => "Nhà nhựa lắp ghép có cửa mở, cửa sổ và mái ngói, dựng được cả trong nhà lẫn ngoài sân.\nNhựa dày chịu nắng mưa, không giòn sau một mùa hè."],

            ['category' => 'Nhà Bóng & Lều', 'name' => 'Lều Cắm Trại Trẻ Em Kiểu Ấn Độ', 'brand' => 'ToyLand', 'price' => 550000, 'qty' => 26,
             'age_min' => 24, 'age_max' => 96, 'material' => 'Vải canvas + khung gỗ thông', 'weight' => 2200,
             'skills' => 'Sáng tạo, Kỹ năng xã hội',
             'desc' => "Khung gỗ thông và vải canvas dày, dáng lều tipi cao 160 cm.\nKèm thảm lót và túi treo bên trong đựng sách - góc đọc sách riêng của bé."],

            ['category' => 'Nhà Bóng & Lều', 'name' => 'Túi 200 Quả Bóng Nhựa Bù Thêm', 'brand' => 'ToyLand', 'price' => 280000, 'qty' => 40,
             'age_min' => 12, 'age_max' => 72, 'material' => 'Nhựa PE dẻo', 'weight' => 1500, 'dims' => 'Đường kính 5.5 cm',
             'safety' => 'Bóng cỡ 5.5 cm, an toàn cho bé dưới 3 tuổi.', 'skills' => 'Vận động thô',
             'desc' => "200 quả bóng nhựa PE dẻo, bóp méo rồi bật lại chứ không vỡ thành mảnh sắc.\nMua bù cho nhà bóng cũ hoặc đổ vào bể bơi phao."],

            // ---- Cầu Trượt & Xích Đu ----
            ['category' => 'Cầu Trượt & Xích Đu', 'name' => 'Cầu Trượt Nhựa Trong Nhà Cho Bé', 'brand' => 'ToyLand', 'price' => 1490000, 'qty' => 14,
             'age_min' => 18, 'age_max' => 72, 'weight' => 8000, 'dims' => '150 x 60 x 90 cm',
             'skills' => 'Vận động thô', 'safety' => 'Đặt trên thảm mềm, luôn có người lớn trông chừng.', 'featured' => true,
             'desc' => "Máng trượt dài 1.5 m có thành cao hai bên, bậc thang chống trượt.\nChân đế rộng bám sàn, gấp gọn dựng vào góc khi không dùng."],

            ['category' => 'Cầu Trượt & Xích Đu', 'name' => 'Xích Đu Nhựa Có Đai An Toàn', 'brand' => 'ToyLand', 'price' => 490000, 'qty' => 24,
             'age_min' => 12, 'age_max' => 48, 'weight' => 2000,
             'skills' => 'Vận động thô', 'safety' => 'Chỉ treo vào điểm chịu lực chắc chắn, kiểm tra dây trước mỗi lần chơi.',
             'desc' => "Ghế nhựa có tựa lưng cao, đai an toàn 3 điểm và thanh chắn trước.\nDây thừng dù chịu tải 80 kg, chỉnh độ cao bằng khóa nhựa."],

            ['category' => 'Cầu Trượt & Xích Đu', 'name' => 'Bộ Cầu Trượt Kèm Xích Đu Và Rổ Bóng', 'brand' => 'ToyLand', 'price' => 2490000, 'qty' => 9,
             'age_min' => 24, 'age_max' => 84, 'weight' => 14000, 'dims' => '220 x 140 x 120 cm',
             'skills' => 'Vận động thô', 'safety' => 'Lắp trên nền phẳng, luôn có người lớn trông chừng.',
             'desc' => "Ba trò trong một khung: cầu trượt, xích đu và rổ bóng rổ mini.\nKhung nhựa dày chịu tải 50 kg, dùng được cả ngoài sân."],

            ['category' => 'Cầu Trượt & Xích Đu', 'name' => 'Xích Đu Lưới Tròn Treo Cây', 'brand' => 'ToyLand', 'price' => 690000, 'qty' => 18,
             'age_min' => 36, 'age_max' => 144, 'material' => 'Khung thép + dây dù', 'weight' => 3000, 'dims' => 'Đường kính 100 cm',
             'skills' => 'Vận động thô', 'safety' => 'Chỉ treo vào cành cây chắc hoặc giá đỡ chuyên dụng.',
             'desc' => "Đĩa lưới đường kính 1 m, hai bé ngồi cùng lúc được, chịu tải 100 kg.\nNằm ngửa lên đó đọc sách buổi chiều cũng rất thích."],

            ['category' => 'Cầu Trượt & Xích Đu', 'name' => 'Nhà Nhún Bơm Hơi Có Máy Bơm', 'brand' => 'Intex', 'price' => 2890000, 'qty' => 8,
             'age_min' => 36, 'age_max' => 96, 'material' => 'Nhựa PVC dày', 'weight' => 12000, 'dims' => '200 x 180 x 170 cm',
             'skills' => 'Vận động thô', 'safety' => 'Tối đa 3 bé cùng lúc. Luôn có người lớn trông chừng.',
             'desc' => "Nhà nhún có tường lưới bao quanh và máng trượt nhỏ, kèm máy bơm điện.\nBơm căng trong 3 phút, xả hơi gấp lại cất tủ."],

            ['category' => 'Cầu Trượt & Xích Đu', 'name' => 'Thang Leo Khỉ Gỗ Cho Bé Trong Nhà', 'brand' => 'ToyLand', 'price' => 1190000, 'qty' => 12,
             'age_min' => 12, 'age_max' => 60, 'material' => 'Gỗ thông tự nhiên', 'weight' => 5000, 'dims' => 'Cao 80 cm',
             'skills' => 'Vận động thô', 'safety' => 'Đặt trên thảm mềm, luôn có người lớn trông chừng.',
             'desc' => "Khung leo Pikler kiểu Montessori, gập chữ A dựng ở góc phòng.\nBé tự leo trèo trong tầm kiểm soát thay vì trèo lên bàn ghế."],
        ];
    }

    // ===================== 11. BOARD GAME & NHẬP VAI =====================

    private static function boardGames(): array
    {
        return [
            // ---- Board Game Gia Đình ----
            ['category' => 'Board Game Gia Đình', 'name' => 'Board Game Cá Ngựa Gỗ Cao Cấp', 'brand' => 'ToyLand', 'price' => 290000, 'qty' => 35,
             'age_min' => 60, 'age_max' => null, 'material' => 'Gỗ tự nhiên', 'weight' => 900,
             'skills' => 'Toán học sớm, Kỹ năng xã hội', 'featured' => true,
             'desc' => "Bàn cờ gỗ và quân ngựa tiện tròn, 2-4 người chơi.\nTrò quen thuộc của nhiều thế hệ, luật đơn giản nên ông bà chơi cùng cháu được."],

            ['category' => 'Board Game Gia Đình', 'name' => 'Board Game Cờ Tỷ Phú Phiên Bản Du Lịch Việt Nam', 'brand' => 'ToyLand', 'price' => 350000, 'qty' => 30,
             'age_min' => 84, 'age_max' => null, 'weight' => 1100,
             'skills' => 'Toán học sớm, Kỹ năng xã hội',
             'desc' => "Bàn cờ in các địa danh Việt Nam, tiền giấy mệnh giá đồng.\nBé học tiêu tiền, tính lời lỗ và thương lượng - toàn kỹ năng dùng được cả đời."],

            ['category' => 'Board Game Gia Đình', 'name' => 'Board Game Rút Gỗ 54 Thanh Kèm Xúc Xắc Màu', 'brand' => 'ToyLand', 'price' => 150000, 'qty' => 45,
             'age_min' => 48, 'age_max' => null, 'material' => 'Gỗ cao su', 'weight' => 800,
             'skills' => 'Vận động tinh, Tập trung & kiên nhẫn',
             'desc' => "54 thanh gỗ bào nhẵn, rút từng thanh mà không làm đổ tháp.\nLuật học trong 10 giây, chơi mãi không chán, ai cũng chơi được."],

            ['category' => 'Board Game Gia Đình', 'name' => 'Board Game Cờ Cá Sấu Nha Sĩ', 'brand' => 'ToyLand', 'price' => 180000, 'qty' => 42,
             'age_min' => 36, 'age_max' => 96, 'weight' => 400,
             'skills' => 'Kỹ năng xã hội, Vận động tinh',
             'desc' => "Ấn từng chiếc răng cá sấu, ai xui gặp chiếc răng đau thì bị đớp.\nTrò may rủi thuần túy nên bé 3 tuổi thắng được cả người lớn."],

            ['category' => 'Board Game Gia Đình', 'name' => 'Board Game Đoán Hình Vẽ Gia Đình', 'brand' => 'ToyLand', 'price' => 260000, 'qty' => 32,
             'age_min' => 72, 'age_max' => null, 'weight' => 700,
             'skills' => 'Ngôn ngữ, Sáng tạo, Kỹ năng xã hội',
             'desc' => "Một người vẽ, cả đội đoán, chạy đua với đồng hồ cát.\n500 thẻ từ chia ba mức độ khó nên cả nhà chơi chung không bị lệch."],

            ['category' => 'Board Game Gia Đình', 'name' => 'Board Game Xếp Hình Ngũ Sắc Blokus', 'brand' => 'Mattel', 'price' => 490000, 'qty' => 24,
             'age_min' => 84, 'age_max' => null, 'weight' => 900,
             'skills' => 'Tư duy logic, Tập trung & kiên nhẫn',
             'desc' => "Đặt các mảnh hình sao cho chỉ chạm góc mảnh cùng màu của mình.\nLuật một câu, nhưng chiến thuật thì càng chơi càng sâu."],

            // ---- Cờ & Trí Tuệ ----
            ['category' => 'Cờ & Trí Tuệ', 'name' => 'Bộ Cờ Vua Nam Châm Gấp Gọn Khổ Lớn 36cm', 'brand' => 'ToyLand', 'price' => 220000, 'qty' => 40,
             'age_min' => 60, 'age_max' => null, 'material' => 'Nhựa + nam châm', 'weight' => 600,
             'skills' => 'Tư duy logic, Tập trung & kiên nhẫn', 'featured' => true,
             'desc' => "Quân cờ hít nam châm nên đi tàu xe không bị xô lệch.\nBàn cờ gập đôi thành hộp đựng luôn quân, không sợ thất lạc."],

            ['category' => 'Cờ & Trí Tuệ', 'name' => 'Bộ Cờ Tướng Gỗ Hương Khắc Chữ', 'brand' => 'ToyLand', 'price' => 350000, 'qty' => 28,
             'age_min' => 72, 'age_max' => null, 'material' => 'Gỗ hương', 'origin' => 'Việt Nam', 'weight' => 1100,
             'skills' => 'Tư duy logic, Tập trung & kiên nhẫn',
             'desc' => "Quân gỗ hương tiện tròn, chữ khắc chìm rồi tô màu chứ không in decal.\nBàn cờ gỗ gấp đôi, đựng vừa quân bên trong."],

            ['category' => 'Cờ & Trí Tuệ', 'name' => 'Rubik 3x3 Tốc Độ Kèm Sách Hướng Dẫn', 'brand' => 'ToyLand', 'price' => 120000, 'qty' => 60,
             'age_min' => 72, 'age_max' => null, 'weight' => 100,
             'skills' => 'Tư duy logic, Vận động tinh, Tập trung & kiên nhẫn',
             'desc' => "Lõi trục ốc chỉnh được độ trơn, xoay nhẹ tay và ít kẹt góc.\nSách hướng dẫn 7 bước bằng tiếng Việt, bé tự giải được sau vài ngày."],

            ['category' => 'Cờ & Trí Tuệ', 'name' => 'Bộ 6 Rubik Biến Thể Kim Tự Tháp', 'brand' => 'ToyLand', 'price' => 290000, 'qty' => 35,
             'age_min' => 84, 'age_max' => null, 'weight' => 500,
             'skills' => 'Tư duy logic, Vận động tinh',
             'desc' => "Sáu khối khác nhau: kim tự tháp, 2x2, 4x4, gương, ngôi sao và bánh răng.\nBé đã giải xong 3x3 thì đây là bước tiếp theo."],

            ['category' => 'Cờ & Trí Tuệ', 'name' => 'Bộ Cờ Caro Gỗ Kèm Quân Đen Trắng', 'brand' => 'ToyLand', 'price' => 190000, 'qty' => 38,
             'age_min' => 60, 'age_max' => null, 'material' => 'Gỗ tự nhiên', 'weight' => 800,
             'skills' => 'Tư duy logic, Tập trung & kiên nhẫn',
             'desc' => "Bàn cờ 15x15 khắc chìm, quân tròn nhựa melamine dày dặn.\nLuật chỉ một dòng nhưng đủ khó để chơi cả tối."],

            ['category' => 'Cờ & Trí Tuệ', 'name' => 'Bộ Sudoku Gỗ Cho Bé Kèm 100 Đề', 'brand' => 'ToyLand', 'price' => 250000, 'qty' => 30,
             'age_min' => 84, 'age_max' => null, 'material' => 'Gỗ tự nhiên', 'weight' => 700,
             'skills' => 'Tư duy logic, Toán học sớm, Tập trung & kiên nhẫn',
             'desc' => "Bảng gỗ và các khối số rời, không cần bút nên làm sai thì nhấc ra đặt lại.\nKèm 100 đề chia 4 mức từ dễ tới khó."],

            // ---- Bài & Thẻ Bài ----
            ['category' => 'Bài & Thẻ Bài', 'name' => 'Bộ Bài Uno Flip Hai Mặt Chính Hãng', 'brand' => 'Mattel', 'price' => 120000, 'qty' => 60,
             'age_min' => 72, 'age_max' => null, 'weight' => 150,
             'skills' => 'Toán học sớm, Kỹ năng xã hội', 'featured' => true,
             'desc' => "Bản Uno Flip có hai mặt sáng - tối, lật bài là đổi luật giữa ván.\nLuật dễ tới mức bé 6 tuổi chơi được ngay ván đầu."],

            ['category' => 'Bài & Thẻ Bài', 'name' => 'Bộ Thẻ Bài Pokémon Sưu Tập 100 Lá', 'brand' => 'Pokémon', 'price' => 280000, 'qty' => 45,
             'age_min' => 72, 'age_max' => null, 'character' => 'Pokémon', 'weight' => 200,
             'skills' => 'Ngôn ngữ, Kỹ năng xã hội',
             'desc' => "100 lá ngẫu nhiên có đảm bảo ít nhất 5 lá hiếm ánh kim.\nBé sưu tập, đổi bài với bạn và học thuộc hệ của từng Pokémon."],

            ['category' => 'Bài & Thẻ Bài', 'name' => 'Board Game Ma Sói Phiên Bản Tiếng Việt', 'brand' => 'ToyLand', 'price' => 190000, 'qty' => 40,
             'age_min' => 96, 'age_max' => null, 'weight' => 250,
             'skills' => 'Ngôn ngữ, Kỹ năng xã hội',
             'desc' => "Bài dành cho 8-18 người, mỗi người một vai giấu kín.\nTrò rèn khả năng lập luận và thuyết phục - hợp cho sinh nhật đông bạn."],

            ['category' => 'Bài & Thẻ Bài', 'name' => 'Bộ Bài Tam Cúc Truyền Thống', 'brand' => 'ToyLand', 'price' => 45000, 'qty' => 70,
             'age_min' => 84, 'age_max' => null, 'origin' => 'Việt Nam', 'weight' => 80,
             'skills' => 'Toán học sớm, Kỹ năng xã hội',
             'desc' => "Bộ bài dân gian 32 quân in trên giấy bồi truyền thống.\nDịp Tết mang ra chơi cùng ông bà, bé học được một nét văn hóa cũ."],

            ['category' => 'Bài & Thẻ Bài', 'name' => 'Board Game Bài Ghép Cặp Trí Nhớ 72 Thẻ', 'brand' => 'ToyLand', 'price' => 140000, 'qty' => 48,
             'age_min' => 36, 'age_max' => 96, 'weight' => 300,
             'skills' => 'Tập trung & kiên nhẫn, Ngôn ngữ',
             'desc' => "36 cặp thẻ hình úp xuống, lật hai lá trùng nhau thì được giữ.\nBé nhỏ thường thắng người lớn ở trò này - trí nhớ hình ảnh của các bé rất tốt."],

            ['category' => 'Bài & Thẻ Bài', 'name' => 'Bộ Bài Toán Học Nhanh Tay 200 Thẻ', 'brand' => 'ToyLand', 'price' => 160000, 'qty' => 42,
             'age_min' => 60, 'age_max' => 144, 'weight' => 250,
             'skills' => 'Toán học sớm, Tập trung & kiên nhẫn',
             'desc' => "Lật thẻ và đọc to kết quả phép tính, ai nhanh hơn được lá bài.\nBảng cửu chương thuộc lúc nào không hay vì bé mải tranh thắng."],

            // ---- Đồ Chơi Đóng Vai ----
            ['category' => 'Đồ Chơi Đóng Vai', 'name' => 'Bộ Đồ Chơi Bác Sĩ 30 Món Kèm Vali', 'brand' => 'ToyLand', 'price' => 320000, 'qty' => 40,
             'age_min' => 36, 'age_max' => 108, 'weight' => 800,
             'skills' => 'Kỹ năng xã hội, Ngôn ngữ', 'featured' => true,
             'desc' => "Ống nghe có tiếng tim đập thật, máy đo huyết áp, kim tiêm giả và áo blouse.\nĐóng vai bác sĩ giúp bé bớt sợ mỗi lần đi khám thật."],

            ['category' => 'Đồ Chơi Đóng Vai', 'name' => 'Bộ Bếp Nấu Ăn Gỗ Có Bồn Rửa', 'brand' => 'Hape', 'price' => 1890000, 'qty' => 12,
             'age_min' => 36, 'age_max' => 108, 'material' => 'Gỗ tự nhiên sơn gốc nước', 'origin' => 'Đức', 'weight' => 7000,
             'dims' => '80 x 35 x 95 cm', 'skills' => 'Kỹ năng xã hội, Sáng tạo',
             'desc' => "Bếp gỗ có núm vặn kêu lạch cạch, lò nướng mở cửa, bồn rửa tháo ra rửa được.\nKèm nồi chảo inox mini và 20 món thực phẩm gỗ."],

            ['category' => 'Đồ Chơi Đóng Vai', 'name' => 'Bộ Đồ Nghề Sửa Chữa Kèm Bàn Thợ', 'brand' => 'ToyLand', 'price' => 490000, 'qty' => 28,
             'age_min' => 36, 'age_max' => 108, 'gender' => 'boy', 'weight' => 1500,
             'skills' => 'Vận động tinh, Sáng tạo',
             'desc' => "Bàn thợ có ê tô, khoan chạy pin, búa, cờ lê và hơn 40 ốc vít nhựa cỡ lớn.\nBé vặn ốc thật vào lỗ thật - khéo tay hơn nhiều so với bấm màn hình."],

            ['category' => 'Đồ Chơi Đóng Vai', 'name' => 'Bộ Siêu Thị Mini Có Máy Tính Tiền', 'brand' => 'ToyLand', 'price' => 690000, 'qty' => 22,
             'age_min' => 36, 'age_max' => 108, 'battery' => '3 viên AA (mua riêng)', 'weight' => 2000,
             'skills' => 'Toán học sớm, Kỹ năng xã hội',
             'desc' => "Quầy hàng có kệ, giỏ, máy quét mã vạch kêu bíp và máy tính tiền có ngăn kéo.\nKèm tiền giấy đồ chơi - bé tập trả tiền và thối lại cho đúng."],

            ['category' => 'Đồ Chơi Đóng Vai', 'name' => 'Bộ Đồ Chơi Lính Cứu Hỏa Kèm Mũ Và Bình', 'brand' => 'ToyLand', 'price' => 280000, 'qty' => 34,
             'age_min' => 36, 'age_max' => 96, 'weight' => 700,
             'skills' => 'Kỹ năng xã hội, Vận động thô',
             'desc' => "Mũ cứu hỏa, áo phản quang, bình xịt nước đeo vai và rìu nhựa mềm.\nBé hóa thân thành người hùng mà lại học được cách gọi 114."],

            ['category' => 'Đồ Chơi Đóng Vai', 'name' => 'Bộ Làm Tóc Và Trang Điểm Có Gương', 'brand' => 'ToyLand', 'price' => 390000, 'qty' => 26,
             'age_min' => 36, 'age_max' => 108, 'gender' => 'girl', 'weight' => 900,
             'skills' => 'Vận động tinh, Kỹ năng xã hội',
             'desc' => "Gương đứng, máy sấy kêu vù vù, lược, kẹp và bộ mỹ phẩm đồ chơi không màu.\nBé chải tóc cho búp bê, cho mẹ và cho chính mình."],
        ];
    }
}
