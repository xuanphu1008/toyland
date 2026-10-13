<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderStatusLog;
use App\Models\Order;
use App\Services\CsvExportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * TÀI CHÍNH — thống kê và tra cứu giao dịch thanh toán.
 *
 * Trang này trả lời câu hỏi "tiền đang nằm ở đâu": mỗi đơn hàng đang ở trạng
 * thái thanh toán nào, thu được bao nhiêu qua COD, bao nhiêu qua MoMo, và đơn
 * nào đang chờ hoàn tiền.
 *
 * Điểm khó nhất nằm ở chỗ MỘT ĐƠN CÓ THỂ CÓ NHIỀU GIAO DỊCH: khách bấm thanh
 * toán MoMo thất bại rồi bấm lại sẽ sinh thêm bản ghi mới. Nếu cứ nối thẳng
 * orders với payment_transactions thì một đơn sẽ bị đếm nhiều lần và tổng tiền
 * phình lên sai. Vì vậy ordersQuery() chỉ chọn ĐÚNG MỘT giao dịch đại diện cho
 * mỗi đơn, ưu tiên giao dịch đã thu được tiền (xem PAYMENT_PRIORITY).
 */
class FinanceController extends Controller
{
    /** Nhãn tiếng Việt của các trạng thái thanh toán - trùng bộ dùng ở trang đơn hàng */
    private const STATUSES = [
        'pending' => 'Chờ thanh toán',
        'initiated' => 'Đang chờ MoMo',
        'paid' => 'Đã thanh toán',
        'failed' => 'Thanh toán thất bại',
        'cancelled' => 'Đã hủy',
        'cod_collected' => 'Đã thu hộ - chờ đối soát',
        'refund_pending' => 'Chờ hoàn tiền',
        'refunded' => 'Đã hoàn tiền',
    ];

    private const METHODS = ['cod' => 'COD', 'momo' => 'MoMo', 'wallet' => 'Ví ToyLand', 'unknown' => 'Chưa xác định'];

    /**
     * Bảng chuyển trạng thái do ADMIN làm tay cho đơn COD: CHỈ có việc ĐỐI SOÁT.
     *
     * Vòng đời tiền COD:
     *   pending --(shipper giao xong, hệ thống tự làm)--> cod_collected
     *   cod_collected --(admin đối soát khớp với bên vận chuyển)--> paid
     *   cod_collected --(thiếu/lệch tiền)--> failed --(sau khi xử lý xong)--> paid
     *
     * KHÔNG có bước nào dẫn tới refund_pending / refunded ở đây. Hoàn tiền chỉ sinh
     * ra từ hủy đơn / hoàn hàng / yêu cầu của khách đã được duyệt và được xử lý ở
     * màn "Hoàn tiền" - trạng thái thanh toán là HỆ QUẢ của sự kiện thật, không
     * phải một ô để admin chọn tùy ý.
     *
     * Mỗi trạng thái tự trỏ về chính nó để thao tác lặp lại không báo lỗi.
     */
    private const COD_TRANSITIONS = [
        'pending' => ['pending'],
        'cod_collected' => ['cod_collected', 'paid', 'failed'],
        'failed' => ['failed', 'paid'],
        'paid' => ['paid'],
        'refund_pending' => ['refund_pending'],
        'refunded' => ['refunded'],
        'cancelled' => ['cancelled'],
    ];

    /**
     * Ưu tiên giao dịch đã thu/đang hoàn tiền; lần thử thanh toán mới không che
     * mất tiền đã thu. Ví dụ: đơn đã trả tiền rồi khách vô tình bấm thanh toán
     * lại (sinh bản ghi 'pending' mới) - nếu lấy bản ghi mới nhất thì đơn sẽ bị
     * báo là chưa thanh toán, tiền thật biến mất khỏi báo cáo.
     */
    private const PAYMENT_PRIORITY = "CASE WHEN status IN ('paid', 'cod_collected', 'refund_pending', 'refunded') THEN 0 ELSE 1 END";

    // =================================================================

