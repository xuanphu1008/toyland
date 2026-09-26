<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Nạp cây danh mục 2 cấp (tham khảo cách phân loại của MyKingdom) và
 * khoảng 100 sản phẩm đồ chơi mẫu với đầy đủ thông số.
 *
 * Seeder này AN TOÀN khi chạy lại: dùng firstOrCreate/updateOrCreate theo tên,
 * KHÔNG xóa dữ liệu cũ - nên không làm hỏng các đơn hàng đang trỏ tới sản phẩm.
 *
 *   php artisan db:seed --class=ToyCatalogSeeder
 */
class ToyCatalogSeeder extends Seeder
{
    /** Danh mục cha => [icon, mô tả, danh sách danh mục con] */
    private array $tree = [
        'Đồ Chơi Lắp Ghép' => [
            '🧱',
            'Xếp hình, mô hình lắp ráp và đất nặn - rèn tư duy không gian, kiên nhẫn và sự khéo léo.',
            ['LEGO & Xếp Hình', 'Mô Hình Lắp Ráp', 'Lắp Ghép Gỗ 3D', 'Đất Nặn & Sáng Tạo'],
        ],
        'Đồ Chơi Phương Tiện' => [
            '🚗',
            'Xe điều khiển, xe mô hình sưu tầm, tàu hỏa và các bộ playset giao thông.',
            ['Xe Điều Khiển Từ Xa', 'Xe Mô Hình Die-cast', 'Tàu Hỏa & Đường Ray', 'Playset Phương Tiện'],
        ],
        'Xe Đạp & Scooter' => [
            '🚲',
            'Xe đạp, scooter, xe chòi chân và đồ bảo hộ cho bé tập vận động ngoài trời.',
            ['Xe Đạp Trẻ Em', 'Scooter & Ván Trượt', 'Xe Chòi Chân & Xe Lắc', 'Mũ & Đồ Bảo Hộ'],
        ],
        'Đồ Chơi Theo Phim' => [
            '🎬',
            'Nhân vật siêu anh hùng, công chúa, anime và game mà các bé yêu thích.',
            ['Siêu Anh Hùng Marvel & DC', 'Công Chúa & Disney', 'Anime & Manga', 'Nhân Vật Game'],
        ],
        'Búp Bê & Nhà Búp Bê' => [
            '🎀',
            'Búp bê thời trang, búp bê vải cho bé sơ sinh, nhà búp bê và phụ kiện.',
            ['Búp Bê Thời Trang', 'Nhà Búp Bê & Phụ Kiện', 'Búp Bê Vải & Sơ Sinh', 'Búp Bê Nhân Vật'],
        ],
        'Robot & Đồ Chơi Công Nghệ' => [
            '🤖',
            'Robot biến hình, robot điều khiển và bộ lập trình STEM cho bé làm quen công nghệ.',
            ['Robot Biến Hình', 'Robot Điều Khiển', 'Lập Trình & STEM', 'Drone & Máy Bay'],
        ],
        'Thế Giới Động Vật' => [
            '🦁',
            'Mô hình động vật, khủng long và thú bông giúp bé khám phá thiên nhiên.',
            ['Mô Hình Động Vật', 'Khủng Long', 'Thú Bông', 'Đại Dương & Côn Trùng'],
        ],
        'Đồ Dùng Học Tập' => [
            '📚',
            'Bảng viết, học chữ - học số, bộ thí nghiệm và dụng cụ mỹ thuật cho bé.',
            ['Bảng & Bút Viết', 'Học Chữ & Học Số', 'Thí Nghiệm Khoa Học', 'Dụng Cụ Mỹ Thuật'],
        ],
        'Đồ Dùng & Đồ Chơi Cho Bé' => [
            '🍼',
            'Đồ chơi an toàn cho bé từ 0 đến 3 tuổi: xúc xắc, gặm nướu, thảm chơi, xe tập đi.',
            ['Đồ Chơi Sơ Sinh', 'Xúc Xắc & Gặm Nướu', 'Xe Tập Đi & Thảm Chơi', 'Đồ Chơi Nhà Tắm'],
        ],
        'Đồ Chơi Vận Động Ngoài Trời' => [
            '⚽',
            'Bóng, đồ chơi bãi biển, nhà bóng và cầu trượt cho bé chạy nhảy thỏa thích.',
            ['Bóng & Thể Thao', 'Đồ Chơi Bãi Biển & Nước', 'Nhà Bóng & Lều', 'Cầu Trượt & Xích Đu'],
        ],
        'Board Game & Nhập Vai' => [
            '🎲',
            'Board game gia đình, cờ trí tuệ và đồ chơi đóng vai bác sĩ, đầu bếp, thợ sửa xe.',
            ['Board Game Gia Đình', 'Cờ & Trí Tuệ', 'Bài & Thẻ Bài', 'Đồ Chơi Đóng Vai'],
        ],
    ];

    /**
     * Các danh mục có từ bản seed cũ nhưng không nằm trong cây mới.
     * Nếu để nguyên, chúng đứng lẻ ở cấp gốc -> menu "Danh mục" xuất hiện
     * những nhóm trống không có danh mục con nào bên dưới.
     * Bảng này gom chúng về đúng nhóm cha, sản phẩm cũ vẫn giữ nguyên.
     */
    private array $legacyMap = [
        'Đồ Chơi Giáo Dục STEM' => 'Robot & Đồ Chơi Công Nghệ',
        'Đồ Chơi Sơ Sinh & Mầm Non' => 'Đồ Dùng & Đồ Chơi Cho Bé',
        'Xe Mô Hình & Điều Khiển' => 'Đồ Chơi Phương Tiện',
    ];

    public function run()
    {
        $categories = $this->seedCategories();
        $this->adoptLegacyCategories($categories);
        $this->seedProducts($categories);
    }

    /**
     * Gắn các danh mục cũ vào nhóm cha tương ứng.
     * Chỉ đụng tới danh mục đang là GỐC để không ghi đè nếu admin đã tự sắp lại.
     */
    private function adoptLegacyCategories(array $categories): void
    {
        foreach ($this->legacyMap as $legacyName => $parentName) {
            $parent = $categories[$parentName] ?? null;
            $legacy = Category::where('name', $legacyName)->whereNull('parent_id')->first();

            if (! $parent || ! $legacy) {
                continue;
            }

            $legacy->update([
                'parent_id' => $parent->id,
                'icon' => $parent->icon,
                'sort_order' => $parent->sort_order + 50,
            ]);
        }
    }

    /**
     * Tạo danh mục cha - con.
     * Một vài danh mục cũ (từ bản thực phẩm chức năng chuyển sang) có tên trùng
     * với danh mục cha mới -> firstOrCreate sẽ dùng lại chính bản ghi đó,
     * nhờ vậy sản phẩm cũ không bị mồ côi danh mục.
     *
     * @return array<string, Category> map "tên danh mục" => model
     */
    private function seedCategories(): array
    {
        $map = [];
        $order = 0;

        foreach ($this->tree as $parentName => [$icon, $description, $children]) {
            $parent = Category::firstOrCreate(
                ['name' => $parentName],
                ['description' => $description]
            );

            $parent->update([
                'parent_id' => null,
                'icon' => $icon,
                'slug' => Str::slug($parentName),
                'description' => $description,
                'sort_order' => ++$order * 100,
            ]);

            $map[$parentName] = $parent;
            $childOrder = 0;

            foreach ($children as $childName) {
                $child = Category::firstOrCreate(['name' => $childName], []);

                $child->update([
                    'parent_id' => $parent->id,
                    'icon' => $icon,
                    'slug' => Str::slug($childName),
                    'description' => $parentName . ' - ' . $childName,
                    'sort_order' => $parent->sort_order + (++$childOrder),
                ]);

                $map[$childName] = $child;
            }
        }

        return $map;
    }

    /**
     * Tạo sản phẩm. Khóa nhận diện là TÊN sản phẩm nên chạy lại seeder
     * sẽ cập nhật thông số chứ không nhân bản thêm.
     */
    private function seedProducts(array $categories): void
    {
        $index = 0;

        foreach ($this->products() as $row) {
            $category = $categories[$row['category']] ?? null;

            if (! $category) {
                continue;
            }

            $index++;

            Product::updateOrCreate(
                ['name' => $row['name']],
                [
                    'category_id' => $category->id,
                    'sku' => $row['sku'] ?? sprintf('TL-%04d', $index),
                    'brand' => $row['brand'],
                    'character_name' => $row['character'] ?? null,
                    'age_min_months' => $row['age_min'],
                    'age_max_months' => $row['age_max'],
                    'age_range' => $this->ageLabel($row['age_min'], $row['age_max']),
                    'material' => $row['material'] ?? 'Nhựa ABS an toàn',
                    'origin' => $row['origin'] ?? 'Trung Quốc',
                    'gender' => $row['gender'] ?? 'unisex',
                    'skills' => $row['skills'] ?? null,
                    'piece_count' => $row['pieces'] ?? null,
                    'battery' => $row['battery'] ?? null,
                    'warranty_months' => $row['warranty'] ?? null,
                    'dimensions' => $row['dims'] ?? null,
                    'weight_grams' => $row['weight'] ?? 500,
                    'safety_note' => $row['safety'] ?? ($row['age_min'] >= 36 ? 'Có chi tiết nhỏ, không dành cho bé dưới 3 tuổi.' : null),
                    'is_featured' => $row['featured'] ?? false,
                    'description' => $row['desc'],
                    'price' => $row['price'],
                    'quantity' => $row['qty'],
                ]
            );
        }
    }

    private function ageLabel(int $min, ?int $max): string
    {
        $format = fn (int $months) => $months < 24 ? $months . ' tháng' : intdiv($months, 12) . ' tuổi';

        if ($max === null) {
            return $format($min) . ' trở lên';
        }

        return $format($min) . ' - ' . $format($max);
    }

    /**
     * Danh sách sản phẩm mẫu, gộp từ hai nguồn:
     *   - các hàm ...Toys() ngay trong file này (bộ dữ liệu ban đầu)
     *   - lớp ToyCatalogExtra (bộ mở rộng, tách file riêng cho dễ đọc)
     *
     * Tổng cộng khoảng 8-9 món cho mỗi danh mục con, đủ để bộ lọc theo hãng,
     * độ tuổi, nhân vật và khoảng giá cho ra kết quả có ý nghĩa.
     */
    private function products(): array
    {
        return array_merge(
            $this->blocksToys(),
            $this->vehicleToys(),
            $this->bikeToys(),
            $this->movieToys(),
            $this->dollToys(),
            $this->robotToys(),
            $this->animalToys(),
            $this->studyToys(),
            $this->babyToys(),
            $this->outdoorToys(),
            $this->boardGameToys(),
            // Kho dữ liệu mở rộng nằm ở file riêng cho dễ sửa - xem ToyCatalogExtra
            ToyCatalogExtra::all()
        );
    }

