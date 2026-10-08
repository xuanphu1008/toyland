<?php

namespace App\Services;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\Wishlist;
use Illuminate\Support\Facades\DB;

/**
 * Gộp các cặp sản phẩm mẫu bị trùng (cùng một món nhưng đặt tên hơi khác).
 *
 * Hai kiểu:
 *  - DUPLICATES: bản sao thật sự -> giữ sản phẩm chính, dồn đơn hàng / đánh giá /
 *    yêu thích của bản sao sang sản phẩm chính rồi xóa bản sao.
 *  - VARIANTS: thực chất là hai phiên bản (cỡ, số lượng chi tiết...) của cùng
 *    một món -> sản phẩm chính trở thành sản phẩm có phân loại; mỗi bản cũ thành
 *    một phân loại, đơn hàng cũ được gắn đúng phân loại.
 *
 * Chạy lại nhiều lần an toàn (bản đã gộp không còn nên bị bỏ qua). Seeder cũng
 * dùng danh sách này để không tạo lại các bản đã gộp.
 */
class ProductMerger
{
    /** tên sản phẩm chính => [các tên bản sao] */
    public const DUPLICATES = [
        'Mô Hình Doraemon Chuông Cổ 20cm' => ['Mô Hình Doraemon Nhựa PVC 20cm'],
        'Board Game Rút Gỗ 54 Thanh' => ['Board Game Rút Gỗ 54 Thanh Kèm Xúc Xắc Màu'],
        'Bộ Vợt Cầu Lông Trẻ Em Kèm 3 Quả Cầu' => ['Bộ Vợt Cầu Lông Trẻ Em Kèm Cầu'],
        'Bộ Bóng Rổ Chỉnh Độ Cao 1.6m' => ['Bộ Bóng Rổ Đứng Điều Chỉnh Độ Cao'],
        'Búp Bê LOL Surprise Hộp Bí Mật' => ['Búp Bê LOL Surprise Hộp Bóc Bất Ngờ'],
        'Búp Bê Công Chúa Elsa Có Váy Phát Sáng' => ['Búp Bê Elsa Frozen Váy Dạ Quang'],
        'Bảng Vẽ Điện Tử LCD 12 Inch Xóa Một Chạm' => ['Bảng Vẽ Điện Tử LCD 12 Inch Xóa Nhanh'],
        'Bảng Gỗ 2 Mặt Đứng Có Chân' => ['Bảng Từ Hai Mặt Có Chân Đứng'],
        'Cầu Trượt Nhựa Trong Nhà Cỡ Nhỏ' => ['Cầu Trượt Nhựa Trong Nhà Cho Bé'],
        'Bộ Sudoku Gỗ Kèm 100 Đề Bài' => ['Bộ Sudoku Gỗ Cho Bé Kèm 100 Đề'],
        'Rubik 3x3 Tốc Độ Kèm Sách Công Thức' => ['Rubik 3x3 Tốc Độ Kèm Sách Hướng Dẫn'],
        'Drone Mini Có Camera 1080p Gập Gọn' => ['Drone Mini Có Camera Cho Người Mới'],
        'Máy Bay Điều Khiển Cánh Bằng Xốp EPP' => ['Máy Bay Điều Khiển Cánh Xốp 2 Kênh'],
        'Trực Thăng Điều Khiển Hồng Ngoại 3.5 Kênh' => ['Trực Thăng Điều Khiển Hợp Kim 3.5 Kênh'],
        'Bộ Tranh Cát Tự Dán 12 Bức' => ['Bộ Tranh Cát Tự Làm 12 Bức'],
        'Bộ Màu Vẽ 150 Chi Tiết Trong Vali' => ['Hộp Màu Vẽ Tổng Hợp 150 Món'],
        'Bàn Tính Gỗ Soroban 100 Hạt' => ['Bàn Tính Gỗ Soroban Học Đếm'],
        'Robot Lập Trình Cho Trẻ Em' => ['Robot Lập Trình Thẻ Lệnh Cho Bé Mẫu Giáo'],
        'Bộ 12 Mô Hình Động Vật Hoang Dã' => ['Bộ 12 Mô Hình Động Vật Hoang Dã Schleich'],
        'Bộ Nông Trại 20 Con Vật Kèm Chuồng' => ['Bộ Nông Trại Kèm 20 Con Vật Nuôi'],
        'Mũ Bảo Hiểm Trẻ Em Hình Khủng Long' => ['Mũ Bảo Hiểm Trẻ Em Họa Tiết Khủng Long'],
        'Lều Bóng Kèm 100 Quả Bóng Nhựa' => ['Lều Bóng Kèm Ống Chui Và 100 Bóng'],
        'Lều Công Chúa Hình Lâu Đài Kèm Đèn Sao' => ['Lều Công Chúa Hình Lâu Đài Có Đèn'],
        'Bộ Mô Hình Minecraft 4 Nhân Vật Kèm Phụ Kiện' => ['Bộ Mô Hình Minecraft Steve Và Alex'],
        'Bộ Bãi Đỗ Xe 4 Tầng Có Thang Máy' => ['Bộ Playset Gara Ô Tô 4 Tầng Có Thang Máy'],
        'Robot Biến Hình Optimus Prime 25cm' => ['Robot Biến Hình Ô Tô Optimus Prime'],
        'Bộ 5 Robot Biến Hình Mini Ghép Thành 1' => ['Bộ 5 Robot Cứu Hộ Biến Hình Ghép Hợp Thể'],
        'Xe Scooter 3 Bánh Phát Sáng' => ['Xe Scooter 3 Bánh Phát Sáng Gập Gọn'],
        'Bộ 6 Mô Hình Avengers Mini' => ['Bộ 6 Mô Hình Avengers Mini 10cm'],
        'Mô Hình Spider-Man Khớp Động 30cm' => ['Mô Hình Người Nhện Khớp Cử Động 30cm'],
        'Bộ Thí Nghiệm Khoa Học 60 Thí Nghiệm' => ['Bộ Thí Nghiệm Hóa Học 60 Thí Nghiệm An Toàn'],
        'Kính Hiển Vi Học Sinh Phóng Đại 1200x' => ['Kính Hiển Vi Trẻ Em Phóng Đại 1200 Lần'],
        'Bộ Trồng Cây Mini Quan Sát Hạt Nảy Mầm' => ['Bộ Trồng Cây Quan Sát Rễ Trong Suốt'],
        'Bộ 4 Thú Bông Mini Móc Khóa Ba Lô' => ['Bộ 4 Thú Bông Mini Treo Ba Lô'],
        'Thú Bông Gối Ôm Hình Mèo Dài 80cm' => ['Thú Bông Mèo Nằm Dài 80cm Ôm Ngủ'],
        'Tàu Hỏa Điện Chạy Pin Có Khói Và Còi' => ['Tàu Điện Chạy Pin Có Khói Và Đèn'],
        'Xe Chòi Chân Hình Ô Tô' => ['Xe Chòi Chân Hình Ô Tô Có Cốp'],
        'Xe Lắc Trẻ Em Có Nhạc' => ['Xe Lắc Trẻ Em Có Nhạc Và Đèn'],
        'Mô Hình Xe Lamborghini Aventador 1:24' => ['Xe Mô Hình Lamborghini Tỉ Lệ 1/24 Mở Cửa'],
        'Xe Tập Đi Đa Năng Có Mái Che' => ['Xe Tập Đi Đa Năng Có Nhạc'],
        'Xe Đẩy Tập Đi Bằng Gỗ Kèm Khối Xếp Hình' => ['Xe Đẩy Tập Đi Gỗ Có Khối Xếp Hình'],
        'Xe Ben Công Trình Điều Khiển 11 Kênh' => ['Xe Ben Điều Khiển Công Trường Có Đèn'],
        'Xe Điều Khiển Địa Hình 4WD Tỉ Lệ 1/16' => ['Xe Điều Khiển Địa Hình Leo Dốc 4WD 1:16'],
        'Xe Đua Điều Khiển Tỉ Lệ 1/24 Tốc Độ Cao' => ['Xe Đua Điều Khiển Tốc Độ Cao 1:18'],
        'Xe Đạp Trẻ Em 12 Inch Có Bánh Phụ' => ['Xe Đạp Trẻ Em 12 Inch Khung Thấp Có Bánh Phụ'],
        'Xe Đạp Trẻ Em 16 Inch Khung Thép' => ['Xe Đạp Trẻ Em 16 Inch Kèm Giỏ Trước'],
        'Sách Vải Mềm Cho Bé 6 Trang' => ['Sách Vải Mềm Cho Bé Sơ Sinh 6 Trang'],
        'Treo Nôi Nhạc Xoay Có Đèn Ngủ' => ['Treo Nôi Xoay Nhạc Hình Thú'],
        'Bộ Bếp Nấu Ăn Gỗ Có Bồn Rửa' => ['Bộ Bếp Nấu Ăn Gỗ Kèm 30 Phụ Kiện'],
        'Bộ Đồ Chơi Bác Sĩ 25 Món Trong Vali' => ['Bộ Đồ Chơi Bác Sĩ 30 Món Kèm Vali'],
        'Bộ Đồ Chơi Cát Biển 12 Món' => ['Bộ Đồ Chơi Cát Xô Xẻng 10 Món'],
        'Vòi Sen Đồ Chơi Chạy Pin Cho Bé' => ['Vòi Sen Đồ Chơi Có Bể Phun Mưa'],
        'Búp Bê Em Bé Biết Khóc Và Uống Sữa' => ['Búp Bê Sơ Sinh Biết Khóc Và Bú Bình'],
        'Bộ 16 Mô Hình Sinh Vật Biển' => ['Bộ 16 Mô Hình Sinh Vật Biển Sâu'],
        'Bộ Mô Hình Côn Trùng 12 Con Kèm Kính Lúp' => ['Bộ Quan Sát Côn Trùng Kèm Kính Lúp'],
        'Robot Chó Cảm Ứng Biết Sủa Và Đi Theo' => ['Robot Chó Điện Tử Biết Sủa Và Nhào Lộn'],
    ];