    /**
     * Mỗi đơn hàng kèm ĐÚNG MỘT giao dịch đại diện.
     *
     * Ghi chú cho SQL Server: bảng orders đã có sẵn cột payment_status thật, nên
     * không được viết orders.* rồi đặt thêm alias payment_status - hai cột trùng
     * tên trong cùng một subquery sẽ bị từ chối (MySQL thì cho qua). Vì vậy phải
     * liệt kê tường minh từng cột cần dùng.
     */
    private function ordersQuery()
    {
        $orders = DB::table('orders')
            ->leftJoin('users', 'users.id', '=', 'orders.user_id')
            ->leftJoin('payment_transactions as payment', function ($join) {
                $join->on('payment.order_id', '=', 'orders.id')->where('payment.id', '=', function ($sub) {
                    $sub->select('id')->from('payment_transactions')
                        ->whereColumn('order_id', 'orders.id')
                        ->orderByRaw(self::PAYMENT_PRIORITY)
                        ->orderByDesc('id')->limit(1);
                });
            })
            ->select([
                'orders.id', 'orders.user_id', 'orders.total_price', 'orders.status',
                'orders.payment_method', 'orders.shipping_status', 'orders.customer_phone',
                'orders.ghn_total_fee', 'orders.discount_amount', 'orders.created_at',
                'users.name as name',
                'payment.id as payment_id',
                'payment.paid_at as paid_at',
            ])
            // Đơn cũ (trước khi tách bảng giao dịch) không có bản ghi payment nào,
            // nên phải suy ngược phương thức từ trạng thái đơn kiểu cũ.
            ->selectRaw("COALESCE(payment.gateway, CASE WHEN orders.status IN ('cod_ordered', 'cod_paid') THEN 'cod' WHEN orders.status IN ('paid', 'paid_momo') THEN 'momo' ELSE 'unknown' END) as gateway")
            ->selectRaw("COALESCE(payment.status, CASE WHEN orders.status = 'cod_ordered' THEN 'pending' WHEN orders.status IN ('cod_paid', 'paid_momo') THEN 'paid' ELSE orders.payment_status END) as payment_status");

        return DB::query()->fromSub($orders, 'finance_orders');
    }