    // ===================== 1. ĐỒ CHƠI LẮP GHÉP =====================
    private function blocksToys(): array
    {
        return [
            ['category' => 'LEGO & Xếp Hình', 'name' => 'LEGO City Đồn Cảnh Sát 60316', 'brand' => 'LEGO', 'price' => 1290000, 'qty' => 24,
             'age_min' => 72, 'age_max' => 144, 'material' => 'Nhựa ABS cao cấp', 'origin' => 'Đan Mạch', 'pieces' => 668, 'weight' => 1100,
             'dims' => '38 x 26 x 7 cm', 'skills' => 'Tư duy logic, Vận động tinh, Sáng tạo', 'featured' => true,
             'desc' => "Bộ đồn cảnh sát gồm 668 chi tiết với xe tuần tra, trực thăng và 5 nhân vật mini.\nSách hướng dẫn minh họa từng bước, bé 6 tuổi có thể tự lắp mà không cần bố mẹ trợ giúp."],

            ['category' => 'LEGO & Xếp Hình', 'name' => 'LEGO Classic Hộp Gạch Sáng Tạo 10698', 'brand' => 'LEGO', 'price' => 1180000, 'qty' => 30,
             'age_min' => 48, 'age_max' => null, 'material' => 'Nhựa ABS cao cấp', 'origin' => 'Đan Mạch', 'pieces' => 790, 'weight' => 1500,
             'skills' => 'Sáng tạo, Vận động tinh', 'featured' => true,
             'desc' => "Hộp 790 viên gạch đủ 33 màu kèm bánh xe, cửa sổ và mắt nhân vật.\nKhông theo mẫu cố định nên bé tự do sáng tạo bất cứ thứ gì mình nghĩ ra."],

            ['category' => 'LEGO & Xếp Hình', 'name' => 'LEGO DUPLO Ngôi Nhà Gia Đình 10994', 'brand' => 'LEGO', 'price' => 2450000, 'qty' => 12,
             'age_min' => 24, 'age_max' => 60, 'material' => 'Nhựa ABS cỡ lớn', 'origin' => 'Đan Mạch', 'pieces' => 129, 'weight' => 2300,
             'skills' => 'Ngôn ngữ, Kỹ năng xã hội', 'safety' => 'Gạch cỡ lớn chuyên cho bé dưới 5 tuổi, không gây hóc.',
             'desc' => "Ngôi nhà 3 tầng với gạch DUPLO cỡ lớn, an toàn cho bàn tay nhỏ của bé.\nKèm nhân vật gia đình để bé chơi đóng vai và tập kể chuyện sinh hoạt hằng ngày."],

            ['category' => 'Mô Hình Lắp Ráp', 'name' => 'Mô Hình Gundam RG 1/144 Strike Freedom', 'brand' => 'Bandai', 'character' => 'Gundam',
             'price' => 950000, 'qty' => 18, 'age_min' => 144, 'age_max' => null, 'material' => 'Nhựa PS', 'origin' => 'Nhật Bản',
             'gender' => 'boy', 'pieces' => 320, 'weight' => 450, 'skills' => 'Tập trung & kiên nhẫn, Vận động tinh',
             'desc' => "Mô hình dòng Real Grade tỉ lệ 1/144, khung xương khớp cử động linh hoạt.\nCác chi tiết đã tách màu sẵn nên không cần sơn hay keo dán."],

            ['category' => 'Mô Hình Lắp Ráp', 'name' => 'Mô Hình Gundam HG 1/144 Barbatos', 'brand' => 'Bandai', 'character' => 'Gundam',
             'price' => 520000, 'qty' => 26, 'age_min' => 96, 'age_max' => null, 'material' => 'Nhựa PS', 'origin' => 'Nhật Bản',
             'gender' => 'boy', 'pieces' => 180, 'weight' => 300, 'skills' => 'Tập trung & kiên nhẫn',
             'desc' => "Phiên bản High Grade dễ lắp, phù hợp cho bạn nhỏ mới bắt đầu chơi mô hình.\nKèm vũ khí rời và decal dán trang trí."],

            ['category' => 'Mô Hình Lắp Ráp', 'name' => 'Bộ Lắp Ráp Kim Loại Tháp Eiffel 3D', 'brand' => 'Piececool', 'price' => 380000, 'qty' => 34,
             'age_min' => 120, 'age_max' => null, 'material' => 'Thép không gỉ', 'origin' => 'Trung Quốc', 'pieces' => 92, 'weight' => 180,
             'dims' => 'Cao 22 cm khi hoàn thiện', 'skills' => 'Tập trung & kiên nhẫn, Vận động tinh',
             'safety' => 'Cạnh kim loại sắc, cần đeo găng và có người lớn giám sát.',
             'desc' => "Mô hình kim loại cắt laser, ghép bằng ngàm không cần keo.\nHoàn thiện thành vật trang trí bàn học rất đẹp mắt."],

            ['category' => 'Lắp Ghép Gỗ 3D', 'name' => 'Bộ Lắp Ghép Gỗ 3D Cối Xay Gió', 'brand' => 'RoboTime', 'price' => 420000, 'qty' => 40,
             'age_min' => 120, 'age_max' => null, 'material' => 'Gỗ ép tự nhiên', 'pieces' => 175, 'weight' => 600,
             'skills' => 'Tư duy logic, Tập trung & kiên nhẫn',
             'desc' => "Mô hình gỗ 3D lắp bằng ngàm, không cần keo dán.\nHoàn thiện có cơ cấu dây cót giúp cánh quạt quay thật."],

            ['category' => 'Lắp Ghép Gỗ 3D', 'name' => 'Nhà Búp Bê Gỗ 3D Tự Lắp Có Đèn', 'brand' => 'RoboTime', 'price' => 690000, 'qty' => 16,
             'age_min' => 144, 'age_max' => null, 'material' => 'Gỗ & vải', 'gender' => 'girl', 'pieces' => 240, 'weight' => 900,
             'battery' => '2 pin AAA (không kèm theo)', 'skills' => 'Sáng tạo, Vận động tinh',
             'desc' => "Bộ nhà búp bê mini tự lắp với hàng trăm phụ kiện tí hon và đèn LED.\nMất khoảng 15-20 giờ để hoàn thiện, rất hợp làm quà sinh nhật."],

            ['category' => 'Đất Nặn & Sáng Tạo', 'name' => 'Bộ Đất Nặn Play-Doh 10 Màu An Toàn', 'brand' => 'Play-Doh', 'price' => 185000, 'qty' => 120,
             'age_min' => 24, 'age_max' => 96, 'material' => 'Bột mì & muối thực phẩm', 'origin' => 'Mỹ', 'weight' => 700,
             'skills' => 'Sáng tạo, Vận động tinh', 'safety' => 'Không độc hại nhưng không dùng để ăn.',
             'desc' => "10 hũ đất nặn màu tươi, mềm dẻo, không dính tay và không để lại vết bẩn.\nThành phần gốc bột mì nên an toàn nếu bé lỡ cho vào miệng."],

            ['category' => 'Đất Nặn & Sáng Tạo', 'name' => 'Bộ Đất Nặn Làm Bánh Kem 42 Chi Tiết', 'brand' => 'Play-Doh', 'price' => 340000, 'qty' => 45,
             'age_min' => 36, 'age_max' => 108, 'material' => 'Bột nặn & nhựa PP', 'origin' => 'Mỹ', 'pieces' => 42, 'weight' => 950,
             'skills' => 'Sáng tạo, Kỹ năng xã hội',
             'desc' => "Bộ khuôn làm bánh kem, bánh quy kèm đế xoay và dụng cụ bắt kem.\nBé vừa nặn vừa học đếm số nến, chia phần bánh cho cả nhà."],
        ];
    }

    // ===================== 2. ĐỒ CHƠI PHƯƠNG TIỆN =====================
    private function vehicleToys(): array
    {
        return [
            ['category' => 'Xe Điều Khiển Từ Xa', 'name' => 'Xe Điều Khiển Địa Hình 4WD Tỉ Lệ 1/16', 'brand' => 'Wltoys', 'price' => 1250000, 'qty' => 16,
             'age_min' => 96, 'age_max' => null, 'material' => 'Nhựa ABS & hợp kim', 'gender' => 'boy', 'weight' => 1400,
             'battery' => 'Pin sạc 7.4V 1500mAh (kèm theo)', 'warranty' => 6, 'dims' => '32 x 24 x 14 cm',
             'skills' => 'Vận động thô, Tập trung & kiên nhẫn', 'featured' => true,
             'desc' => "Xe điều khiển sóng 2.4GHz chống nhiễu, 4 bánh chủ động, giảm xóc lò xo độc lập.\nMột lần sạc chạy liên tục khoảng 25 phút, leo dốc và chạy cát đều tốt."],

            ['category' => 'Xe Điều Khiển Từ Xa', 'name' => 'Xe Đua Điều Khiển Tỉ Lệ 1/24 Tốc Độ Cao', 'brand' => 'Rastar', 'price' => 480000, 'qty' => 38,
             'age_min' => 72, 'age_max' => null, 'material' => 'Nhựa ABS', 'gender' => 'boy', 'weight' => 600,
             'battery' => 'Pin sạc USB (kèm theo)', 'warranty' => 3, 'skills' => 'Vận động tinh',
             'desc' => "Xe đua mini tốc độ 15km/h, vô-lăng điều khiển dễ cầm vừa tay bé.\nSạc bằng cổng USB thông thường, không cần mua pin rời."],

            ['category' => 'Xe Điều Khiển Từ Xa', 'name' => 'Xe Ben Công Trình Điều Khiển 11 Kênh', 'brand' => 'Double E', 'price' => 890000, 'qty' => 20,
             'age_min' => 72, 'age_max' => 168, 'material' => 'Hợp kim & nhựa ABS', 'gender' => 'boy', 'weight' => 1800,
             'battery' => 'Pin sạc 3.7V (kèm theo)', 'warranty' => 6, 'skills' => 'Tư duy logic, Vận động tinh',
             'desc' => "Xe ben mô phỏng công trường với 11 kênh điều khiển: nâng thùng, quay đầu, bật đèn.\nKhung hợp kim chắc chắn, chịu được va đập khi bé chơi mạnh tay."],

            ['category' => 'Xe Mô Hình Die-cast', 'name' => 'Bộ 6 Xe Mô Hình Kim Loại Tỉ Lệ 1/64', 'brand' => 'Hot Wheels', 'price' => 320000, 'qty' => 65,
             'age_min' => 36, 'age_max' => null, 'material' => 'Kim loại đúc & nhựa', 'origin' => 'Thái Lan', 'gender' => 'boy', 'weight' => 350,
             'skills' => 'Sáng tạo',
             'desc' => "Bộ sưu tập 6 mẫu xe thể thao đúc kim loại, sơn tĩnh điện bền màu.\nBánh xe lăn trơn trên sàn nhà, hợp để bé bày trận đua xe."],

            ['category' => 'Xe Mô Hình Die-cast', 'name' => 'Xe Mô Hình Lamborghini Tỉ Lệ 1/24 Mở Cửa', 'brand' => 'Bburago', 'price' => 560000, 'qty' => 22,
             'age_min' => 96, 'age_max' => null, 'material' => 'Kim loại đúc', 'origin' => 'Ý', 'gender' => 'boy', 'weight' => 800,
             'dims' => '20 x 9 x 5 cm', 'skills' => 'Tập trung & kiên nhẫn',
             'desc' => "Mô hình tỉ lệ 1/24 mở được cửa, nắp ca-pô và cốp xe, nội thất chi tiết.\nKèm đế trưng bày, phù hợp cho bạn nhỏ bắt đầu sưu tầm xe."],

            ['category' => 'Xe Mô Hình Die-cast', 'name' => 'Bộ Xe Cứu Hộ Hợp Kim 4 Món Có Đèn Nhạc', 'brand' => 'Toyshouse', 'price' => 295000, 'qty' => 55,
             'age_min' => 36, 'age_max' => 108, 'material' => 'Hợp kim & nhựa', 'weight' => 700,
             'battery' => '3 pin LR44 (kèm theo)', 'skills' => 'Ngôn ngữ, Kỹ năng xã hội',
             'desc' => "Bộ 4 xe cứu hỏa, cứu thương, cảnh sát và xe cẩu có đèn chớp và còi hú.\nBé học được vai trò của từng lực lượng qua trò chơi đóng vai."],

            ['category' => 'Tàu Hỏa & Đường Ray', 'name' => 'Bộ Đường Ray Tàu Hỏa Gỗ 70 Chi Tiết', 'brand' => 'Winwintoys', 'price' => 650000, 'qty' => 28,
             'age_min' => 36, 'age_max' => 96, 'material' => 'Gỗ cao su tự nhiên', 'origin' => 'Việt Nam', 'pieces' => 70, 'weight' => 2000,
             'skills' => 'Tư duy logic, Sáng tạo, Vận động tinh', 'featured' => true,
             'desc' => "70 chi tiết đường ray gỗ ghép được thành hàng chục sơ đồ khác nhau.\nSơn gốc nước đạt chuẩn an toàn, tương thích với ray gỗ của các hãng khác."],

            ['category' => 'Tàu Hỏa & Đường Ray', 'name' => 'Tàu Điện Chạy Pin Có Khói Và Đèn', 'brand' => 'Toyshouse', 'price' => 470000, 'qty' => 30,
             'age_min' => 36, 'age_max' => 120, 'material' => 'Nhựa ABS', 'weight' => 1200,
             'battery' => '3 pin AA (không kèm theo)', 'skills' => 'Sáng tạo',
             'desc' => "Đoàn tàu chạy tự động trên ray tròn, phun khói giả và phát nhạc.\nLắp ráp trong 5 phút, bé mẫu giáo có thể tự bật tắt."],

            ['category' => 'Playset Phương Tiện', 'name' => 'Bộ Playset Gara Ô Tô 4 Tầng Có Thang Máy', 'brand' => 'Majorette', 'price' => 1150000, 'qty' => 14,
             'age_min' => 48, 'age_max' => 120, 'material' => 'Nhựa ABS', 'gender' => 'boy', 'weight' => 2600,
             'dims' => '60 x 40 x 55 cm', 'skills' => 'Sáng tạo, Kỹ năng xã hội',
             'desc' => "Gara 4 tầng có thang máy quay tay, trạm rửa xe và đường dốc xoắn.\nKèm 2 xe die-cast, tương thích với hầu hết xe tỉ lệ 1/64."],

            ['category' => 'Playset Phương Tiện', 'name' => 'Thảm Chơi Thành Phố Kèm 10 Xe Mini', 'brand' => 'Toyshouse', 'price' => 260000, 'qty' => 60,
             'age_min' => 24, 'age_max' => 84, 'material' => 'Vải nỉ & nhựa', 'weight' => 500,
             'dims' => 'Thảm 100 x 80 cm', 'skills' => 'Ngôn ngữ, Kỹ năng xã hội',
             'desc' => "Thảm vải in bản đồ thành phố với đường phố, bãi đỗ và công viên.\nGấp gọn mang đi được, kèm 10 xe mini bằng nhựa mềm."],
        ];
    }

