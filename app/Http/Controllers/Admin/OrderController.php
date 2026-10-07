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
use App\Services\Orders\OrderWorkflow;
use App\Services\Orders\WorkflowException;
use Throwable;

class OrderController extends Controller
{
    /**
     * Các tab theo TRẠNG THÁI ĐƠN - mỗi tab là một "hàng chờ" việc của shop.
     */
    private const TABS = [
        'all' => ['label' => 'Tất cả', 'color' => 'blue', 'statuses' => []],
        'awaiting' => ['label' => 'Chờ thanh toán', 'color' => 'amber', 'statuses' => ['awaiting_payment']],
        'pending' => ['label' => 'Chờ xác nhận', 'color' => 'slate', 'statuses' => ['pending']],
        'confirmed' => ['label' => 'Chờ đóng gói', 'color' => 'cyan', 'statuses' => ['confirmed']],
        'ready' => ['label' => 'Chờ lấy hàng', 'color' => 'cyan', 'statuses' => ['packed']],
        'delivering' => ['label' => 'Đang giao', 'color' => 'amber', 'statuses' => ['shipping']],
        'delivered' => ['label' => 'Thành công', 'color' => 'green', 'statuses' => ['completed']],
        'return' => ['label' => 'Hoàn hàng', 'color' => 'orange', 'statuses' => ['returning', 'returned']],
        'cancelled' => ['label' => 'Đã hủy', 'color' => 'red', 'statuses' => ['cancelled']],
    ];

