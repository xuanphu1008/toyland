<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CsvExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * XUẤT DỮ LIỆU RA FILE CHO EXCEL
 *
 * Bốn báo cáo, đều dùng chung CsvExportService (lớp đó lo phần BOM UTF-8,
 * dấu phân cách và chống CSV injection).
 *
 * Tất cả đều đọc theo TỪNG KHỐI 500 bản ghi rồi đẩy thẳng xuống trình duyệt,
 * không dựng cả file trong bộ nhớ - xuất mười nghìn đơn cũng không nổ RAM.
 */
class ExportController extends Controller
{
    public function __construct(private CsvExportService $csv)
    {
    }

    /** Trang chọn báo cáo cần xuất */
    public function index()
    {
        return view('admin.exports.index', [
            'statuses' => Order::STATUSES,
            'stats' => [
                'orders' => Order::count(),
                'products' => Product::count(),
                'customers' => User::where('role', '!=', 'admin')->count(),
            ],
        ]);
    }

    // ===================================================================

    /** Đơn hàng, lọc được theo trạng thái và khoảng ngày */
    public function orders(Request $request)
    {
        $filters = $request->validate([
            'status' => ['nullable', 'in:' . implode(',', array_keys(Order::STATUSES))],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $makeQuery = fn () => Order::with('user')
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d));

        $headers = [
            'Mã đơn', 'Ngày đặt', 'Khách hàng', 'Email', 'Điện thoại',
            'Trạng thái', 'Thanh toán', 'Đã trả tiền',
            'Tiền hàng', 'Giảm giá', 'Phí ship', 'Tổng tiền', 'Địa chỉ nhận',
        ];

        return $this->csv->download('don-hang', $headers, $this->stream($makeQuery, function (Order $order) {
            $subtotal = (int) $order->total_price
                - (int) ($order->ghn_total_fee ?? 0)
                + (int) ($order->discount_amount ?? 0);

            return [
                $order->id,
                optional($order->created_at)->format('d/m/Y H:i'),
                $order->user->name ?? '(đã xóa)',
                $order->user->email ?? '',
                $order->customer_phone,
                $order->status_label,
                $order->payment_method === 'cod' ? 'COD' : $order->payment_method_label,
                $order->customerHasPaid() ? 'Rồi' : 'Chưa',
                $subtotal,
                (int) ($order->discount_amount ?? 0),
                (int) ($order->ghn_total_fee ?? 0),
                (int) $order->total_price,
                $order->shipping_address,
            ];
        }));
    }

    /**
     * Doanh thu theo ngày.
     *
     * Gộp nhóm ở tầng SQL bằng CAST(... AS DATE) - viết được trên cả SQL Server
     * lẫn MySQL, khác với DATE() hay DATEPART() vốn chỉ đúng trên một hệ.
     * Đơn đã hủy bị loại vì chúng không phải doanh thu.
     */
    public function revenue(Request $request)
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $rows = DB::table('orders')
            ->selectRaw('CAST(created_at AS DATE) as ngay')
            ->selectRaw('COUNT(*) as so_don')
            ->selectRaw('SUM(total_price) as doanh_thu')
            ->selectRaw('SUM(discount_amount) as giam_gia')
            ->whereNotIn('status', \App\Models\Order::EXCLUDED_FROM_SALES)
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->groupByRaw('CAST(created_at AS DATE)')
            ->orderByRaw('CAST(created_at AS DATE)')
            ->get();

        $headers = ['Ngày', 'Số đơn', 'Doanh thu', 'Tổng giảm giá', 'Trung bình mỗi đơn'];

        $data = $rows->map(fn ($r) => [
            \Carbon\Carbon::parse($r->ngay)->format('d/m/Y'),
            (int) $r->so_don,
            (int) $r->doanh_thu,
            (int) $r->giam_gia,
            $r->so_don > 0 ? (int) round($r->doanh_thu / $r->so_don) : 0,
        ]);