    // ===================== 3. XE ĐẠP & SCOOTER =====================
    private function bikeToys(): array
    {
        return [
            ['category' => 'Xe Đạp Trẻ Em', 'name' => 'Xe Đạp Trẻ Em 12 Inch Có Bánh Phụ', 'brand' => 'Royal Baby', 'price' => 1850000, 'qty' => 15,
             'age_min' => 24, 'age_max' => 60, 'material' => 'Khung thép & nhựa', 'weight' => 8000, 'warranty' => 12,
             'dims' => 'Bánh 12 inch', 'skills' => 'Vận động thô',
             'desc' => "Xe đạp 12 inch cho bé 2-5 tuổi, có 2 bánh phụ tháo rời khi bé đã vững.\nKhung thép sơn tĩnh điện, yên và ghi-đông điều chỉnh được độ cao."],

            ['category' => 'Xe Đạp Trẻ Em', 'name' => 'Xe Đạp Trẻ Em 16 Inch Kèm Giỏ Trước', 'brand' => 'Royal Baby', 'price' => 2390000, 'qty' => 10,
             'age_min' => 48, 'age_max' => 96, 'material' => 'Khung thép & nhựa', 'weight' => 10000, 'warranty' => 12,
             'dims' => 'Bánh 16 inch', 'skills' => 'Vận động thô',
             'desc' => "Xe đạp 16 inch phanh tay kép, giỏ trước đựng đồ và chắn xích kín an toàn.\nPhù hợp bé cao từ 105cm đến 130cm."],

            ['category' => 'Xe Đạp Trẻ Em', 'name' => 'Xe Đạp Thăng Bằng Không Bàn Đạp', 'brand' => 'Chillafish', 'price' => 1290000, 'qty' => 22,
             'age_min' => 18, 'age_max' => 60, 'material' => 'Nhựa composite', 'origin' => 'Bỉ', 'weight' => 3200, 'warranty' => 12,
             'skills' => 'Vận động thô, Tập trung & kiên nhẫn', 'featured' => true,
             'desc' => "Xe thăng bằng giúp bé học giữ thăng bằng trước khi chuyển sang xe đạp thật.\nThân nhựa siêu nhẹ 3.2kg, bé tự nhấc và dắt xe được."],

            ['category' => 'Scooter & Ván Trượt', 'name' => 'Xe Scooter 3 Bánh Phát Sáng', 'brand' => 'Broller', 'price' => 780000, 'qty' => 34,
             'age_min' => 36, 'age_max' => 120, 'material' => 'Nhôm & nhựa PU', 'weight' => 2600, 'warranty' => 6,
             'skills' => 'Vận động thô',
             'desc' => "Scooter 3 bánh cân bằng tốt, bánh PU phát sáng khi lăn mà không cần pin.\nTay cầm chỉnh 3 nấc độ cao, gấp gọn bỏ cốp xe máy."],

            ['category' => 'Scooter & Ván Trượt', 'name' => 'Ván Trượt Skateboard Cỡ Nhỏ Cho Bé', 'brand' => 'Broller', 'price' => 490000, 'qty' => 26,
             'age_min' => 72, 'age_max' => 168, 'material' => 'Gỗ phong ép 7 lớp', 'gender' => 'boy', 'weight' => 2000,
             'skills' => 'Vận động thô', 'safety' => 'Bắt buộc đội mũ bảo hiểm và đeo bảo hộ khi chơi.',
             'desc' => "Ván trượt 22 inch mặt nhám chống trượt, trục hợp kim chịu tải 70kg.\nKích thước nhỏ gọn vừa với bàn chân của bé tiểu học."],

            ['category' => 'Scooter & Ván Trượt', 'name' => 'Scooter Gấp Gọn 2 Bánh Cho Thiếu Niên', 'brand' => 'Centosy', 'price' => 1150000, 'qty' => 18,
             'age_min' => 96, 'age_max' => null, 'material' => 'Hợp kim nhôm', 'weight' => 3400, 'warranty' => 12,
             'skills' => 'Vận động thô',
             'desc' => "Scooter 2 bánh lớn chạy êm, phanh chân sau và hệ giảm xóc trước.\nGấp gọn bằng một thao tác, tiện mang lên xe buýt hoặc cất trong nhà."],

            ['category' => 'Xe Chòi Chân & Xe Lắc', 'name' => 'Xe Chòi Chân Hình Ô Tô', 'brand' => 'Broller', 'price' => 560000, 'qty' => 30,
             'age_min' => 12, 'age_max' => 48, 'material' => 'Nhựa HDPE', 'origin' => 'Việt Nam', 'weight' => 3000,
             'skills' => 'Vận động thô',
             'desc' => "Xe chòi chân kiểu dáng ô tô thể thao, bánh cao su chạy êm không xước sàn.\nChịu tải đến 30kg, có ngăn chứa đồ dưới yên."],

            ['category' => 'Xe Chòi Chân & Xe Lắc', 'name' => 'Xe Lắc Trẻ Em Có Nhạc', 'brand' => 'Toyshouse', 'price' => 430000, 'qty' => 25,
             'age_min' => 18, 'age_max' => 60, 'material' => 'Nhựa PP', 'origin' => 'Việt Nam', 'weight' => 2800,
             'battery' => '2 pin AA (không kèm theo)', 'skills' => 'Vận động thô',
             'desc' => "Xe lắc chạy bằng động tác xoay vô-lăng, rèn cơ tay và cơ lưng cho bé.\nCó hộp nhạc thiếu nhi kèm đèn nháy ở đầu xe."],

            ['category' => 'Mũ & Đồ Bảo Hộ', 'name' => 'Bộ Mũ Bảo Hiểm Và Giáp Bảo Hộ 7 Món', 'brand' => 'Broller', 'price' => 350000, 'qty' => 48,
             'age_min' => 36, 'age_max' => 144, 'material' => 'Xốp EPS & nhựa PVC', 'weight' => 900,
             'safety' => 'Đo chu vi đầu bé trước khi chọn size để mũ ôm vừa vặn.',
             'desc' => "Bộ bảo hộ gồm mũ, 2 bọc khuỷu tay, 2 bọc đầu gối và 2 bọc cổ tay.\nQuai mũ có khóa nhả nhanh, lót trong tháo giặt được."],

            ['category' => 'Mũ & Đồ Bảo Hộ', 'name' => 'Mũ Bảo Hiểm Trẻ Em Họa Tiết Khủng Long', 'brand' => 'Broller', 'price' => 220000, 'qty' => 60,
             'age_min' => 24, 'age_max' => 96, 'material' => 'Xốp EPS & nhựa ABS', 'weight' => 320,
             'skills' => null, 'safety' => 'Thay mũ mới nếu đã từng va đập mạnh.',
             'desc' => "Mũ bảo hiểm nhẹ 320g với họa tiết khủng long bé thích nên chịu đội.\n11 lỗ thông gió giúp đầu bé không bị hầm khi chơi ngoài trời."],
        ];
    }

