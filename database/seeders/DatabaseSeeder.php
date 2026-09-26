<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // 1. Tài khoản Admin & Khách hàng mặc định
        //    (updateOrCreate nên chạy lại seeder không bị trùng tài khoản)
        $this->call(AdminUserSeeder::class);
        $this->call(CustomerUserSeeder::class);

        // 2. Cây danh mục 2 cấp + khoảng 100 sản phẩm đồ chơi mẫu.
        //
        //    LƯU Ý: bản cũ xóa sạch products & categories trước khi seed lại.
        //    Cách đó làm hỏng các đơn hàng đang trỏ tới sản phẩm (khóa ngoại)
        //    và mất luôn dữ liệu test. ToyCatalogSeeder dùng firstOrCreate /
        //    updateOrCreate theo TÊN nên chạy bao nhiêu lần cũng an toàn.
        $this->call(ToyCatalogSeeder::class);

        // 3. Nội dung phụ trợ: FAQ, trang tĩnh, bài viết blog, mã giảm giá
        $this->call(ContentSeeder::class);
    }
}
