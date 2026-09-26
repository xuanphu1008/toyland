<?php

/*
|--------------------------------------------------------------------------
| Cấu hình riêng của ToyLand khi triển khai
|--------------------------------------------------------------------------
| Mọi giá trị đọc từ .env / biến môi trường của Render. Không đọc env() ở
| nơi khác ngoài thư mục config: sau `php artisan config:cache`, env() gọi
| ngoài config sẽ trả null.
*/

return [

    // Tài khoản quản trị do seeder tạo ở lần triển khai đầu tiên.
    // Máy nhà (APP_ENV=local) để trống mật khẩu thì dùng mật khẩu mẫu Admin@123.
    // Production BẮT BUỘC khai ADMIN_PASSWORD, không thì seeder bỏ qua tài khoản admin.
    'admin' => [
        'email' => env('ADMIN_EMAIL', 'admin@tpcn.com'),
        'name' => env('ADMIN_NAME', 'Quản Trị Viên'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    // Hai tài khoản khách mẫu để thầy cô / nhóm vào thử. Tắt bằng SEED_DEMO_CUSTOMERS=false.
    'demo_customers' => [
        'enabled' => env('SEED_DEMO_CUSTOMERS', true),
        'password' => env('DEMO_CUSTOMER_PASSWORD', 'Khach@123'),
    ],

    // Mã bí mật cho đường dẫn /cron/daily (dịch vụ hẹn giờ bên ngoài gọi vào).
    // Để trống = tắt hẳn đường dẫn đó (trả 404).
    'cron_token' => env('CRON_TOKEN'),

    // Ép mọi URL sinh ra dùng https (Render đứng sau proxy https)
    'force_https' => env('FORCE_HTTPS', false),
];
