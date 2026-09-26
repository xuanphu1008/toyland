<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

/**
 * Dùng chung cho trang Hồ sơ và trang Thanh toán để một địa chỉ
 * LUÔN được lưu kèm mã hành chính của GHN (district_id + ward_code).
 *
 * Thiếu hai mã này thì không tính được phí vận chuyển - đó chính là lý do
 * trước đây chọn địa chỉ đã lưu xong vẫn phải chọn lại Tỉnh/Quận/Phường.
 */
trait HandlesAddressForm
{
    protected function addressRules(string $prefix = ''): array
    {
        return [
            $prefix . 'name' => ['required', 'string', 'max:255'],
            $prefix . 'phone' => ['required', 'string', 'max:20', 'regex:/^0\d{8,10}$/'],
            $prefix . 'address' => ['required', 'string', 'max:500'],
            $prefix . 'province_id' => ['nullable', 'integer'],
            $prefix . 'province_name' => ['nullable', 'string', 'max:120'],
            $prefix . 'district_id' => ['nullable', 'integer'],
            $prefix . 'district_name' => ['nullable', 'string', 'max:120'],
            $prefix . 'ward_code' => ['nullable', 'string', 'max:30'],
            $prefix . 'ward_name' => ['nullable', 'string', 'max:120'],
        ];
    }

    protected function addressAttributes(string $prefix = ''): array
    {
        return [
            $prefix . 'name' => 'Tên người nhận',
            $prefix . 'phone' => 'Số điện thoại',
            $prefix . 'address' => 'Địa chỉ chi tiết',
            $prefix . 'province_id' => 'Tỉnh/Thành phố',
            $prefix . 'district_id' => 'Quận/Huyện',
            $prefix . 'ward_code' => 'Phường/Xã',
        ];
    }

    protected function addressMessages(string $prefix = ''): array
    {
        return [
            $prefix . 'phone.regex' => 'Số điện thoại phải gồm 9-11 chữ số và bắt đầu bằng 0.',
        ];
    }

    /**
     * Gom dữ liệu đã validate về đúng tên cột trong bảng user_addresses
     * (bỏ tiền tố new_ khi form checkout dùng new_name, new_phone...).
     */
    protected function addressPayload(Request $request, string $prefix = ''): array
    {
        $fields = [
            'name', 'phone', 'address',
            'province_id', 'province_name',
            'district_id', 'district_name',
            'ward_code', 'ward_name',
        ];

        $payload = [];

        foreach ($fields as $field) {
            $value = $request->input($prefix . $field);
            $payload[$field] = is_string($value) ? (trim($value) ?: null) : $value;
        }

        return $payload;
    }
}
