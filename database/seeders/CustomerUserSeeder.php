<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CustomerUserSeeder extends Seeder
{
    public function run()
    {
        // Tắt bằng SEED_DEMO_CUSTOMERS=false khi không muốn có tài khoản mẫu trên web thật
        if (! filter_var(config('toyland.demo_customers.enabled', true), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        $password = (string) config('toyland.demo_customers.password') ?: 'Khach@123';

        $customers = [
            'khachhang1@tpcn.com' => 'Nguyễn Văn A',
            'khachhang2@tpcn.com' => 'Trần Thị B',
        ];

        foreach ($customers as $email => $name) {
            User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make($password),
                    'role' => 'user',
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
