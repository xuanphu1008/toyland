<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Orders\OrderWorkflow;
use App\Services\Orders\WorkflowException;
use App\Services\Shipping\ShippingCarrier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * MÀN HÌNH ĐƠN VỊ VẬN CHUYỂN
 *
 * Mô phỏng phía bên giao hàng: quản trị viên ĐÓNG VAI shipper để chạy trọn
 * vòng đời một đơn mà không cần đối tác thật:
 *
 *   Chờ lấy hàng --lấy hàng--> Đã lấy --đi giao--> Đang giao --+--> Giao thành công
 *                                  ^                           |
 *                                  +------ giao lại -----------+--> Giao thất bại --> Chuyển hoàn --> Đã hoàn về shop
 *
 * Mọi nút bấm ở đây đều gọi OrderWorkflow với vai trò 'carrier', nên bên vận
 * chuyển KHÔNG làm được việc của shop (xác nhận, hủy đơn...) và ngược lại.
 */
class CarrierController extends Controller
{
    private const TABS = [
        'pickup' => ['label' => 'Chờ lấy hàng', 'statuses' => ['packed']],
        'delivering' => ['label' => 'Đang giao', 'statuses' => ['shipping']],
        'returning' => ['label' => 'Đang hoàn', 'statuses' => ['returning']],
        'done' => ['label' => 'Đã xong (7 ngày)', 'statuses' => ['completed', 'returned']],
    ];

    public function __construct(private OrderWorkflow $workflow)
    {
    }

    public function index(Request $request)
    {
        $tab = array_key_exists($request->query('tab'), self::TABS) ? $request->query('tab') : 'pickup';
        $search = trim((string) $request->query('q', ''));

        $counts = Order::whereIn('status', ['packed', 'shipping', 'returning'])
            ->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        $query = Order::with(['user', 'items.product'])
            ->whereNotNull('ghn_order_code');

        if ($search !== '') {
            // Tìm theo mã vận đơn / mã đơn / số điện thoại - bỏ qua tab
            $query->where(function ($q) use ($search) {
                $q->where('ghn_order_code', 'like', '%' . $search . '%')
                    ->orWhere('customer_phone', 'like', '%' . $search . '%');

                if (ctype_digit(ltrim($search, '#'))) {
                    $q->orWhere('id', (int) ltrim($search, '#'));
                }
            });
        } else {
            $query->whereIn('status', self::TABS[$tab]['statuses']);

            if ($tab === 'done') {
                $query->where('updated_at', '>=', now()->subDays(7));
            }
        }

        $orders = $query->orderBy('waybill_created_at')->orderBy('id')->paginate(30)->withQueryString();

        $actions = $orders->getCollection()->mapWithKeys(
            fn (Order $o) => [$o->id => $this->workflow->available($o, 'carrier')]
        );

        $tabs = collect(self::TABS)->map(function ($t, $key) use ($counts) {
            $t['count'] = $key === 'done' ? null : collect($t['statuses'])->sum(fn ($s) => $counts->get($s, 0));

            return $t;
        });

        $bulkActions = collect(OrderWorkflow::BULK_ACTIONS['carrier'])
            ->mapWithKeys(fn ($a) => [$a => OrderWorkflow::ACTIONS[$a]['label']]);

        $carrier = app(ShippingCarrier::class);

        return view('admin.carrier.index', [
            'orders' => $orders,
            'actions' => $actions,
            'tabs' => $tabs,
            'tab' => $tab,
            'search' => $search,
            'bulkActions' => $bulkActions,
            'carrierName' => $carrier->name(),
            'manual' => $carrier->isManuallyOperated(),
            'maxAttempts' => $this->workflow->maxDeliveryAttempts(),
            'codToCollect' => (int) Order::where('status', 'shipping')->where('payment_method', 'cod')
                ->where('payment_status', '!=', 'paid')->sum('total_price'),
        ]);
    }

    /** Một thao tác của shipper trên một đơn */
    public function act(Request $request, Order $order)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(array_keys(array_filter(
                OrderWorkflow::ACTIONS,
                fn ($def) => in_array('carrier', $def['actors'], true)
            )))],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $order = $this->workflow->apply($order, $data['action'], 'carrier', ['note' => $data['note'] ?? '']);
        } catch (WorkflowException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', sprintf(
            '%s: %s → %s',
            $order->ghn_order_code,
            OrderWorkflow::ACTIONS[$data['action']]['label'],
            $order->shipping_label
        ));
    }

    /** Thao tác hàng loạt (lấy hàng cả chuyến, giao xong cả tuyến...) */
    public function bulk(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:' . OrderWorkflow::BULK_LIMIT],
            'ids.*' => ['integer'],
            'action' => ['required', Rule::in(OrderWorkflow::BULK_ACTIONS['carrier'])],
        ], ['ids.required' => 'Hãy tick chọn ít nhất một đơn.']);

        $result = $this->workflow->bulk($data['ids'], $data['action'], 'carrier');

        return back()->with(OrderController::bulkFlash($result, OrderWorkflow::ACTIONS[$data['action']]['label']));
    }
}