    // ===================== 4. ĐỒ CHƠI THEO PHIM =====================
    private function movieToys(): array
    {
        return [
            ['category' => 'Siêu Anh Hùng Marvel & DC', 'name' => 'Mô Hình Người Nhện Khớp Cử Động 30cm', 'brand' => 'Hasbro', 'character' => 'Marvel',
             'price' => 590000, 'qty' => 32, 'age_min' => 48, 'age_max' => 144, 'material' => 'Nhựa PVC', 'origin' => 'Mỹ',
             'gender' => 'boy', 'weight' => 450, 'dims' => 'Cao 30 cm', 'skills' => 'Sáng tạo, Ngôn ngữ', 'featured' => true,
             'desc' => "Mô hình Người Nhện cao 30cm với 22 khớp cử động, tạo được nhiều tư thế.\nChi tiết sơn sắc nét theo tạo hình trong phim."],

            ['category' => 'Siêu Anh Hùng Marvel & DC', 'name' => 'Bộ 6 Mô Hình Avengers Mini', 'brand' => 'Hasbro', 'character' => 'Marvel',
             'price' => 420000, 'qty' => 40, 'age_min' => 48, 'age_max' => 144, 'material' => 'Nhựa PVC', 'origin' => 'Mỹ',
             'gender' => 'boy', 'weight' => 500, 'skills' => 'Sáng tạo, Kỹ năng xã hội',
             'desc' => "Bộ 6 nhân vật Iron Man, Captain America, Hulk, Thor, Black Widow và Hawkeye.\nMỗi nhân vật cao 10cm kèm vũ khí đặc trưng để bé dựng trận chiến."],

            ['category' => 'Siêu Anh Hùng Marvel & DC', 'name' => 'Găng Tay Vô Cực Phát Sáng', 'brand' => 'Hasbro', 'character' => 'Marvel',
             'price' => 680000, 'qty' => 20, 'age_min' => 60, 'age_max' => 144, 'material' => 'Nhựa ABS', 'origin' => 'Mỹ',
             'gender' => 'boy', 'weight' => 600, 'battery' => '3 pin AAA (không kèm theo)', 'skills' => 'Sáng tạo',
             'desc' => "Găng tay đeo vừa tay bé, 6 viên đá phát sáng kèm hiệu ứng âm thanh.\nHóa trang Halloween hoặc sinh nhật đều rất nổi bật."],

            ['category' => 'Công Chúa & Disney', 'name' => 'Búp Bê Công Chúa Elsa Có Váy Phát Sáng', 'brand' => 'Disney', 'character' => 'Frozen',
             'price' => 750000, 'qty' => 28, 'age_min' => 36, 'age_max' => 120, 'material' => 'Nhựa & vải', 'origin' => 'Mỹ',
             'gender' => 'girl', 'weight' => 550, 'battery' => '3 pin LR44 (kèm theo)', 'skills' => 'Sáng tạo, Ngôn ngữ', 'featured' => true,
             'desc' => "Búp bê Elsa cao 30cm, váy dạ hội đính kim tuyến phát sáng theo nhịp nhạc.\nTóc chải được, kèm lược và vương miện."],

            ['category' => 'Công Chúa & Disney', 'name' => 'Bộ 5 Búp Bê Công Chúa Disney Mini', 'brand' => 'Disney', 'character' => 'Disney Princess',
             'price' => 520000, 'qty' => 35, 'age_min' => 36, 'age_max' => 108, 'material' => 'Nhựa PVC & vải', 'origin' => 'Mỹ',
             'gender' => 'girl', 'weight' => 450, 'skills' => 'Ngôn ngữ, Kỹ năng xã hội',
             'desc' => "Bộ 5 nàng công chúa Belle, Ariel, Rapunzel, Cinderella và Jasmine cao 12cm.\nVáy tháo rời được, bé thay đồ và kể chuyện theo trí tưởng tượng."],

            ['category' => 'Công Chúa & Disney', 'name' => 'Nhà Búp Bê Lâu Đài 3 Tầng Có Đèn LED', 'brand' => 'KidKraft', 'character' => 'Disney Princess',
             'price' => 2350000, 'qty' => 8, 'age_min' => 48, 'age_max' => 120, 'material' => 'Gỗ MDF phủ sơn an toàn', 'origin' => 'Việt Nam',
             'gender' => 'girl', 'weight' => 12000, 'dims' => 'Cao 120 cm', 'battery' => '3 pin AA (không kèm theo)',
             'skills' => 'Sáng tạo, Kỹ năng xã hội',
             'desc' => "Lâu đài búp bê 3 tầng cao 1m2 với đầy đủ nội thất mini và đèn LED.\nVán gỗ MDF phủ sơn gốc nước, cạnh bo tròn an toàn."],

            ['category' => 'Anime & Manga', 'name' => 'Mô Hình Doraemon Nhựa PVC 20cm', 'brand' => 'Bandai', 'character' => 'Doraemon',
             'price' => 380000, 'qty' => 42, 'age_min' => 36, 'age_max' => null, 'material' => 'Nhựa PVC', 'origin' => 'Nhật Bản',
             'weight' => 350, 'dims' => 'Cao 20 cm', 'skills' => 'Sáng tạo',
             'desc' => "Mô hình Doraemon tư thế cười tươi cầm bánh rán, sơn màu chuẩn bản quyền.\nĐặt bàn học hoặc kệ sách đều dễ thương."],

            ['category' => 'Anime & Manga', 'name' => 'Bộ 8 Mô Hình Pokémon Mini Sưu Tầm', 'brand' => 'Takara Tomy', 'character' => 'Pokémon',
             'price' => 450000, 'qty' => 36, 'age_min' => 48, 'age_max' => null, 'material' => 'Nhựa PVC', 'origin' => 'Nhật Bản',
             'weight' => 300, 'skills' => 'Sáng tạo, Kỹ năng xã hội',
             'desc' => "Bộ 8 Pokémon quen thuộc gồm Pikachu, Charmander, Squirtle, Bulbasaur và bạn bè.\nMỗi mô hình cao 5-7cm, tỉ lệ đồng bộ để bé xếp thành đội hình."],

            ['category' => 'Nhân Vật Game', 'name' => 'Bộ Mô Hình Minecraft 4 Nhân Vật Kèm Phụ Kiện', 'brand' => 'Mattel', 'character' => 'Minecraft',
             'price' => 560000, 'qty' => 24, 'age_min' => 72, 'age_max' => 168, 'material' => 'Nhựa ABS', 'origin' => 'Mỹ',
             'weight' => 420, 'skills' => 'Sáng tạo, Tư duy logic',
             'desc' => "Bộ 4 nhân vật Steve, Alex, Creeper và Zombie theo phong cách khối vuông.\nKèm cuốc, kiếm và khối quặng để bé tái hiện cảnh trong game."],

            ['category' => 'Nhân Vật Game', 'name' => 'Mô Hình Mario Và Luigi Khớp Cử Động', 'brand' => 'Jakks Pacific', 'character' => 'Super Mario',
             'price' => 490000, 'qty' => 30, 'age_min' => 60, 'age_max' => 168, 'material' => 'Nhựa PVC', 'origin' => 'Mỹ',
             'weight' => 380, 'skills' => 'Sáng tạo',
             'desc' => "Cặp mô hình Mario và Luigi cao 10cm, khớp vai và chân cử động.\nKèm nấm tăng lực và ống cống để bé dựng cảnh."],
        ];
    }

    // ===================== 5. BÚP BÊ & NHÀ BÚP BÊ =====================
    private function dollToys(): array
    {
        return [
            // 3 dòng đầu trùng tên với sản phẩm của bản seed cũ -> updateOrCreate sẽ
            // chuyển chúng vào đúng danh mục con và bổ sung thông số, không tạo bản sao.
            ['category' => 'Búp Bê Thời Trang', 'name' => 'Búp Bê Thời Trang Kèm 5 Bộ Váy', 'brand' => 'Barbie', 'character' => 'Barbie',
             'price' => 650000, 'qty' => 35, 'age_min' => 36, 'age_max' => 96, 'material' => 'Nhựa PVC & vải', 'origin' => 'Mỹ',
             'gender' => 'girl', 'weight' => 500, 'dims' => 'Cao 29 cm', 'skills' => 'Sáng tạo, Kỹ năng xã hội, Ngôn ngữ', 'featured' => true,
             'desc' => "Búp bê cao 29cm, khớp tay chân cử động, kèm 5 bộ trang phục và phụ kiện giày dép.\nBé tự phối đồ theo mùa, theo dịp - vừa chơi vừa tập gu thẩm mỹ."],

            ['category' => 'Nhà Búp Bê & Phụ Kiện', 'name' => 'Nhà Búp Bê 3 Tầng Có Đèn LED', 'brand' => 'KidKraft',
             'price' => 2350000, 'qty' => 8, 'age_min' => 48, 'age_max' => 120, 'material' => 'Gỗ MDF phủ sơn an toàn', 'origin' => 'Việt Nam',
             'gender' => 'girl', 'weight' => 12000, 'dims' => 'Cao 120 cm', 'battery' => '3 pin AA (không kèm theo)',
             'skills' => 'Sáng tạo, Kỹ năng xã hội',
             'desc' => "Nhà búp bê 3 tầng cao 1m2, đầy đủ nội thất mini và đèn LED chạy pin.\nVán gỗ phủ sơn gốc nước, cạnh bo tròn an toàn cho bé."],

            ['category' => 'Búp Bê Vải & Sơ Sinh', 'name' => 'Búp Bê Vải Handmade Cỡ Lớn', 'brand' => 'Mộc Toys',
             'price' => 280000, 'qty' => 40, 'age_min' => 0, 'age_max' => 36, 'material' => 'Vải bông hữu cơ', 'origin' => 'Việt Nam',
             'gender' => 'girl', 'weight' => 400, 'skills' => 'Kỹ năng xã hội',
             'safety' => 'Không có chi tiết nhỏ rời, an toàn cho bé sơ sinh.',
             'desc' => "Búp bê vải khâu tay, ruột bông gòn mềm, mắt mũi thêu chỉ nên không lo bé bứt ra.\nGiặt máy được ở chế độ nhẹ."],

            ['category' => 'Búp Bê Thời Trang', 'name' => 'Búp Bê Barbie Nghề Nghiệp Bác Sĩ', 'brand' => 'Barbie', 'character' => 'Barbie',
             'price' => 420000, 'qty' => 45, 'age_min' => 36, 'age_max' => 108, 'material' => 'Nhựa PVC & vải', 'origin' => 'Mỹ',
             'gender' => 'girl', 'weight' => 300, 'skills' => 'Ngôn ngữ, Kỹ năng xã hội',
             'desc' => "Búp bê trong trang phục bác sĩ kèm ống nghe, bệnh án và bàn khám mini.\nGiúp bé hình dung nghề nghiệp và bớt sợ khi đi khám bệnh."],

            ['category' => 'Búp Bê Thời Trang', 'name' => 'Bộ Búp Bê Và Tủ Quần Áo 20 Món', 'brand' => 'Barbie', 'character' => 'Barbie',
             'price' => 890000, 'qty' => 22, 'age_min' => 48, 'age_max' => 120, 'material' => 'Nhựa & vải', 'origin' => 'Mỹ',
             'gender' => 'girl', 'weight' => 1100, 'pieces' => 20, 'skills' => 'Sáng tạo, Vận động tinh',
             'desc' => "Tủ quần áo mở 2 cánh có móc treo, gương và ngăn kéo, kèm 20 món trang phục - phụ kiện.\nBé học cách sắp xếp đồ đạc gọn gàng qua trò chơi."],

            ['category' => 'Nhà Búp Bê & Phụ Kiện', 'name' => 'Bộ Nội Thất Nhà Búp Bê Gỗ 24 Món', 'brand' => 'Winwintoys',
             'price' => 480000, 'qty' => 30, 'age_min' => 36, 'age_max' => 108, 'material' => 'Gỗ cao su tự nhiên', 'origin' => 'Việt Nam',
             'gender' => 'girl', 'weight' => 1300, 'pieces' => 24, 'skills' => 'Sáng tạo, Kỹ năng xã hội',
             'desc' => "24 món nội thất gỗ cho phòng khách, phòng ngủ, bếp và nhà tắm.\nTỉ lệ chuẩn với hầu hết nhà búp bê đang bán trên thị trường."],

            ['category' => 'Nhà Búp Bê & Phụ Kiện', 'name' => 'Xe Đẩy Búp Bê Gấp Gọn Có Mái Che', 'brand' => 'Toyshouse',
             'price' => 390000, 'qty' => 36, 'age_min' => 24, 'age_max' => 84, 'material' => 'Khung thép & vải', 'weight' => 1800,
             'gender' => 'girl', 'skills' => 'Vận động thô, Kỹ năng xã hội',
             'desc' => "Xe đẩy búp bê như xe đẩy thật, có mái che và giỏ đựng đồ phía dưới.\nGấp gọn một thao tác, bé tự đẩy búp bê đi dạo trong nhà."],

            ['category' => 'Búp Bê Vải & Sơ Sinh', 'name' => 'Búp Bê Sơ Sinh Biết Khóc Và Bú Bình', 'brand' => 'Toyshouse',
             'price' => 350000, 'qty' => 42, 'age_min' => 24, 'age_max' => 84, 'material' => 'Nhựa PVC mềm', 'weight' => 700,
             'gender' => 'girl', 'battery' => '3 pin AA (không kèm theo)', 'skills' => 'Kỹ năng xã hội, Ngôn ngữ',
             'desc' => "Búp bê em bé biết khóc, cười và bú bình, kèm bình sữa - núm ti - tã giả.\nBé tập chăm em, rất hợp cho gia đình sắp đón em bé mới."],

            ['category' => 'Búp Bê Vải & Sơ Sinh', 'name' => 'Bộ 3 Búp Bê Vải Gia Đình Nhỏ', 'brand' => 'Mộc Toys',
             'price' => 320000, 'qty' => 38, 'age_min' => 12, 'age_max' => 72, 'material' => 'Vải bông & bông gòn', 'origin' => 'Việt Nam',
             'weight' => 450, 'skills' => 'Ngôn ngữ, Kỹ năng xã hội',
             'desc' => "Bộ 3 búp bê vải bố - mẹ - con cao 22cm, quần áo tháo ra mặc vào được.\nBé kể chuyện gia đình và học gọi tên các thành viên."],

            ['category' => 'Búp Bê Nhân Vật', 'name' => 'Búp Bê LOL Surprise Hộp Bí Mật', 'brand' => 'MGA', 'character' => 'LOL Surprise',
             'price' => 290000, 'qty' => 60, 'age_min' => 48, 'age_max' => 120, 'material' => 'Nhựa PVC', 'origin' => 'Mỹ',
             'gender' => 'girl', 'weight' => 250, 'skills' => 'Vận động tinh, Sáng tạo',
             'desc' => "Quả cầu bí mật bóc từng lớp mới lộ ra búp bê và phụ kiện bên trong.\nMỗi hộp một nhân vật ngẫu nhiên nên bé rất thích sưu tầm."],

            ['category' => 'Búp Bê Nhân Vật', 'name' => 'Bộ 4 Búp Bê Mini Công Chúa Hoạt Hình', 'brand' => 'Disney', 'character' => 'Disney Princess',
             'price' => 380000, 'qty' => 44, 'age_min' => 36, 'age_max' => 108, 'material' => 'Nhựa PVC & vải', 'origin' => 'Mỹ',
             'gender' => 'girl', 'weight' => 350, 'skills' => 'Ngôn ngữ, Sáng tạo',
             'desc' => "4 nàng công chúa cao 15cm trong trang phục dạ hội, tóc chải và tết được.\nKèm lược, vương miện và đế đứng trưng bày."],
        ];
    }

