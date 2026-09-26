<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\OrderStatusChangedMail;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Models\PaymentTransaction;
use App\Models\PointTransaction;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Throwable;

class OrderController extends Controller
{
    private const TABS = [
        'all' => ['label' => 'Tất cả', 'color' => 'blue', 'statuses' => []],
        'pending' => ['label' => 'Chờ xử lý', 'color' => 'slate', 'statuses' => ['pending', 'not_shipped', 'processing']],
        'ready' => ['label' => 'Chờ lấy hàng', 'color' => 'cyan', 'statuses' => ['ready_to_pick']],
        'picking' => ['label' => 'Đang lấy hàng', 'color' => 'cyan', 'statuses' => ['picking']],
        'delivering' => ['label' => 'Đang giao', 'color' => 'amber', 'statuses' => ['delivering', 'picked', 'storing', 'transporting', 'sorting']],
        'delivered' => ['label' => 'Thành công', 'color' => 'green', 'statuses' => ['delivered']],
        'return' => ['label' => 'Hoàn hàng', 'color' => 'orange', 'statuses' => ['return', 'returning', 'returned', 'return_transporting', 'return_sorting']],
        'cancelled' => ['label' => 'Đã hủy', 'color' => 'red', 'statuses' => ['cancelled']],
    ];

    public function index(Request $request)
    {
        $paymentLabels = [
            'pending' => 'Chờ thanh toán', 'initiated' => 'Đang chờ MoMo', 'paid' => 'Đã thanh toán',
            'failed' => 'Thanh toán thất bại', 'cancelled' => 'Đã hủy',
            'refund_pending' => 'Chờ hoàn tiền', 'refunded' => 'Đã hoàn tiền',
        ];
        $shippingLabels = [
            'pending' => 'Chờ tạo vận đơn', 'not_shipped' => 'Chưa giao hàng', 'processing' => 'Đang tạo vận đơn',
            'ready_to_pick' => 'Chờ lấy hàng', 'picking' => 'Đang lấy hàng', 'picked' => 'Đã lấy hàng',
            'storing' => 'Đang lưu kho', 'transporting' => 'Đang trung chuyển', 'sorting' => 'Đang phân loại',
            'delivering' => 'Đang giao hàng', 'delivered' => 'Giao hàng thành công',
            'return' => 'Chờ hoàn hàng', 'returning' => 'Đang hoàn hàng', 'returned' => 'Đã hoàn hàng',
            'return_transporting' => 'Đang chuyển hoàn', 'return_sorting' => 'Đang phân loại hoàn', 'cancelled' => 'Đã hủy',
        ];

        // Trạng thái đơn hợp lệ = bộ chuẩn mới + các giá trị cũ còn sót trong DB
        $orderStatuses = array_merge(array_keys(Order::STATUSES), Order::LEGACY_STATUSES);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in($orderStatuses)],
            'payment_status' => ['nullable', Rule::in(array_keys($paymentLabels))],
            'shipping_status' => ['nullable', Rule::in(array_keys($shippingLabels))],
            'gateway' => ['nullable', Rule::in(['cod', 'momo', 'unknown'])],
            'tab' => ['nullable', Rule::in(array_keys(self::TABS))],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('date_from') ? ['after_or_equal:date_from'] : [])],
            'per_page' => ['nullable', 'integer', Rule::in([25, 50, 100])],
            'sort' => ['nullable', Rule::in(['newest', 'oldest', 'amount_desc', 'amount_asc'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $source = DB::table('orders')->leftJoin('payment_transactions as payment', function ($join) {
            $join->on('payment.order_id', '=', 'orders.id')->where('payment.id', '=', function ($sub) {
                $sub->select('id')->from('payment_transactions')
                    ->whereColumn('order_id', 'orders.id')
                    ->orderByRaw("CASE WHEN status IN ('paid', 'refund_pending', 'refunded') THEN 0 ELSE 1 END")
                    ->orderByDesc('id')->limit(1);
            });
        })->select([
                // Không dùng orders.* vì orders đã có sẵn cột payment_status thật -
                // sẽ đụng tên với cột payment_status suy ra bên dưới (SQL Server không cho phép
                // 2 cột trùng tên trong 1 subquery, khác với MySQL).
                'orders.id', 'orders.user_id', 'orders.total_price', 'orders.status',
                'orders.payment_method', 'orders.shipping_address', 'orders.customer_phone',
                'orders.shipping_status', 'orders.ghn_order_code', 'orders.ghn_total_fee',
                'orders.to_district_id', 'orders.to_ward_code', 'orders.created_at', 'orders.updated_at',
            ])
            ->selectRaw("COALESCE(payment.gateway, CASE WHEN orders.status IN ('cod_ordered', 'cod_paid') THEN 'cod' WHEN orders.status IN ('paid', 'paid_momo') THEN 'momo' ELSE 'unknown' END) as gateway")
            ->selectRaw("COALESCE(payment.status, CASE WHEN orders.status = 'cod_ordered' THEN 'pending' WHEN orders.status IN ('cod_paid', 'paid_momo') THEN 'paid' ELSE orders.status END) as payment_status");

        $query = Order::query()->fromSub($source, 'orders');

        foreach (['status', 'payment_status', 'gateway'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $filters[$field]);
            }
        }

        if ($request->filled('search')) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', fn ($u) => $u->where('name', 'like', '%'.$search.'%'))
                  ->orWhere('customer_phone', 'like', '%'.$search.'%')
                  ->orWhere('ghn_order_code', 'like', '%'.$search.'%')
                  ->orWhereHas('items.product', fn ($products) => $products->where('name', 'like', '%'.$search.'%'));

                if (preg_match('/^(?:#|DH)?0*(\d+)$/i', $search, $matches)) {
                    $q->orWhere('orders.id', $matches[1]);
                }
            });
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', Carbon::parse($filters['date_from'])->startOfDay());
        }
        if ($request->filled('date_to')) {
            $query->where('created_at', '<', Carbon::parse($filters['date_to'])->addDay()->startOfDay());
        }

        $shippingCounts = (clone $query)->select('shipping_status')->selectRaw('COUNT(*) as total')
            ->groupBy('shipping_status')->pluck('total', 'shipping_status');

        $tabs = collect(self::TABS)->map(function ($tab, $key) use ($shippingCounts) {
            $tab['count'] = $key === 'all' ? $shippingCounts->sum() : collect($tab['statuses'])->sum(fn ($status) => $shippingCounts->get($status, 0));
            return $tab;
        });

        $activeTab = $filters['tab'] ?? 'all';
        if ($activeTab !== 'all') {
            $query->whereIn('shipping_status', self::TABS[$activeTab]['statuses']);
        }
        if ($request->filled('shipping_status')) {
            $query->where('shipping_status', $filters['shipping_status']);
        }

        [$column, $direction] = match ($filters['sort'] ?? 'newest') {
            'oldest' => ['created_at', 'asc'],
            'amount_desc' => ['total_price', 'desc'],
            'amount_asc' => ['total_price', 'asc'],
            default => ['created_at', 'desc'],
        };

        $orders = $query->with(['user', 'items.product'])->orderBy($column, $direction)->orderBy('id', $direction)
            ->paginate((int) ($filters['per_page'] ?? 25))->withQueryString();

        $orderStatusLabels = Order::STATUSES;

        return view('admin.order.index', compact(
            'orders', 'filters', 'tabs', 'activeTab', 'paymentLabels', 'shippingLabels', 'orderStatusLabels'
        ));
    }

    public function show($id)
    {
        $order = Order::with([
            'user',
            'items.product',
            'paymentTransactions' => fn ($query) => $query->latest(),
        ])->findOrFail($id);

        // Nhật ký chuyển trạng thái, cũ -> mới, để admin theo dõi vòng đời đơn
        $statusLogs = OrderStatusLog::with('changedBy')
            ->where('order_id', $order->id)
            ->orderBy('id')
            ->get();

        return view('admin.order.show', compact('order', 'statusLogs'));
    }

    /**
     * Cập nhật trạng thái đơn hàng (xác nhận / đóng gói / vận chuyển / đã giao).
     * Việc hủy đơn đi theo route riêng vì có thêm bước hoàn kho.
     */
    /**
     * Hóa đơn phía quản trị. Dùng CHUNG bản hóa đơn với phía khách
     * (OrderController::invoiceData) nên hai bên không bao giờ lệch nhau.
     */
    public function invoice($id)
    {
        $order = Order::findOrFail($id);

        return view('orders.invoice', \App\Http\Controllers\OrderController::invoiceData(
            $order,
            route('admin.orders.show', $order->id)
        ));
    }

    public function updateStatus(Request $request, $id)
    {
        $order = Order::with('items')->findOrFail($id);

        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'confirmed', 'packed', 'shipping', 'completed'])],
            'admin_note' => ['nullable', 'string', 'max:500'],
        ]);

        if ($order->isCancelled()) {
            return back()->with('error', 'Đơn hàng đã bị hủy, không thể đổi trạng thái.');
        }

        $from = $order->status;
        $to = $data['status'];

        if ($from === $to && ! $request->filled('admin_note')) {
            return back()->with('error', 'Trạng thái không thay đổi.');
        }

        DB::transaction(function () use ($order, $from, $to, $data) {
            $order->status = $to;
            $order->shipping_status = Order::STATUS_TO_SHIPPING[$to] ?? $order->shipping_status;

            if ($data['admin_note'] ?? null) {
                $order->admin_note = $data['admin_note'];
            }

            // Đơn COD khi giao thành công thì coi như đã thu đủ tiền
            if ($to === 'completed' && $order->payment_method === 'cod' && ! $order->isPaid()) {
                $order->payment_status = 'paid';

                PaymentTransaction::where('order_id', $order->id)
                    ->where('gateway', 'cod')
                    ->where('status', 'pending')
                    ->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                        'message' => 'Đã thu tiền khi giao hàng',
                    ]);
            }

            // Đơn giao thành công -> cộng điểm thưởng & cập nhật tổng chi tiêu để xét hạng
            if ($to === 'completed' && $order->points_earned <= 0) {
                $this->awardLoyaltyPoints($order);
            }

            $order->save();

            OrderStatusLog::create([
                'order_id' => $order->id,
                'changed_by' => Auth::id(),
                'from_status' => $from,
                'to_status' => $to,
                'note' => $data['admin_note'] ?? null,
            ]);
        });

        $this->notifyCustomer($order, $from);

        return back()->with('success', 'Đã cập nhật đơn hàng #' . $order->id . ' sang trạng thái "' . (Order::STATUSES[$to] ?? $to) . '". Email thông báo đã được gửi cho khách.');
    }

    /**
     * Hủy đơn hàng và HOÀN LẠI TỒN KHO cho từng sản phẩm trong đơn.
     * Cột restocked_at đảm bảo không bao giờ hoàn kho 2 lần cho cùng 1 đơn.
     */
    public function cancel(Request $request, $id)
    {
        $order = Order::with('items')->findOrFail($id);

        $data = $request->validate([
            'cancel_reason' => ['required', 'string', 'max:255'],
        ]);

        if (! $order->canBeCancelledByAdmin()) {
            return back()->with('error', 'Đơn hàng đã giao thành công hoặc đã hủy trước đó, không thể hủy.');
        }

        DB::transaction(function () use ($order, $data) {
            $from = $order->status;

            // Hoàn kho: cộng trả số lượng đã trừ lúc đặt hàng
            if (is_null($order->restocked_at)) {
                foreach ($order->items as $item) {
                    Product::where('id', $item->product_id)->increment('quantity', $item->quantity);
                }
                $order->restocked_at = now();
            }

            $order->status = 'cancelled';
            $order->shipping_status = 'cancelled';
            $order->cancel_reason = $data['cancel_reason'];
            $order->cancelled_at = now();

            // Đơn chưa thanh toán -> đánh dấu hủy; đã thanh toán online -> chờ hoàn tiền
            $order->payment_status = $order->isPaid() ? 'refund_pending' : 'cancelled';
            $order->save();

            PaymentTransaction::where('order_id', $order->id)
                ->whereIn('status', ['pending', 'initiated'])
                ->update(['status' => 'cancelled', 'message' => 'Đơn hàng bị hủy']);

            if ($order->payment_status === 'refund_pending') {
                PaymentTransaction::where('order_id', $order->id)
                    ->where('status', 'paid')
                    ->update(['status' => 'refund_pending', 'message' => 'Đơn bị hủy - chờ hoàn tiền cho khách']);
            }

            OrderStatusLog::create([
                'order_id' => $order->id,
                'changed_by' => Auth::id(),
                'from_status' => $from,
                'to_status' => 'cancelled',
                'note' => 'Lý do: ' . $data['cancel_reason'],
            ]);
        });

        $this->notifyCustomer($order, 'cancelled');

        $message = 'Đã hủy đơn hàng #' . $order->id . ' và hoàn lại tồn kho.';
        if ($order->payment_status === 'refund_pending') {
            $message .= ' Đơn này đã thanh toán online, vui lòng xử lý hoàn tiền cho khách.';
        }

        return back()->with('success', $message);
    }

    /**
     * Gửi email thông báo đổi trạng thái cho khách.
     * Lỗi SMTP chỉ ghi log để không làm hỏng thao tác của admin.
     */
    protected function notifyCustomer(Order $order, string $previousStatus): void
    {
        // Thông báo trong ứng dụng trước - không phụ thuộc SMTP nên luôn tới nơi
        UserNotification::send(
            $order->user_id,
            $order->status === 'cancelled' ? 'system' : 'order_status',
            'Đơn #' . $order->id . ': ' . $order->status_label,
            match ($order->status) {
                'confirmed' => 'Shop đã xác nhận đơn, đang chuẩn bị hàng cho bạn.',
                'packed' => 'Đơn đã đóng gói xong, sắp bàn giao cho đơn vị vận chuyển.',
                'shipping' => 'Đơn đang trên đường tới bạn. Giữ máy để shipper liên hệ nhé!',
                'completed' => 'Đơn đã giao thành công. Mời bạn đánh giá sản phẩm để nhận điểm thưởng.',
                'cancelled' => 'Đơn đã được hủy. Liên hệ 1900 6868 nếu bạn cần hỗ trợ thêm.',
                default => 'Trạng thái đơn hàng vừa được cập nhật.',
            },
            route('orders.show', $order->id)
        );

        try {
            $order->loadMissing('user');
            $email = $order->user->email ?? null;

            if ($email) {
                Mail::to($email)->send(new OrderStatusChangedMail($order, $previousStatus));
            }
        } catch (Throwable $e) {
            Log::warning('Không gửi được email cập nhật trạng thái đơn #' . $order->id, [
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Cộng điểm thưởng cho khách khi đơn giao thành công.
     * Quy đổi: mỗi 10.000đ giá trị đơn = 1 điểm. Đồng thời cộng dồn lifetime_spent
     * để hệ thống tự xét lại hạng thành viên (Đồng / Bạc / Vàng / Kim cương).
     */
    protected function awardLoyaltyPoints(Order $order): void
    {
        $user = User::find($order->user_id);

        if (! $user) {
            return;
        }

        $points = (int) floor($order->total_price / User::POINTS_PER_VND);

        $order->points_earned = $points;

        $user->lifetime_spent = (int) $user->lifetime_spent + (int) $order->total_price;
        $user->points = (int) $user->points + $points;
        $user->save();

        if ($points > 0) {
            PointTransaction::create([
                'user_id' => $user->id,
                'order_id' => $order->id,
                'points' => $points,
                'reason' => 'Tích điểm từ đơn hàng #' . $order->id,
            ]);
        }
    }
}