    /**
     * tên sản phẩm chính => [tên (hoặc nhãn) phân loại => tên sản phẩm cũ].
     * Phân loại đầu tiên chính là sản phẩm chính (giá thấp nhất làm giá gốc).
     */
    public const VARIANTS = [
        'Bộ 8 Mô Hình Pokémon Mini Sưu Tầm' => ['Bộ 8 mô hình' => null, 'Bộ 12 mô hình' => 'Bộ 12 Mô Hình Pokémon Mini Sưu Tập'],
        'Bộ 5 Búp Bê Công Chúa Disney Mini' => ['Bộ 5 búp bê' => null, 'Bộ 6 búp bê' => 'Bộ 6 Búp Bê Công Chúa Disney Mini'],
        'Bộ Cờ Vua Nam Châm Gấp Gọn' => ['Khổ thường' => null, 'Khổ lớn 36cm' => 'Bộ Cờ Vua Nam Châm Gấp Gọn Khổ Lớn 36cm'],
        'Bộ Thẻ Học Thông Minh 200 Thẻ Song Ngữ' => ['200 thẻ' => null, '416 thẻ' => 'Bộ Thẻ Học Thông Minh 416 Thẻ Song Ngữ'],
        'Bộ Khai Quật Hóa Thạch Khủng Long' => ['Bộ tiêu chuẩn' => null, '3 khối cỡ lớn' => 'Bộ Khai Quật Hóa Thạch Khủng Long 3 Khối Cỡ Lớn'],
        'Gấu Bông Teddy Lông Xoăn 60cm' => ['Lông xoăn' => null, 'Lông xù' => 'Gấu Bông Teddy Lông Xù 60cm'],
        'Thảm Xốp Ghép 10 Miếng Hình Chữ Số' => ['10 miếng' => null, '12 tấm' => 'Thảm Xốp Ghép 12 Tấm Có Chữ Số'],
        'Bộ Thẻ Bài Pokémon Sưu Tập 100 Lá' => ['Sưu tập 100 lá' => null, 'Khởi đầu 60 lá' => 'Thẻ Bài Pokémon Bộ Khởi Đầu 60 Lá'],
    ];