    // ===================== 6. ROBOT & CÔNG NGHỆ =====================
    private function robotToys(): array
    {
        return [
            ['category' => 'Robot Biến Hình', 'name' => 'Robot Biến Hình Ô Tô Optimus Prime', 'brand' => 'Hasbro', 'character' => 'Transformers',
             'price' => 690000, 'qty' => 26, 'age_min' => 72, 'age_max' => 168, 'material' => 'Nhựa ABS', 'origin' => 'Mỹ',
             'gender' => 'boy', 'weight' => 650, 'skills' => 'Tư duy logic, Vận động tinh', 'featured' => true,
             'desc' => "Robot biến hình qua 18 bước thao tác từ xe tải thành người máy.\nKhớp chắc chắn, không bị lỏng sau nhiều lần biến đổi."],

            ['category' => 'Robot Biến Hình', 'name' => 'Bộ 5 Robot Biến Hình Mini Ghép Thành 1', 'brand' => 'Toyshouse', 'price' => 420000, 'qty' => 34,
             'age_min' => 60, 'age_max' => 144, 'material' => 'Nhựa ABS', 'gender' => 'boy', 'weight' => 700,
             'skills' => 'Tư duy logic, Sáng tạo',
             'desc' => "5 robot nhỏ biến hình riêng, ghép lại thành một robot lớn cao 30cm.\nBé học cách lắp ghép theo thứ tự và ghi nhớ quy trình."],

            ['category' => 'Robot Điều Khiển', 'name' => 'Robot Điều Khiển Từ Xa Biết Nhảy Và Nói', 'brand' => 'JJRC', 'price' => 850000, 'qty' => 22,
             'age_min' => 48, 'age_max' => 144, 'material' => 'Nhựa ABS', 'weight' => 900,
             'battery' => 'Pin sạc tích hợp (kèm cáp USB)', 'warranty' => 6, 'skills' => 'Ngôn ngữ, Sáng tạo',
             'desc' => "Robot cao 30cm biết đi, nhảy theo nhạc và nhại lại giọng nói của bé.\nĐiều khiển bằng remote hồng ngoại hoặc cử chỉ tay."],

            ['category' => 'Robot Điều Khiển', 'name' => 'Robot Chó Cảm Ứng Biết Sủa Và Đi Theo', 'brand' => 'JJRC', 'price' => 620000, 'qty' => 28,
             'age_min' => 36, 'age_max' => 120, 'material' => 'Nhựa ABS', 'weight' => 700,
             'battery' => 'Pin sạc tích hợp (kèm cáp USB)', 'warranty' => 6, 'skills' => 'Kỹ năng xã hội, Ngôn ngữ',
             'desc' => "Chú chó robot phản ứng với tiếng vỗ tay, biết sủa, lắc đuôi và đi theo bé.\nPhù hợp cho gia đình chưa thể nuôi thú cưng thật."],

            ['category' => 'Lập Trình & STEM', 'name' => 'Robot Lập Trình Cho Trẻ Em', 'brand' => 'Makeblock', 'price' => 1650000, 'qty' => 12,
             'age_min' => 72, 'age_max' => 144, 'material' => 'Hợp kim nhôm & nhựa', 'weight' => 1100,
             'battery' => 'Pin sạc Li-ion (kèm theo)', 'warranty' => 12,
             'skills' => 'Tư duy logic, Toán học sớm, Tập trung & kiên nhẫn', 'featured' => true,
             'desc' => "Robot lập trình bằng cách kéo thả khối lệnh trên app điện thoại hoặc máy tính.\nCó cảm biến dò đường và tránh vật cản, dạy bé tư duy thuật toán từ sớm."],

            ['category' => 'Lập Trình & STEM', 'name' => 'Bộ Kit Điện Tử STEM 120 Thí Nghiệm', 'brand' => 'Snap Circuits', 'price' => 1290000, 'qty' => 16,
             'age_min' => 96, 'age_max' => 168, 'material' => 'Nhựa ABS & linh kiện điện tử', 'origin' => 'Mỹ', 'pieces' => 60,
             'weight' => 1500, 'skills' => 'Tư duy logic, Tập trung & kiên nhẫn',
             'safety' => 'Chỉ dùng pin AA, tuyệt đối không cắm vào điện lưới.',
             'desc' => "Bộ linh kiện ghép bằng nút bấm, không cần hàn, thực hiện 120 mạch điện khác nhau.\nSách hướng dẫn giải thích nguyên lý từng mạch bằng hình vẽ dễ hiểu."],

            ['category' => 'Lập Trình & STEM', 'name' => 'Bộ Xe Năng Lượng Mặt Trời Tự Lắp 6 Trong 1', 'brand' => 'Science4you', 'price' => 380000, 'qty' => 40,
             'age_min' => 96, 'age_max' => 168, 'material' => 'Nhựa ABS & pin mặt trời', 'origin' => 'Bồ Đào Nha', 'pieces' => 190,
             'weight' => 500, 'skills' => 'Tư duy logic, Sáng tạo',
             'desc' => "Một bộ linh kiện lắp được 6 mẫu xe và robot chạy bằng pin mặt trời.\nKhông cần pin, bé mang ra sân nắng là chạy được ngay."],

            ['category' => 'Drone & Máy Bay', 'name' => 'Drone Mini Có Camera Cho Người Mới', 'brand' => 'JJRC', 'price' => 1450000, 'qty' => 14,
             'age_min' => 120, 'age_max' => null, 'material' => 'Nhựa ABS', 'weight' => 400,
             'battery' => '2 pin sạc 3.7V (kèm theo)', 'warranty' => 6,
             'skills' => 'Tập trung & kiên nhẫn, Vận động tinh', 'safety' => 'Chơi ở nơi trống, tránh xa người và vật nuôi.',
             'desc' => "Drone gấp gọn có camera 720p, giữ độ cao tự động nên rất dễ bay.\nHai viên pin cho tổng thời gian bay khoảng 20 phút."],

            ['category' => 'Drone & Máy Bay', 'name' => 'Máy Bay Điều Khiển Cánh Xốp 2 Kênh', 'brand' => 'Wltoys', 'price' => 560000, 'qty' => 24,
             'age_min' => 96, 'age_max' => null, 'material' => 'Xốp EPP & nhựa', 'gender' => 'boy', 'weight' => 350,
             'battery' => 'Pin sạc 3.7V (kèm theo)', 'skills' => 'Vận động tinh, Tập trung & kiên nhẫn',
             'desc' => "Máy bay cánh bằng làm từ xốp EPP siêu nhẹ, rơi không vỡ.\nĐiều khiển 2 kênh đơn giản, hợp cho bé mới tập bay."],

            ['category' => 'Drone & Máy Bay', 'name' => 'Trực Thăng Điều Khiển Hồng Ngoại 3.5 Kênh', 'brand' => 'Syma', 'price' => 490000, 'qty' => 30,
             'age_min' => 96, 'age_max' => null, 'material' => 'Hợp kim & nhựa', 'gender' => 'boy', 'weight' => 320,
             'battery' => 'Pin sạc tích hợp (kèm cáp USB)', 'skills' => 'Vận động tinh',
             'desc' => "Trực thăng khung hợp kim bay trong nhà rất ổn định nhờ con quay hồi chuyển.\nCó đèn LED nên chơi buổi tối vẫn nhìn rõ."],
        ];
    }

