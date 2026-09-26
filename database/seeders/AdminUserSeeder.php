<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /** Mật khẩu mẫu chỉ dùng khi chạy trên máy nhà */
    private const LOCAL_DEFAULT_PASSWORD = 'Admin@123';

    public function run()
    {
        $email = (string) config('toyland.admin.email', 'admin@tpcn.com');
        $password = (string) config('toyland.admin.password');
        $isProduction = app()->environment('production');

        if ($password === '') {
            if ($isProduction) {
                // Không bao giờ tạo admin với mật khẩu ai cũng biết trên web thật
                if ($this->command) {
                    $this->command->warn('Bỏ qua tạo admin: chưa khai ADMIN_PASSWORD.');
                }

                return;
            }

            $password = self::LOCAL_DEFAULT_PASSWORD;
        }

        $user = User::firstOrNew(['email' => $email]);

        // Trên web thật: admin đã tồn tại thì KHÔNG ghi đè mật khẩu - có thể
        // admin đã đổi mật khẩu trong trang hồ sơ. Máy nhà giữ cách cũ (chạy
        // seeder là đặt lại mật khẩu mẫu) cho tiện thử nghiệm.
        if (! $user->exists || ! $isProduction) {
            $user->password = Hash::make($password);
        }

        $user->name = $user->name ?: (string) config('toyland.admin.name', 'Quản Trị Viên');
        $user->role = 'admin';
        $user->email_verified_at = $user->email_verified_at ?: now();
        $user->save();
    }
}