    public function index(Request $request)
    {
        // Hủy các đơn MoMo quá hạn giữ hàng để danh sách phản ánh đúng
        app(OrderWorkflow::class)->releaseExpired();

        $paymentLabels = Order::PAYMENT_LABELS;
        $shippingLabels = Order::SHIPPING_LABELS;

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
                'orders.payment_expires_at', 'orders.carrier', 'orders.delivery_attempts', 'orders.last_delivery_note',
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

        $statusCounts = (clone $query)->select('status')->selectRaw('COUNT(*) as total')
            ->groupBy('status')->pluck('total', 'status');

        $tabs = collect(self::TABS)->map(function ($tab, $key) use ($statusCounts) {
            $tab['count'] = $key === 'all' ? $statusCounts->sum() : collect($tab['statuses'])->sum(fn ($status) => $statusCounts->get($status, 0));
            return $tab;
        });

        $activeTab = $filters['tab'] ?? 'all';
        if ($activeTab !== 'all') {
            $query->whereIn('status', self::TABS[$activeTab]['statuses']);
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

        $bulkActions = collect(OrderWorkflow::BULK_ACTIONS['admin'])
            ->mapWithKeys(fn ($a) => [$a => OrderWorkflow::ACTIONS[$a]['label']])
            ->put('print', 'In phiếu giao hàng');

        return view('admin.order.index', compact(
            'orders', 'filters', 'tabs', 'activeTab', 'paymentLabels', 'shippingLabels', 'orderStatusLabels', 'bulkActions'
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

        $workflow = app(OrderWorkflow::class);
        $adminActions = $workflow->available($order, 'admin');
        $carrierActions = $workflow->available($order, 'carrier');

        return view('admin.order.show', compact('order', 'statusLogs', 'adminActions', 'carrierActions'));
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

    /**
     * Shop thao tác trên MỘT đơn: xác nhận / đóng gói (tạo mã vận đơn) / nhận lại hàng hoàn.
     * Mọi kiểm tra và việc đi kèm nằm trong OrderWorkflow.
     */
    public function updateStatus(Request $request, $id)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['confirm', 'pack', 'confirm_returned'])],
            'admin_note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $order = app(OrderWorkflow::class)->apply((int) $id, $data['action'], 'admin', [
                'note' => $data['admin_note'] ?? '',
            ]);
        } catch (WorkflowException $e) {
            return back()->with('error', $e->getMessage());
        }

        if (! empty($data['admin_note'])) {
            $order->update(['admin_note' => $data['admin_note']]);
        }

        $message = 'Đơn #' . $order->id . ' chuyển sang "' . $order->status_label . '".';

        if ($data['action'] === 'pack') {
            $message .= ' Mã vận đơn: ' . $order->ghn_order_code . '.';
        }

        return back()->with('success', $message);
    }

    /** Shop hủy đơn (trước khi bên vận chuyển lấy hàng): trả kho, xử lý tiền, hủy vận đơn nếu có */
    public function cancel(Request $request, $id)
    {
        $data = $request->validate([
            'cancel_reason' => ['required', 'string', 'max:255'],
        ]);

        try {
            $order = app(OrderWorkflow::class)->apply((int) $id, 'cancel', 'admin', ['note' => $data['cancel_reason']]);
        } catch (WorkflowException $e) {
            return back()->with('error', $e->getMessage());
        }

        $message = 'Đã hủy đơn hàng #' . $order->id . ' và hoàn lại tồn kho.';

        if ($order->payment_status === 'refund_pending') {
            $message .= ' Đơn này đã thanh toán online, vui lòng xử lý hoàn tiền cho khách.';
        }

        return back()->with('success', $message);
    }

    /**
     * XỬ LÝ HÀNG LOẠT: chọn nhiều đơn ở danh sách rồi xác nhận / đóng gói / hủy /
     * in phiếu giao hàng. Mỗi đơn xử lý độc lập - đơn nào không hợp lệ thì bỏ qua
     * và báo lại lý do, không làm hỏng các đơn còn lại.
     */
    public function bulk(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:' . OrderWorkflow::BULK_LIMIT],
            'ids.*' => ['integer'],
            'action' => ['required', Rule::in(array_merge(OrderWorkflow::BULK_ACTIONS['admin'], ['print']))],
            'cancel_reason' => ['nullable', 'required_if:action,cancel', 'string', 'max:255'],
        ], [
            'ids.required' => 'Hãy tick chọn ít nhất một đơn.',
            'cancel_reason.required_if' => 'Hủy hàng loạt cần ghi lý do.',
        ]);

        if ($data['action'] === 'print') {
            return redirect()->route('admin.orders.labels', ['ids' => implode(',', $data['ids'])]);
        }

        $result = app(OrderWorkflow::class)->bulk($data['ids'], $data['action'], 'admin', [
            'note' => $data['cancel_reason'] ?? '',
        ]);

        return back()->with(self::bulkFlash($result, OrderWorkflow::ACTIONS[$data['action']]['label']));
    }

    /**
     * Phiếu giao hàng để in và dán lên kiện (mã vận đơn, người nhận, tiền thu hộ).
     * ?ids=1,2,3 - chỉ in được đơn đã có mã vận đơn.
     */
    public function labels(Request $request)
    {
        $ids = array_slice(array_filter(array_map('intval', explode(',', (string) $request->query('ids')))), 0, OrderWorkflow::BULK_LIMIT);

        $orders = Order::with(['user', 'items.product'])
            ->whereIn('id', $ids)
            ->whereNotNull('ghn_order_code')
            ->orderBy('id')
            ->get();

        if ($orders->isEmpty()) {
            return redirect()->route('admin.orders.index')
                ->with('error', 'Chưa có đơn nào có mã vận đơn để in. Hãy "Đóng gói & tạo vận đơn" trước.');
        }

        return view('admin.order.labels', [
            'orders' => $orders,
            'skipped' => count($ids) - $orders->count(),
            'carrierName' => app(\App\Services\Shipping\ShippingCarrier::class)->name(),
        ]);
    }

    /** Gom kết quả xử lý hàng loạt thành thông báo flash */
    public static function bulkFlash(array $result, string $label): array
    {
        $okCount = count($result['ok']);
        $failCount = count($result['failed']);
        $flash = [];

        if ($okCount) {
            $flash['success'] = sprintf('%s: thành công %d đơn (%s).', $label, $okCount,
                implode(', ', array_map(fn ($id) => '#' . $id, $result['ok'])));
        }

        if ($failCount) {
            $lines = collect($result['failed'])->take(10)->map(fn ($msg, $id) => $msg)->implode(' • ');
            $flash['error'] = sprintf('Bỏ qua %d đơn: %s%s', $failCount, $lines, $failCount > 10 ? ' …' : '');
        }

        return $flash ?: ['error' => 'Không có đơn nào được xử lý.'];
    }
}