    // ===================== 6. THẾ GIỚI ĐỘNG VẬT =====================
    private function animalToys(): array
    {
        return [
            ['category' => 'Mô Hình Động Vật', 'name' => 'Bộ 12 Mô Hình Động Vật Hoang Dã', 'brand' => 'Schleich', 'price' => 390000, 'qty' => 50,
             'age_min' => 36, 'age_max' => 120, 'material' => 'Nhựa PVC mềm', 'origin' => 'Đức', 'weight' => 600,
             'skills' => 'Ngôn ngữ, Kỹ năng xã hội',
             'desc' => "12 con vật sư tử, voi, hươu cao cổ, hổ... mô phỏng chi tiết đến từng nếp da.\nKèm thẻ tên tiếng Việt - tiếng Anh giúp bé học từ vựng."],

            ['category' => 'Mô Hình Động Vật', 'name' => 'Bộ Nông Trại Kèm 20 Con Vật Nuôi', 'brand' => 'Schleich', 'price' => 680000, 'qty' => 26,
             'age_min' => 36, 'age_max' => 108, 'material' => 'Nhựa PVC & PP', 'origin' => 'Đức', 'pieces' => 28, 'weight' => 1200,
             'skills' => 'Ngôn ngữ, Kỹ năng xã hội, Sáng tạo',
             'desc' => "Bộ nông trại có chuồng, hàng rào và 20 con vật nuôi quen thuộc.\nBé học tên con vật, tiếng kêu và công việc của bác nông dân."],

            ['category' => 'Khủng Long', 'name' => 'Bộ 8 Mô Hình Khủng Long Cỡ Lớn', 'brand' => 'Jurassic World', 'character' => 'Jurassic World',
             'price' => 450000, 'qty' => 44, 'age_min' => 36, 'age_max' => 132, 'material' => 'Nhựa PVC mềm', 'origin' => 'Mỹ',
             'gender' => 'boy', 'weight' => 900, 'skills' => 'Ngôn ngữ, Sáng tạo', 'featured' => true,
             'desc' => "8 loài khủng long nổi tiếng gồm T-Rex, Triceratops, Brachiosaurus...\nCon lớn nhất dài 25cm, nhựa mềm không có cạnh sắc."],

            ['category' => 'Khủng Long', 'name' => 'Khủng Long T-Rex Điều Khiển Biết Gầm', 'brand' => 'Toyshouse', 'price' => 780000, 'qty' => 18,
             'age_min' => 48, 'age_max' => 144, 'material' => 'Nhựa ABS & silicone', 'gender' => 'boy', 'weight' => 1300,
             'battery' => '4 pin AA (không kèm theo)', 'skills' => 'Sáng tạo',
             'desc' => "T-Rex dài 45cm biết đi, há miệng gầm, mắt phát sáng và phun sương.\nĐiều khiển từ xa trong bán kính 10m."],

            ['category' => 'Khủng Long', 'name' => 'Bộ Khai Quật Hóa Thạch Khủng Long', 'brand' => 'Science4you', 'price' => 260000, 'qty' => 55,
             'age_min' => 72, 'age_max' => 144, 'material' => 'Thạch cao & nhựa', 'origin' => 'Bồ Đào Nha', 'weight' => 700,
             'skills' => 'Tập trung & kiên nhẫn, Tư duy logic',
             'desc' => "Bé dùng búa và đục mini đào khối thạch cao để tìm bộ xương khủng long.\nĐào xong ghép lại thành mô hình hoàn chỉnh, kèm sách kiến thức."],

            ['category' => 'Thú Bông', 'name' => 'Gấu Bông Teddy Lông Xù 60cm', 'brand' => 'Mộc Toys', 'price' => 420000, 'qty' => 60,
             'age_min' => 0, 'age_max' => null, 'material' => 'Vải nhung & bông gòn', 'origin' => 'Việt Nam', 'weight' => 800,
             'dims' => 'Cao 60 cm', 'skills' => 'Kỹ năng xã hội',
             'desc' => "Gấu bông nhồi bông gòn trắng cao cấp, lông mềm không rụng.\nGiặt máy được ở chế độ nhẹ, đường may chắc chắn không bung chỉ."],

            ['category' => 'Thú Bông', 'name' => 'Thú Bông Gối Ôm Hình Mèo Dài 80cm', 'brand' => 'Mộc Toys', 'price' => 350000, 'qty' => 48,
             'age_min' => 12, 'age_max' => null, 'material' => 'Vải nhung & bông', 'origin' => 'Việt Nam', 'weight' => 900,
             'gender' => 'girl', 'skills' => 'Kỹ năng xã hội',
             'desc' => "Gối ôm hình mèo dài 80cm, vừa làm bạn ngủ vừa làm gối tựa lưng.\nVỏ gối tháo rời được để giặt riêng."],

            ['category' => 'Thú Bông', 'name' => 'Bộ 4 Thú Bông Mini Treo Ba Lô', 'brand' => 'Mộc Toys', 'price' => 180000, 'qty' => 80,
             'age_min' => 36, 'age_max' => null, 'material' => 'Vải nỉ & bông', 'origin' => 'Việt Nam', 'weight' => 200,
             'skills' => 'Kỹ năng xã hội',
             'desc' => "4 thú bông nhỏ 12cm có móc khóa để treo ba lô hoặc cặp sách.\nMón quà nhỏ dễ thương cho bé tặng bạn bè."],

            ['category' => 'Đại Dương & Côn Trùng', 'name' => 'Bộ 16 Mô Hình Sinh Vật Biển', 'brand' => 'Schleich', 'price' => 320000, 'qty' => 52,
             'age_min' => 36, 'age_max' => 120, 'material' => 'Nhựa PVC mềm', 'origin' => 'Đức', 'weight' => 500,
             'skills' => 'Ngôn ngữ, Tư duy logic',
             'desc' => "16 sinh vật biển từ cá heo, cá mập đến sao biển và rùa biển.\nThả nổi được trong bồn tắm, giúp giờ tắm của bé vui hơn."],

            ['category' => 'Đại Dương & Côn Trùng', 'name' => 'Bộ Quan Sát Côn Trùng Kèm Kính Lúp', 'brand' => 'Science4you', 'price' => 230000, 'qty' => 46,
             'age_min' => 60, 'age_max' => 144, 'material' => 'Nhựa PP & thấu kính', 'origin' => 'Bồ Đào Nha', 'weight' => 400,
             'skills' => 'Tư duy logic, Tập trung & kiên nhẫn',
             'desc' => "Hộp nuôi quan sát có nắp lưới thoáng khí, kèm kính lúp phóng đại 5x và vợt.\nSách nhỏ hướng dẫn bé quan sát và thả côn trùng về tự nhiên."],
        ];
    }

    // ===================== 7. ĐỒ DÙNG HỌC TẬP =====================
    private function studyToys(): array
    {
        return [
            ['category' => 'Bảng & Bút Viết', 'name' => 'Bảng Vẽ Điện Tử LCD 12 Inch Xóa Nhanh', 'brand' => 'Winwintoys', 'price' => 190000, 'qty' => 90,
             'age_min' => 36, 'age_max' => 144, 'material' => 'Nhựa ABS & màn LCD', 'origin' => 'Việt Nam', 'weight' => 250,
             'battery' => '1 pin CR2025 (kèm theo)', 'skills' => 'Sáng tạo, Vận động tinh', 'featured' => true,
             'desc' => "Bảng LCD 12 inch viết vẽ thoải mái, bấm một nút là xóa sạch.\nKhông mực, không bụi phấn, một viên pin dùng được khoảng một năm."],

            ['category' => 'Bảng & Bút Viết', 'name' => 'Bảng Gỗ 2 Mặt Đứng Có Chân', 'brand' => 'Winwintoys', 'price' => 560000, 'qty' => 24,
             'age_min' => 36, 'age_max' => 120, 'material' => 'Gỗ cao su tự nhiên', 'origin' => 'Việt Nam', 'weight' => 4500,
             'dims' => 'Cao 110 cm', 'skills' => 'Sáng tạo, Vận động tinh',
             'desc' => "Bảng đứng 2 mặt: một mặt phấn, một mặt bút lông, có khay đựng dụng cụ.\nChân gỗ chắc chắn, chỉnh được độ cao theo chiều cao của bé."],

            ['category' => 'Học Chữ & Học Số', 'name' => 'Bảng Chữ Cái & Số Học Nam Châm', 'brand' => 'Winwintoys', 'price' => 195000, 'qty' => 120,
             'age_min' => 36, 'age_max' => 72, 'material' => 'Gỗ cao su tự nhiên', 'origin' => 'Việt Nam', 'pieces' => 78, 'weight' => 900,
             'skills' => 'Ngôn ngữ, Toán học sớm',
             'desc' => "Bảng gỗ nam châm 2 mặt kèm bộ chữ cái tiếng Việt và số rời.\nBé ghép từ, làm phép tính và tự kiểm tra kết quả."],

            ['category' => 'Học Chữ & Học Số', 'name' => 'Bộ Thẻ Học Thông Minh 200 Thẻ Song Ngữ', 'brand' => 'Glenn Doman', 'price' => 150000, 'qty' => 140,
             'age_min' => 12, 'age_max' => 72, 'material' => 'Giấy couche cán màng', 'origin' => 'Việt Nam', 'pieces' => 200, 'weight' => 600,
             'skills' => 'Ngôn ngữ, Ghi nhớ',
             'desc' => "200 thẻ hình chủ đề con vật, đồ vật, phương tiện, nghề nghiệp.\nMỗi thẻ có từ tiếng Việt và tiếng Anh kèm phiên âm."],

            ['category' => 'Học Chữ & Học Số', 'name' => 'Bàn Tính Gỗ Soroban 100 Hạt', 'brand' => 'Winwintoys', 'price' => 175000, 'qty' => 85,
             'age_min' => 48, 'age_max' => 120, 'material' => 'Gỗ tự nhiên', 'origin' => 'Việt Nam', 'weight' => 500,
             'skills' => 'Toán học sớm, Tư duy logic',
             'desc' => "Bàn tính 10 hàng x 10 hạt gỗ đủ màu, hạt xoay trơn không kẹt.\nHọc đếm, cộng trừ và làm quen với khái niệm hàng chục hàng đơn vị."],

            ['category' => 'Thí Nghiệm Khoa Học', 'name' => 'Bộ Thí Nghiệm Khoa Học 60 Thí Nghiệm', 'brand' => 'Science4you', 'price' => 780000, 'qty' => 22,
             'age_min' => 96, 'age_max' => 168, 'material' => 'Nhựa & thủy tinh chịu nhiệt', 'origin' => 'Bồ Đào Nha', 'weight' => 1600,
             'skills' => 'Tư duy logic, Tập trung & kiên nhẫn',
             'safety' => 'Cần người lớn giám sát khi làm thí nghiệm có hóa chất.',
             'desc' => "Bộ dụng cụ thực hiện 60 thí nghiệm hóa học và vật lý an toàn tại nhà.\nSách hướng dẫn tiếng Việt giải thích hiện tượng theo ngôn ngữ trẻ em."],

            ['category' => 'Thí Nghiệm Khoa Học', 'name' => 'Kính Hiển Vi Học Sinh Phóng Đại 1200x', 'brand' => 'Science4you', 'price' => 890000, 'qty' => 18,
             'age_min' => 108, 'age_max' => null, 'material' => 'Kim loại & thấu kính quang học', 'origin' => 'Bồ Đào Nha', 'weight' => 1800,
             'skills' => 'Tư duy logic, Tập trung & kiên nhẫn',
             'desc' => "Kính hiển vi 3 mức phóng đại tối đa 1200x, đèn LED chiếu sáng mẫu vật.\nKèm 10 lam kính mẫu, nhíp và ống nhỏ giọt."],

            ['category' => 'Thí Nghiệm Khoa Học', 'name' => 'Bộ Trồng Cây Mini Quan Sát Hạt Nảy Mầm', 'brand' => 'Science4you', 'price' => 210000, 'qty' => 65,
             'age_min' => 60, 'age_max' => 132, 'material' => 'Nhựa trong & giá thể', 'origin' => 'Bồ Đào Nha', 'weight' => 600,
             'skills' => 'Tập trung & kiên nhẫn, Tư duy logic',
             'desc' => "Chậu trong suốt cho bé nhìn rõ rễ cây phát triển từng ngày.\nKèm hạt giống, giá thể và nhật ký theo dõi để bé ghi chép."],

            ['category' => 'Dụng Cụ Mỹ Thuật', 'name' => 'Bộ Màu Vẽ 150 Chi Tiết Trong Vali', 'brand' => 'Colokit', 'price' => 340000, 'qty' => 70,
             'age_min' => 48, 'age_max' => 168, 'material' => 'Sáp, chì màu & màu nước', 'origin' => 'Việt Nam', 'pieces' => 150, 'weight' => 1400,
             'skills' => 'Sáng tạo, Vận động tinh',
             'desc' => "Vali nhựa đựng 150 món: sáp màu, chì màu, bút lông, màu nước và cọ.\nMàu gốc nước không độc, rửa sạch bằng nước ấm."],

            ['category' => 'Dụng Cụ Mỹ Thuật', 'name' => 'Bộ Tranh Cát Tự Làm 12 Bức', 'brand' => 'Colokit', 'price' => 160000, 'qty' => 95,
             'age_min' => 48, 'age_max' => 120, 'material' => 'Giấy bìa & cát màu', 'origin' => 'Việt Nam', 'pieces' => 12, 'weight' => 800,
             'skills' => 'Sáng tạo, Vận động tinh, Tập trung & kiên nhẫn',
             'desc' => "12 bức tranh in sẵn có lớp keo, bé bóc từng mảng rồi rắc cát màu.\nKhông cần kéo hay keo dán, bé 4 tuổi làm được một mình."],
        ];
    }