    /** Tên các sản phẩm cũ đã bị gộp - seeder bỏ qua để không tạo lại */
    public static function retiredNames(): array
    {
        $names = array_merge(...array_values(self::DUPLICATES));

        foreach (self::VARIANTS as $labels) {
            foreach ($labels as $old) {
                if ($old) {
                    $names[] = $old;
                }
            }
        }

        return $names;
    }

    /** @return array{duplicates:int, variants:int} số lần gộp thực hiện được */
    public function run(): array
    {
        $dups = 0;
        $vars = 0;

        foreach (self::DUPLICATES as $main => $copies) {
            $keep = Product::where('name', $main)->first();

            foreach ($copies as $name) {
                $copy = $keep ? Product::where('name', $name)->first() : null;
                if ($copy && $copy->id !== $keep->id) {
                    DB::transaction(fn () => $this->absorb($keep, $copy, null));
                    $dups++;
                }
            }
        }

        foreach (self::VARIANTS as $main => $labels) {
            $keep = Product::where('name', $main)->first();
            if (! $keep) {
                continue;
            }

            $pending = [];
            foreach ($labels as $label => $old) {
                $copy = $old ? Product::where('name', $old)->first() : null;
                if ($copy) {
                    $pending[$label] = $copy;
                }
            }
            if (! $pending) {
                continue;
            }

            DB::transaction(function () use ($keep, $labels, $pending, &$vars) {
                $this->makeVariantsFor($keep, array_key_first($labels), $pending);
                $vars += count($pending);
            });
        }

        return ['duplicates' => $dups, 'variants' => $vars];
    }

