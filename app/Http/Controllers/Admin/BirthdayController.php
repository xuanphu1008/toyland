<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChildProfile;
use Illuminate\Http\Request;

/**
 * SỔ SINH NHẬT PHÍA QUẢN TRỊ
 *
 * Đây là phần "thiên về vận hành": danh sách bé sắp sinh nhật của TOÀN BỘ
 * khách hàng, xếp theo ngày gần nhất. Nhân viên nhìn vào là biết tuần này nên
 * gọi điện hay gửi mã giảm giá cho ai - một danh sách bán hàng có sẵn, không
 * phải đoán mò.
 *
 * VÌ SAO LỌC Ở PHP CHỨ KHÔNG Ở SQL:
 * Câu hỏi "sinh nhật rơi vào N ngày tới" là so sánh ngày/tháng BẤT KỂ NĂM.
 * Mỗi hệ CSDL viết một kiểu (SQL Server dùng DATEPART, MySQL dùng DAYOFYEAR),
 * viết theo một hệ là khóa chết dự án vào hệ đó. Bảng hồ sơ bé vốn nhỏ (mỗi
 * tài khoản vài bé) nên tải lên rồi lọc bằng accessor vừa đúng vừa gọn.
 * Nếu sau này dữ liệu lớn lên, chỗ cần tối ưu là đây - đã ghi sẵn ở đây.
 */
class BirthdayController extends Controller
{
    /** Các mốc thời gian cho ô chọn nhanh */
    private const RANGES = [
        7 => 'Trong 7 ngày',
        14 => 'Trong 14 ngày',
        30 => 'Trong 30 ngày',
        90 => 'Trong 3 tháng',
    ];

    public function index(Request $request)
    {
        $days = (int) $request->input('days', 30);
        $days = array_key_exists($days, self::RANGES) ? $days : 30;

        $all = ChildProfile::with('user')->get();

        $upcoming = $all
            ->filter(fn (ChildProfile $c) => $c->days_until_birthday <= $days)
            ->sortBy('days_until_birthday')
            ->values();

        // Phân bố sinh nhật theo tháng - biết mùa nào đông để chuẩn bị hàng
        $byMonth = $all
            ->groupBy(fn (ChildProfile $c) => optional($c->birthday)->month)
            ->map->count()
            ->sortKeys();

        return view('admin.birthdays.index', [
            'upcoming' => $upcoming,
            'ranges' => self::RANGES,
            'days' => $days,
            'byMonth' => $byMonth,
            'stats' => [
                'children' => $all->count(),
                'families' => $all->pluck('user_id')->unique()->count(),
                'this_week' => $all->filter(fn ($c) => $c->days_until_birthday <= 7)->count(),
                'avg_age' => $all->isEmpty() ? 0 : round($all->avg('age_months') / 12, 1),
            ],
        ]);
    }
}