        return $this->csv->download('doanh-thu-theo-ngay', $headers, $data);
    }

    /** Tồn kho - kèm số đã bán để biết món nào cần nhập thêm */
    public function inventory()
    {
        $makeQuery = fn () => Product::with('category')->withSoldQuantity();

        $headers = [
            'ID', 'Mã SKU', 'Tên sản phẩm', 'Danh mục', 'Thương hiệu', 'Nhân vật',
            'Độ tuổi', 'Giá bán', 'Tồn kho', 'Đã bán', 'Tình trạng',
        ];

        return $this->csv->download('ton-kho', $headers, $this->stream($makeQuery, function (Product $p) {
            return [
                $p->id,
                $p->sku,
                $p->name,
                $p->category->name ?? '',
                $p->brand,
                $p->character_name,
                $p->age_label,
                (int) $p->price,
                (int) $p->quantity,
                (int) $p->sold_quantity,
                match (true) {
                    (int) $p->quantity === 0 => 'HẾT HÀNG',
                    (int) $p->quantity <= 5 => 'Sắp hết',
                    default => 'Còn hàng',
                },
            ];
        }));
    }

    /** Khách hàng - kèm tổng chi tiêu và hạng thành viên */
    public function customers()
    {
        $makeQuery = fn () => User::where('role', '!=', 'admin')
            ->withCount(['orders as so_don' => fn ($q) => $q->whereNotIn('status', \App\Models\Order::EXCLUDED_FROM_SALES)]);

        $headers = [
            'ID', 'Họ tên', 'Email', 'Ngày đăng ký', 'Số đơn',
            'Tổng chi tiêu', 'Điểm thưởng', 'Hạng thành viên', 'Đã xác thực email',
        ];

        return $this->csv->download('khach-hang', $headers, $this->stream($makeQuery, function (User $u) {
            return [
                $u->id,
                $u->name,
                $u->email,
                optional($u->created_at)->format('d/m/Y'),
                (int) $u->so_don,
                (int) $u->lifetime_spent,
                (int) $u->points,
                $u->tier_label,
                $u->email_verified_at ? 'Rồi' : 'Chưa',
            ];
        }));
    }

    // ===================================================================

    /**
     * Đọc dữ liệu theo TỪNG KHỐI và trả ra từng dòng cho file CSV.
     *
     * VÌ SAO KHÔNG DÙNG cursor():
     * `cursor()` chỉ giữ một bản ghi trong bộ nhớ - nghe thì lý tưởng - nhưng
     * nó KHÔNG nạp sẵn quan hệ. Mỗi lần đọc $order->user là thêm một truy vấn,
     * xuất 5.000 đơn thành 5.001 câu lệnh (lỗi N+1).
     *
     * Cách ở đây lấy mỗi lần 500 bản ghi kèm quan hệ nạp sẵn: bộ nhớ vẫn nhỏ
     * mà số truy vấn giảm hàng nghìn lần.
     *
     * Phân trang theo `id > lần cuối` chứ không dùng OFFSET, vì OFFSET phải
     * đếm lại từ đầu ở mỗi khối nên càng về sau càng chậm.
     *
     * @param  callable  $queryFactory  hàm trả về builder MỚI mỗi lần gọi -
     *                                  tránh hẳn chuyện dùng lại builder cũ
     *                                  đã dính điều kiện của khối trước
     */
    private function stream(callable $queryFactory, callable $mapper, int $size = 500): iterable
    {
        $lastId = 0;

        while (true) {
            $chunk = $queryFactory()
                ->where('id', '>', $lastId)
                ->orderBy('id')
                ->limit($size)
                ->get();

            if ($chunk->isEmpty()) {
                break;
            }

            foreach ($chunk as $model) {
                $lastId = $model->getKey();

                yield $mapper($model);
            }

            // Khối cuối cùng luôn ít hơn $size - hết dữ liệu thì dừng
            if ($chunk->count() < $size) {
                break;
            }
        }
    }
}