    /** Dồn dữ liệu của bản sao sang sản phẩm chính rồi xóa bản sao */
    private function absorb(Product $keep, Product $copy, ?ProductVariant $variant): void
    {
        OrderItem::where('product_id', $copy->id)->update([
            'product_id' => $keep->id,
            'variant_id' => $variant?->id,
            'variant_name' => $variant?->name,
        ]);

        // Mỗi khách chỉ được một dòng yêu thích / đánh giá cho một sản phẩm (ràng buộc duy nhất)
        foreach ([Wishlist::class, Review::class] as $model) {
            foreach ($model::where('product_id', $copy->id)->get() as $row) {
                $exists = $model::where('product_id', $keep->id)->where('user_id', $row->user_id)->exists();
                $exists ? $row->delete() : $row->update(['product_id' => $keep->id]);
            }
        }

        DB::table('product_views')->where('product_id', $copy->id)->get()->each(function ($v) use ($keep) {
            $dupe = DB::table('product_views')->where('product_id', $keep->id)->where('user_id', $v->user_id)->exists();
            $dupe
                ? DB::table('product_views')->where('id', $v->id)->delete()
                : DB::table('product_views')->where('id', $v->id)->update(['product_id' => $keep->id]);
        });

        $copy->images()->delete();
        $copy->delete();
    }

    /** Biến sản phẩm chính thành sản phẩm có phân loại, mỗi bản cũ là một phân loại */
    private function makeVariantsFor(Product $keep, string $mainLabel, array $pending): void
    {
        $keep->load('variants');

        // Lần đầu: sản phẩm chính tự là phân loại thứ nhất, mang theo tồn kho và đơn cũ của nó
        if ($keep->variants->isEmpty()) {
            $first = $keep->variants()->create([
                'name' => $mainLabel, 'price_adjust' => 0, 'quantity' => (int) $keep->quantity, 'sort_order' => 0,
            ]);
            OrderItem::where('product_id', $keep->id)->whereNull('variant_id')
                ->update(['variant_id' => $first->id, 'variant_name' => $first->name]);
        }

        $order = $keep->variants()->max('sort_order') + 1;
        $base = (int) $keep->price;

        foreach ($pending as $label => $copy) {
            $variant = $keep->variants()->create([
                'name' => $label,
                'price_adjust' => (int) $copy->price - $base,
                'quantity' => (int) $copy->quantity,
                'sort_order' => $order++,
            ]);
            $this->absorb($keep, $copy, $variant);
        }

        $keep->unsetRelation('variants');
        $keep->syncStockFromVariants();
    }
}