    // ===================== 8. ĐỒ CHƠI CHO BÉ =====================
    private function babyToys(): array
    {
        return [
            ['category' => 'Đồ Chơi Sơ Sinh', 'name' => 'Thảm Chơi Nhạc Có Gọng Treo', 'brand' => 'Fisher-Price', 'price' => 890000, 'qty' => 20,
             'age_min' => 0, 'age_max' => 12, 'material' => 'Vải polyester & nhựa PP', 'origin' => 'Mỹ', 'weight' => 1600,
             'battery' => '3 pin AA (không kèm theo)', 'skills' => 'Vận động thô, Ngôn ngữ', 'featured' => true,
             'safety' => 'Luôn có người lớn quan sát khi bé nằm chơi.',
             'desc' => "Thảm nằm chơi có gọng treo đồ chơi, phát nhạc và đèn dịu mắt.\nKích thích thị giác, thính giác và động tác với tay của bé sơ sinh."],

            ['category' => 'Đồ Chơi Sơ Sinh', 'name' => 'Treo Nôi Xoay Nhạc Hình Thú', 'brand' => 'Fisher-Price', 'price' => 520000, 'qty' => 30,
             'age_min' => 0, 'age_max' => 9, 'material' => 'Vải & nhựa ABS', 'origin' => 'Mỹ', 'weight' => 700,
             'battery' => '3 pin AA (không kèm theo)', 'skills' => 'Ngôn ngữ',
             'desc' => "Cần treo kẹp vào thành nôi, các bạn thú xoay tròn theo nhạc ru êm dịu.\n12 bản nhạc và hẹn giờ tắt tự động sau 20 phút."],

            ['category' => 'Đồ Chơi Sơ Sinh', 'name' => 'Sách Vải Mềm Cho Bé 6 Trang', 'brand' => 'Mộc Toys', 'price' => 135000, 'qty' => 110,
             'age_min' => 0, 'age_max' => 24, 'material' => 'Vải bông hữu cơ', 'origin' => 'Việt Nam', 'weight' => 150,
             'skills' => 'Ngôn ngữ, Vận động tinh',
             'desc' => "Sách vải 6 trang in hình con vật, có phần sột soạt và gương an toàn.\nGiặt máy được, không sợ bé cắn rách hay đau tay."],

            ['category' => 'Xúc Xắc & Gặm Nướu', 'name' => 'Bộ Xúc Xắc Gặm Nướu 6 Món', 'brand' => 'Winwintoys', 'price' => 165000, 'qty' => 90,
             'age_min' => 0, 'age_max' => 18, 'material' => 'Silicone y tế & gỗ', 'origin' => 'Việt Nam', 'pieces' => 6, 'weight' => 300,
             'skills' => 'Vận động tinh', 'safety' => 'Tiệt trùng bằng nước sôi trước lần dùng đầu tiên.',
             'desc' => "Bộ 6 món kết hợp xúc xắc và gặm nướu, chất liệu đạt chuẩn an toàn cho bé.\nKích cỡ vừa tay bé cầm, bề mặt gai massage nướu khi mọc răng."],

            ['category' => 'Xúc Xắc & Gặm Nướu', 'name' => 'Gặm Nướu Silicone Hình Hươu Cao Cổ', 'brand' => 'Mộc Toys', 'price' => 95000, 'qty' => 150,
             'age_min' => 3, 'age_max' => 18, 'material' => 'Silicone y tế', 'origin' => 'Việt Nam', 'weight' => 80,
             'skills' => 'Vận động tinh', 'safety' => 'Thay mới nếu phát hiện vết rách trên bề mặt.',
             'desc' => "Gặm nướu một khối silicone mềm, không mối nối nên không đọng nước bên trong.\nCổ dài vừa tay bé cầm, chịu được nhiệt khi luộc tiệt trùng."],

            ['category' => 'Xe Tập Đi & Thảm Chơi', 'name' => 'Xe Tập Đi Đa Năng Có Nhạc', 'brand' => 'Umoo', 'price' => 720000, 'qty' => 14,
             'age_min' => 9, 'age_max' => 24, 'material' => 'Nhựa PP cao cấp', 'origin' => 'Việt Nam', 'weight' => 3500,
             'battery' => '2 pin AA (không kèm theo)', 'warranty' => 6, 'skills' => 'Vận động thô, Ngôn ngữ',
             'safety' => 'Chỉ dùng trên mặt sàn phẳng, tránh xa cầu thang.',
             'desc' => "Xe tập đi kèm bảng hoạt động phát nhạc và dạy chữ số.\nCó phanh hãm tốc độ và khung chống lật khi bé đẩy nhanh."],

            ['category' => 'Xe Tập Đi & Thảm Chơi', 'name' => 'Thảm Xốp Ghép 10 Miếng Hình Chữ Số', 'brand' => 'Toyshouse', 'price' => 280000, 'qty' => 70,
             'age_min' => 6, 'age_max' => 72, 'material' => 'Xốp EVA không mùi', 'origin' => 'Việt Nam', 'pieces' => 10, 'weight' => 2200,
             'dims' => 'Mỗi miếng 30 x 30 cm', 'skills' => 'Toán học sớm, Vận động thô',
             'desc' => "10 miếng xốp EVA dày 1cm ghép lại thành khu vui chơi 90x100cm.\nSố ở giữa gỡ ra được để bé vừa chơi ghép hình vừa học đếm."],

            ['category' => 'Xe Tập Đi & Thảm Chơi', 'name' => 'Xe Đẩy Tập Đi Bằng Gỗ Kèm Khối Xếp Hình', 'brand' => 'Winwintoys', 'price' => 690000, 'qty' => 18,
             'age_min' => 10, 'age_max' => 36, 'material' => 'Gỗ cao su tự nhiên', 'origin' => 'Việt Nam', 'pieces' => 30, 'weight' => 4000,
             'skills' => 'Vận động thô, Vận động tinh', 'featured' => true,
             'desc' => "Xe đẩy gỗ chắc chắn giúp bé vịn tập đi, kèm 30 khối gỗ nhiều hình dạng.\nBánh xe bọc cao su chạy êm và không làm xước sàn."],

            ['category' => 'Đồ Chơi Nhà Tắm', 'name' => 'Bộ Đồ Chơi Nhà Tắm 10 Món Phun Nước', 'brand' => 'Toyshouse', 'price' => 145000, 'qty' => 130,
             'age_min' => 6, 'age_max' => 48, 'material' => 'Nhựa PVC mềm', 'weight' => 300,
             'skills' => 'Vận động tinh', 'safety' => 'Vắt khô sau khi chơi để tránh nấm mốc bên trong.',
             'desc' => "10 con vật nhựa mềm bóp là phun nước, nổi tốt trên mặt nước.\nGiờ tắm trở thành giờ chơi, bé hết sợ nước."],

            ['category' => 'Đồ Chơi Nhà Tắm', 'name' => 'Vòi Sen Đồ Chơi Có Bể Phun Mưa', 'brand' => 'Toyshouse', 'price' => 260000, 'qty' => 55,
             'age_min' => 12, 'age_max' => 72, 'material' => 'Nhựa ABS', 'weight' => 600,
             'battery' => '2 pin AA (không kèm theo)', 'skills' => 'Vận động tinh, Sáng tạo',
             'desc' => "Bộ vòi sen chạy pin bơm nước tuần hoàn tạo mưa rơi trong bồn tắm.\nGắn tường bằng giác hút, tháo lắp trong vài giây."],
        ];
    }