    /**
     * Kiểm tra dữ liệu lọc rồi dựng truy vấn đã lọc.
     * Trả về [truy vấn, bộ lọc đã làm sạch] để cả hai trang dùng chung.
     */
    private function filteredOrders(Request $request): array
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            // after_or_equal chỉ áp dụng khi ô ngày bắt đầu có giá trị - nếu để
            // trống, strtotime('') trả về false và luật này luôn báo sai.
            'date_to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('date_from') ? ['after_or_equal:date_from'] : [])],
            'min_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'max_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99', ...($request->filled('min_amount') ? ['gte:min_amount'] : [])],
            'gateway' => ['nullable', Rule::in(array_keys(self::METHODS))],
            'payment_status' => ['nullable', Rule::in(array_keys(self::STATUSES))],
            'sort' => ['nullable', Rule::in(['newest', 'oldest', 'amount_asc', 'amount_desc'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ], [
            'date_to.after_or_equal' => 'Ngày kết thúc phải từ ngày bắt đầu trở đi.',
            'max_amount.gte' => 'Số tiền tối đa phải lớn hơn hoặc bằng số tiền tối thiểu.',
            '*.date_format' => 'Ngày lọc không hợp lệ (định dạng năm-tháng-ngày).',
            '*.numeric' => 'Số tiền phải là một giá trị số.',
            '*.min' => 'Giá trị bộ lọc nhỏ hơn mức cho phép.',
            '*.max' => 'Giá trị bộ lọc lớn hơn mức cho phép.',
            '*.in' => 'Giá trị bộ lọc không hợp lệ.',
        ]);

        // Dữ liệu mẫu có thể chứa đơn ghi ngày tương lai; loại ra để tổng tiền
        // hôm nay không tự nhiên vọt lên.
        $query = $this->ordersQuery()->where('created_at', '<=', now());

        if ($request->filled('search')) {
            $search = trim($filters['search']);
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%')
                      ->orWhere('customer_phone', 'like', '%'.$search.'%');

                // Gõ "12" hay "#12" đều hiểu là tìm đơn số 12
                if (ctype_digit(ltrim($search, '#'))) {
                    $query->orWhere('id', ltrim($search, '#'));
                }
            });
        }

        foreach (['gateway', 'payment_status'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $filters[$field]);
            }
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', Carbon::parse($filters['date_from'])->startOfDay());
        }
        if ($request->filled('date_to')) {
            // Lấy mốc 0h ngày hôm sau để đơn đặt lúc 23h59 ngày cuối vẫn được tính.
            $query->where('created_at', '<', Carbon::parse($filters['date_to'])->addDay()->startOfDay());
        }

        foreach (['min_amount' => '>=', 'max_amount' => '<='] as $field => $operator) {
            if ($request->filled($field)) {
                $query->where('total_price', $operator, $filters[$field]);
            }
        }

        return [$query, $filters];
    }

    // =================================================================

    /**
     * Áp thứ tự sắp xếp người dùng chọn. Dùng chung cho trang danh sách và file
     * xuất, để file tải về có đúng thứ tự như đang nhìn trên màn hình.
     *
     * Luôn kèm sắp xếp phụ theo id: khi hai đơn trùng ngày hoặc trùng tiền, thứ
     * tự vẫn cố định - không có đơn nào hiện ở cả trang 1 lẫn trang 2.
     */
    private function sorted($query, array $filters)
    {
        [$column, $direction] = match ($filters['sort'] ?? 'newest') {
            'oldest' => ['created_at', 'asc'],
            'amount_asc' => ['total_price', 'asc'],
            'amount_desc' => ['total_price', 'desc'],
            default => ['created_at', 'desc'],
        };

        return $query->orderBy($column, $direction)->orderBy('id', $direction);
    }

    /** Trang 1: các chỉ số tài chính trên đúng tập đơn đang lọc. */
    public function index(Request $request)
    {
        [$query, $filters] = $this->filteredOrders($request);

        // Thống kê toàn bộ kết quả lọc; mỗi đơn chỉ tính một lần.
        $summary = (clone $query)
            ->selectRaw('COUNT(*) as order_count, COALESCE(SUM(total_price), 0) as total_amount')
            ->first();

        $statusTotals = (clone $query)->select('payment_status')
            ->selectRaw('COUNT(*) as order_count, COALESCE(SUM(total_price), 0) as total_amount')
            ->groupBy('payment_status')->get()->keyBy('payment_status');

        $methodTotals = (clone $query)->select('gateway')
            ->selectRaw('COUNT(*) as order_count, COALESCE(SUM(total_price), 0) as total_amount')
            ->selectRaw("COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN total_price ELSE 0 END), 0) as paid_amount")
            ->groupBy('gateway')->get()->keyBy('gateway');

        return view('admin.finance.index', [
            'filters' => $filters,
            'summary' => $summary,
            'statusTotals' => $statusTotals,
            'methodTotals' => $methodTotals,
            'statuses' => self::STATUSES,
            'methods' => self::METHODS,
        ]);
    }

    /** Trang 2: danh sách giao dịch, kèm ô cập nhật trạng thái thu tiền COD. */
    public function transactions(Request $request)
    {
        [$query, $filters] = $this->filteredOrders($request);

        $orders = $this->sorted($query, $filters)->paginate(15)->withQueryString();

        return view('admin.finance.transactions', [
            'orders' => $orders,
            'filters' => $filters,
            'statuses' => self::STATUSES,
            'methods' => self::METHODS,
            'codTransitions' => self::COD_TRANSITIONS,
        ]);
    }

    /**
     * Xuất ĐÚNG tập đơn đang lọc ra file CSV mở bằng Excel.
     *
     * Nhận nguyên bộ lọc của hai trang tài chính (từ khóa, khoảng ngày, khoảng
     * tiền, phương thức, trạng thái, thứ tự) - bấm xuất ở đâu thì file khớp với
     * đúng những gì đang thấy ở đó.
     *
     * Cách đọc dữ liệu có chủ đích: lấy DANH SÁCH MÃ ĐƠN theo đúng thứ tự trước
     * (chỉ là các số nguyên, mười nghìn đơn cũng chỉ vài chục KB), rồi mới nạp
     * chi tiết theo từng lô 500 đơn. Được ba điều cùng lúc:
     *   - bộ nhớ không phình theo số đơn;
     *   - giữ được mọi kiểu sắp xếp, kể cả theo số tiền;
     *   - file là một "ảnh chụp" cố định: đơn mới phát sinh trong lúc đang xuất
     *     không chen vào làm lệch hay lặp dòng như khi phân trang bằng OFFSET.
     * Lô 500 cũng giữ câu IN (...) dưới giới hạn 2100 tham số của SQL Server.
     */
    public function export(Request $request, CsvExportService $csv)
    {
        [$query, $filters] = $this->filteredOrders($request);

        $ids = $this->sorted($query, $filters)->pluck('id');

        $headers = [
            'Mã đơn', 'Ngày tạo đơn', 'Khách hàng', 'Điện thoại', 'Phương thức',
            'Trạng thái thanh toán', 'Trạng thái đơn', 'Số tiền (đ)', 'Thời điểm thu tiền',
        ];

        $rows = function () use ($ids) {
            foreach ($ids->chunk(500) as $chunk) {
                $details = $this->ordersQuery()->whereIn('id', $chunk->values()->all())->get()->keyBy('id');

                foreach ($chunk as $id) {
                    $order = $details->get($id);
                    if (! $order) {
                        continue;
                    }

                    yield [
                        $order->id,
                        Carbon::parse($order->created_at)->format('d/m/Y H:i'),
                        $order->name ?? '(tài khoản đã xóa)',
                        $order->customer_phone,
                        self::METHODS[$order->gateway] ?? $order->gateway,
                        self::STATUSES[$order->payment_status] ?? $order->payment_status,
                        Order::STATUSES[$order->status] ?? $order->status,
                        // Số nguyên thuần, không định dạng dấu chấm - để Excel cộng được.
                        (int) $order->total_price,
                        $order->paid_at ? Carbon::parse($order->paid_at)->format('d/m/Y H:i') : '',
                    ];
                }
            }
        };

        return $csv->download('giao-dich-thanh-toan', $headers, $rows());
    }

    // =================================================================

    /**
     * Cập nhật THỦ CÔNG trạng thái thu tiền của một đơn COD.
     *
     * Chỉ đơn COD mới được sửa tay: tiền COD do nhân viên giao hàng thu nên
     * không có hệ thống nào tự báo về, phải có người xác nhận. Đơn MoMo thì
     * ngược lại - trạng thái do cổng thanh toán quyết định, sửa tay sẽ làm
     * lệch với đối soát của MoMo.
     */
    public function updateStatus(Request $request, Order $order)
    {
        $data = $request->validate([
            'payment_status' => ['required', Rule::in(array_keys(self::COD_TRANSITIONS))],
            // Ba trường dưới là ảnh chụp trạng thái lúc trang được tải. Nếu trong
            // lúc admin A đang mở trang mà admin B đã đổi đơn, ba giá trị này sẽ
            // không khớp nữa và thao tác bị chặn - tránh ghi đè lên việc của nhau.
            'current_payment_status' => ['required', 'string'],
            'current_order_status' => ['required', 'string'],
            'current_payment_id' => ['required', 'integer', 'min:0'],
        ], [
            'payment_status.in' => 'Trạng thái COD không hợp lệ.',
            '*.required' => 'Thiếu thông tin trạng thái. Vui lòng tải lại trang.',
        ]);

        $change = DB::transaction(function () use ($order, $data, $request) {
            // Khóa đơn và giao dịch trong suốt giao dịch CSDL để hai người bấm
            // cùng lúc thì người sau phải đợi, đọc được trạng thái mới nhất.
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            $payment = $order->paymentTransactions()
                ->orderByRaw(self::PAYMENT_PRIORITY)->orderByDesc('id')
                ->lockForUpdate()->first();

            $isCod = $payment
                ? $payment->gateway === 'cod'
                : ($order->payment_method === 'cod' || in_array($order->status, ['cod_ordered', 'cod_paid'], true));

            if (! $isCod) {
                throw ValidationException::withMessages([
                    'payment_status' => 'Chỉ được cập nhật thủ công cho đơn COD.',
                ]);
            }

            $currentStatus = $payment?->status
                ?? ($order->payment_status ?: ($order->status === 'cod_paid' ? 'paid' : 'pending'));

            if ($currentStatus !== $data['current_payment_status']
                || $order->status !== $data['current_order_status']
                || (int) ($payment?->id ?? 0) !== (int) $data['current_payment_id']) {
                throw ValidationException::withMessages([
                    'payment_status' => 'Đơn hàng vừa thay đổi. Vui lòng tải lại trang trước khi cập nhật.',
                ]);
            }

            $newStatus = $data['payment_status'];

            if (! in_array($newStatus, self::COD_TRANSITIONS[$currentStatus] ?? [], true)) {
                throw ValidationException::withMessages([
                    'payment_status' => 'Không thể chuyển sang trạng thái thanh toán này.',
                ]);
            }

            // Chọn lại đúng trạng thái đang có thì coi như không làm gì,
            // và cũng không làm phiền khách bằng một thông báo vô nghĩa.
            if ($newStatus === $currentStatus) {
                return null;
            }

            // Đối soát chỉ có nghĩa khi hàng đã giao: đơn chưa giao/đã hủy/đã hoàn thì không có tiền để đối soát.
            if ($order->status !== 'completed') {
                throw ValidationException::withMessages([
                    'payment_status' => 'Chỉ đối soát COD cho đơn đã giao thành công. Đơn này đang "'.(Order::STATUSES[$order->status] ?? $order->status).'".',
                ]);
            }

            $attributes = [
                'status' => $newStatus,
                'message' => 'Quản trị viên #'.$request->user()->id.' đối soát COD: '.self::STATUSES[$newStatus],
                // Giữ nguyên mốc đã thu nếu trước đó từng thu, để lịch sử không bị dời.
                'paid_at' => $newStatus === 'paid' ? ($payment?->paid_at ?? now()) : $payment?->paid_at,
            ];

            if ($payment) {
                $payment->update($attributes);
            } else {
                $order->paymentTransactions()->create(array_merge($attributes, [
                    'gateway' => 'cod',
                    'amount' => $order->total_price,
                ]));
            }

            // Đồng bộ lên cột payment_status của đơn hàng để hóa đơn, báo cáo và
            // trang lịch sử đơn của khách đều thấy cùng một con số.
            //
            // KHÔNG đụng vào orders.status: ở hệ thống này cột đó là trạng thái
            // VÒNG ĐỜI ĐƠN (chờ xác nhận -> đã giao), tách hẳn khỏi trạng thái
            // thanh toán. Ghi 'cod_paid' vào đó sẽ làm đơn biến mất khỏi các tab
            // quản lý đơn và vỡ dải tiến trình bên phía khách hàng.
            $order->update(['payment_status' => $newStatus]);

            // Ghi nhật ký: ai đối soát, từ trạng thái nào sang trạng thái nào.
            OrderStatusLog::create([
                'order_id' => $order->id,
                'changed_by' => $request->user()->id,
                'from_status' => $order->status,
                'to_status' => $order->status,
                'note' => mb_substr('[Shop] Đối soát COD: '.(self::STATUSES[$currentStatus] ?? $currentStatus).' -> '.(self::STATUSES[$newStatus] ?? $newStatus), 0, 255),
            ]);

            return ['from' => $currentStatus, 'to' => $newStatus];
        });

        // Đối soát COD là việc nội bộ giữa shop và đơn vị vận chuyển: khách đã trả tiền mặt
        // cho shipper từ lúc nhận hàng nên KHÔNG gửi thông báo cho khách ở bước này.

        return back()->with('success', $change
            ? 'Đã lưu đối soát COD đơn #'.$order->id.'.'
            : 'Trạng thái thanh toán đơn COD #'.$order->id.' không thay đổi.');
    }
}