    // ===================== 9. VẬN ĐỘNG NGOÀI TRỜI =====================
    private function outdoorToys(): array
    {
        return [
            ['category' => 'Bóng & Thể Thao', 'name' => 'Bộ Bóng Rổ Đứng Điều Chỉnh Độ Cao', 'brand' => 'Broller', 'price' => 690000, 'qty' => 26,
             'age_min' => 36, 'age_max' => 144, 'material' => 'Nhựa PP & thép', 'weight' => 4500,
             'dims' => 'Cao 90 - 160 cm', 'skills' => 'Vận động thô, Kỹ năng xã hội', 'featured' => true,
             'desc' => "Trụ bóng rổ chỉnh cao từ 90cm đến 1m6 nên dùng được nhiều năm.\nĐế đổ nước hoặc cát cho vững, kèm bóng và bơm tay."],

            ['category' => 'Bóng & Thể Thao', 'name' => 'Bộ Vợt Cầu Lông Trẻ Em Kèm 3 Quả Cầu', 'brand' => 'Broller', 'price' => 180000, 'qty' => 70,
             'age_min' => 48, 'age_max' => 144, 'material' => 'Hợp kim nhôm & nhựa', 'weight' => 400,
             'skills' => 'Vận động thô, Kỹ năng xã hội',
             'desc' => "Cặp vợt ngắn nhẹ 85g vừa tay bé, cán bọc cao su chống trượt.\nKèm 3 quả cầu nhựa bền, chơi được cả trong nhà."],

            ['category' => 'Bóng & Thể Thao', 'name' => 'Bóng Đá Số 4 Khâu Tay Cho Trẻ Em', 'brand' => 'Động Lực', 'price' => 230000, 'qty' => 80,
             'age_min' => 60, 'age_max' => 168, 'material' => 'Da PU & cao su', 'origin' => 'Việt Nam', 'weight' => 400,
             'gender' => 'boy', 'skills' => 'Vận động thô, Kỹ năng xã hội',
             'desc' => "Bóng size 4 đúng chuẩn cho lứa tuổi tiểu học, khâu tay bền đường chỉ.\nRuột cao su giữ hơi tốt, đá sân cỏ nhân tạo và sân xi măng đều được."],

            ['category' => 'Đồ Chơi Bãi Biển & Nước', 'name' => 'Bộ Đồ Chơi Cát Biển 12 Món', 'brand' => 'Toyshouse', 'price' => 145000, 'qty' => 150,
             'age_min' => 24, 'age_max' => 96, 'material' => 'Nhựa PP an toàn', 'weight' => 700,
             'pieces' => 12, 'skills' => 'Sáng tạo, Vận động tinh',
             'desc' => "12 món gồm xẻng, xô, cào, khuôn cát hình lâu đài và bình tưới.\nNhựa dày không gãy, màu tươi không phai dưới nắng."],

            ['category' => 'Đồ Chơi Bãi Biển & Nước', 'name' => 'Súng Phun Nước Áp Lực 600ml', 'brand' => 'Toyshouse', 'price' => 120000, 'qty' => 100,
             'age_min' => 36, 'age_max' => 144, 'material' => 'Nhựa ABS', 'weight' => 350,
             'skills' => 'Vận động thô', 'safety' => 'Không xịt vào mặt hoặc mắt người khác.',
             'desc' => "Bình chứa 600ml, tia nước xa tới 8m nhờ cơ cấu bơm áp lực.\nKhông dùng pin, chỉ cần kéo đẩy tay cầm."],

            ['category' => 'Đồ Chơi Bãi Biển & Nước', 'name' => 'Phao Bơi Trẻ Em Có Mái Che Nắng', 'brand' => 'Intex', 'price' => 290000, 'qty' => 45,
             'age_min' => 12, 'age_max' => 48, 'material' => 'Nhựa PVC dày', 'origin' => 'Mỹ', 'weight' => 800,
             'safety' => 'Luôn có người lớn ở cạnh khi bé xuống nước.',
             'desc' => "Phao ngồi có đai an toàn giữa 2 chân và mái che chống nắng tháo rời.\nHai khoang khí độc lập, nếu một khoang xì thì khoang còn lại vẫn nổi."],

            ['category' => 'Nhà Bóng & Lều', 'name' => 'Lều Bóng Kèm 100 Quả Bóng Nhựa', 'brand' => 'Toyshouse', 'price' => 480000, 'qty' => 26,
             'age_min' => 12, 'age_max' => 72, 'material' => 'Vải lưới & nhựa PE', 'origin' => 'Việt Nam', 'weight' => 3000,
             'skills' => 'Vận động thô, Kỹ năng xã hội',
             'desc' => "Lều chơi dựng và xếp lại trong 30 giây, kèm 100 quả bóng nhựa nhiều màu.\nVải lưới thoáng khí, bố mẹ vẫn nhìn thấy bé bên trong."],

            ['category' => 'Nhà Bóng & Lều', 'name' => 'Lều Công Chúa Hình Lâu Đài Kèm Đèn Sao', 'brand' => 'Toyshouse', 'price' => 620000, 'qty' => 20,
             'age_min' => 24, 'age_max' => 96, 'material' => 'Vải polyester & khung thép', 'origin' => 'Việt Nam',
             'gender' => 'girl', 'weight' => 2600, 'dims' => '135 x 105 x 135 cm', 'skills' => 'Sáng tạo, Kỹ năng xã hội',
             'desc' => "Lều hình lâu đài cao 1m35 có cửa sổ lưới và dây đèn sao chạy pin.\nGóc riêng để bé đọc sách và chơi đồ hàng trong nhà."],

            ['category' => 'Cầu Trượt & Xích Đu', 'name' => 'Cầu Trượt Nhựa Trong Nhà Cỡ Nhỏ', 'brand' => 'Broller', 'price' => 1450000, 'qty' => 12,
             'age_min' => 18, 'age_max' => 72, 'material' => 'Nhựa HDPE', 'origin' => 'Việt Nam', 'weight' => 9000,
             'dims' => '150 x 60 x 90 cm', 'skills' => 'Vận động thô',
             'safety' => 'Đặt trên thảm mềm và có người lớn trông chừng.',
             'desc' => "Cầu trượt nhựa nguyên khối, mặt trượt nhám chống trơn và tay vịn hai bên.\nChịu tải 50kg, tháo lắp không cần dụng cụ."],

            ['category' => 'Cầu Trượt & Xích Đu', 'name' => 'Xích Đu Gỗ Treo Có Tựa Lưng', 'brand' => 'Winwintoys', 'price' => 560000, 'qty' => 22,
             'age_min' => 18, 'age_max' => 84, 'material' => 'Gỗ cao su & dây dù', 'origin' => 'Việt Nam', 'weight' => 3000,
             'skills' => 'Vận động thô',
             'safety' => 'Kiểm tra điểm treo chịu lực trước mỗi lần cho bé chơi.',
             'desc' => "Ghế xích đu gỗ có tựa lưng và thanh chắn trước, dây dù chịu tải 80kg.\nĐiều chỉnh được độ dài dây để treo ở nhiều độ cao khác nhau."],
        ];
    }

    // ===================== 10. BOARD GAME & NHẬP VAI =====================
    private function boardGameToys(): array
    {
        return [
            ['category' => 'Board Game Gia Đình', 'name' => 'Board Game Cờ Tỷ Phú Phiên Bản Việt Nam', 'brand' => 'Hasbro', 'price' => 350000, 'qty' => 55,
             'age_min' => 96, 'age_max' => null, 'material' => 'Bìa cứng & nhựa', 'origin' => 'Việt Nam', 'weight' => 1200,
             'skills' => 'Toán học sớm, Tư duy logic, Kỹ năng xã hội', 'featured' => true,
             'desc' => "Cờ tỷ phú với bản đồ các thành phố Việt Nam, luật chơi in tiếng Việt.\nCả nhà chơi được 2-6 người, mỗi ván khoảng 60 phút."],

            ['category' => 'Board Game Gia Đình', 'name' => 'Board Game Rút Gỗ 54 Thanh', 'brand' => 'Winwintoys', 'price' => 165000, 'qty' => 85,
             'age_min' => 60, 'age_max' => null, 'material' => 'Gỗ tự nhiên', 'origin' => 'Việt Nam', 'pieces' => 54, 'weight' => 900,
             'skills' => 'Vận động tinh, Tập trung & kiên nhẫn, Kỹ năng xã hội',
             'desc' => "54 thanh gỗ bào nhẵn, xếp tháp rồi lần lượt rút ra mà không làm đổ.\nLuật đơn giản nên bé 5 tuổi đến người lớn đều chơi chung được."],

            ['category' => 'Board Game Gia Đình', 'name' => 'Board Game Cá Ngựa Nam Châm Gấp Gọn', 'brand' => 'Winwintoys', 'price' => 120000, 'qty' => 95,
             'age_min' => 60, 'age_max' => null, 'material' => 'Nhựa & nam châm', 'origin' => 'Việt Nam', 'weight' => 400,
             'skills' => 'Toán học sớm, Kỹ năng xã hội',
             'desc' => "Bàn cờ cá ngựa nam châm gấp đôi, quân cờ hít chặt không rơi khi di chuyển.\nNhỏ gọn bỏ vừa ba lô, hợp mang đi du lịch."],

            ['category' => 'Cờ & Trí Tuệ', 'name' => 'Bộ Cờ Vua Nam Châm Gấp Gọn', 'brand' => 'Winwintoys', 'price' => 190000, 'qty' => 75,
             'age_min' => 72, 'age_max' => null, 'material' => 'Nhựa & nam châm', 'origin' => 'Việt Nam', 'pieces' => 32, 'weight' => 600,
             'skills' => 'Tư duy logic, Tập trung & kiên nhẫn',
             'desc' => "Bàn cờ vua gấp đôi thành hộp đựng quân, quân cờ có nam châm.\nKèm sách mỏng hướng dẫn nước đi của từng quân cho bé mới học."],

            ['category' => 'Cờ & Trí Tuệ', 'name' => 'Rubik 3x3 Tốc Độ Kèm Sách Công Thức', 'brand' => 'GAN', 'price' => 260000, 'qty' => 90,
             'age_min' => 72, 'age_max' => null, 'material' => 'Nhựa ABS', 'weight' => 120,
             'skills' => 'Tư duy logic, Vận động tinh, Tập trung & kiên nhẫn',
             'desc' => "Rubik xoay nhẹ và trơn, có thể chỉnh độ lỏng theo ý người chơi.\nSách công thức minh họa từng bước, bé tự giải được sau vài buổi tập."],

            ['category' => 'Cờ & Trí Tuệ', 'name' => 'Bộ Sudoku Gỗ Kèm 100 Đề Bài', 'brand' => 'Winwintoys', 'price' => 210000, 'qty' => 60,
             'age_min' => 84, 'age_max' => null, 'material' => 'Gỗ tự nhiên', 'origin' => 'Việt Nam', 'pieces' => 81, 'weight' => 800,
             'skills' => 'Tư duy logic, Toán học sớm, Tập trung & kiên nhẫn',
             'desc' => "Bảng gỗ 9x9 với 81 khối số rời, kèm tập 100 đề từ dễ đến khó.\nChơi không cần bút giấy nên làm đi làm lại thoải mái."],

            ['category' => 'Bài & Thẻ Bài', 'name' => 'Bộ Bài Uno Chính Hãng', 'brand' => 'Mattel', 'price' => 95000, 'qty' => 160,
             'age_min' => 84, 'age_max' => null, 'material' => 'Giấy cán màng', 'origin' => 'Mỹ', 'pieces' => 112, 'weight' => 150,
             'skills' => 'Kỹ năng xã hội, Toán học sớm',
             'desc' => "Bộ bài Uno 112 lá cho 2-10 người chơi, luật dễ học trong 5 phút.\nMón đồ chơi gọn nhẹ mà cả nhà chơi được hàng giờ."],

            ['category' => 'Bài & Thẻ Bài', 'name' => 'Thẻ Bài Pokémon Bộ Khởi Đầu 60 Lá', 'brand' => 'Pokémon', 'character' => 'Pokémon',
             'price' => 280000, 'qty' => 70, 'age_min' => 72, 'age_max' => null, 'material' => 'Giấy cán màng', 'origin' => 'Nhật Bản',
             'pieces' => 60, 'weight' => 200, 'skills' => 'Tư duy logic, Kỹ năng xã hội',
             'desc' => "Bộ bài khởi đầu 60 lá đã dựng sẵn, chơi được ngay mà không cần mua thêm.\nKèm bảng tính điểm và hướng dẫn luật cơ bản."],

            ['category' => 'Đồ Chơi Đóng Vai', 'name' => 'Bộ Đồ Chơi Bác Sĩ 25 Món Trong Vali', 'brand' => 'Winwintoys', 'price' => 290000, 'qty' => 65,
             'age_min' => 36, 'age_max' => 96, 'material' => 'Nhựa ABS', 'origin' => 'Việt Nam', 'pieces' => 25, 'weight' => 900,
             'skills' => 'Kỹ năng xã hội, Ngôn ngữ, Sáng tạo',
             'desc' => "Vali bác sĩ 25 món có ống nghe kêu thật, máy đo huyết áp và bộ tiêm giả.\nBé tập đóng vai bác sĩ nên bớt sợ khi đi khám bệnh thật."],

            ['category' => 'Đồ Chơi Đóng Vai', 'name' => 'Bộ Bếp Nấu Ăn Gỗ Kèm 30 Phụ Kiện', 'brand' => 'Winwintoys', 'price' => 1290000, 'qty' => 16,
             'age_min' => 36, 'age_max' => 108, 'material' => 'Gỗ cao su tự nhiên', 'origin' => 'Việt Nam', 'pieces' => 30, 'weight' => 8000,
             'dims' => '60 x 30 x 90 cm', 'skills' => 'Kỹ năng xã hội, Sáng tạo, Ngôn ngữ',
             'desc' => "Bộ bếp gỗ có bếp từ giả phát sáng, vòi nước, lò nướng và tủ lạnh mini.\nKèm 30 phụ kiện nồi chảo và thực phẩm gỗ để bé chơi đồ hàng."],
        ];
    }
}
