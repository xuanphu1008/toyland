<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    private function paidOrders(): Builder
    {
        $paymentStatus = DB::table('payment_transactions')->select('status')
            ->whereColumn('order_id', 'orders.id')
            ->orderByRaw("CASE WHEN status IN ('paid', 'refund_pending', 'refunded') THEN 0 ELSE 1 END")
            ->orderByDesc('id')->limit(1);

        return Order::query()->where('orders.created_at', '<=', now())
            ->whereNotIn('orders.status', \App\Models\Order::EXCLUDED_FROM_SALES)
            ->whereNotIn('orders.shipping_status', ['cancelled', 'return', 'returned'])
            ->where(function (Builder $query) use ($paymentStatus) {
                $query->where($paymentStatus, 'paid')
                      ->orWhere(function (Builder $legacy) {
                          $legacy->whereDoesntHave('paymentTransactions')
                                 ->whereIn('orders.status', ['paid', 'cod_paid', 'paid_momo']);
                      });
            });
    }

    private function categoryRevenue(): Collection
    {
        return DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->whereIn('order_items.order_id', $this->paidOrders()->select('orders.id'))
            ->select('products.category_id', 'categories.name as category_name')
            ->selectRaw('SUM(order_items.price * order_items.quantity) as total_revenue, SUM(order_items.quantity) as total_qty')
            ->groupBy('products.category_id', 'categories.name')
            ->orderByDesc('total_revenue')->get();
    }

    /**
     * Top sản phẩm bán chạy theo doanh thu và số lượng, chỉ tính các đơn đã thanh toán.
     * Đáp ứng yêu cầu "Thống kê doanh số: báo cáo doanh thu, sản phẩm bán chạy".
     */
    private function topProducts(int $limit = 10): Collection
    {
        return DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->whereIn('order_items.order_id', $this->paidOrders()->select('orders.id'))
            ->select('products.id', 'products.name', 'products.image', 'categories.name as category_name')
            ->selectRaw('SUM(order_items.quantity) as total_qty, SUM(order_items.price * order_items.quantity) as total_revenue')
            ->groupBy('products.id', 'products.name', 'products.image', 'categories.name')
            ->orderByDesc('total_qty')
            ->limit($limit)
            ->get();
    }

    private function dailyRevenue(): Collection
    {
        return $this->paidOrders()
            ->selectRaw('CAST(orders.created_at AS DATE) as date, SUM(total_price) as total_revenue, COUNT(*) as order_count')
            ->groupByRaw('CAST(orders.created_at AS DATE)')->orderBy('date')->get();
    }

    private function periodRevenue(Collection $days, string $period): Collection
    {
        return $days->groupBy(fn ($day) => substr($day->date, 0, $period === 'month' ? 7 : 4))
            ->map(fn (Collection $rows, $key) => (object) [
                $period => (string) $key,
                'total_revenue' => $rows->sum('total_revenue'),
                'order_count' => $rows->sum('order_count'),
            ])->values();
    }

    public function index()
    {
        $categoryRevenue = $this->categoryRevenue();
        $topProducts = $this->topProducts(10);
        $totalOrders = Order::where('created_at', '<=', now())->count();
        $totalCustomers = DB::table('users')->where('role', 'user')->count();
        $revenueByDate = $this->dailyRevenue();
        $revenueByMonth = $this->periodRevenue($revenueByDate, 'month');
        $revenueByYear = $this->periodRevenue($revenueByDate, 'year');
        $totalRevenue = $revenueByDate->sum('total_revenue');

        return view('admin.reports.index', compact(
            'categoryRevenue', 'topProducts', 'totalOrders', 'totalCustomers', 'totalRevenue',
            'revenueByDate', 'revenueByMonth', 'revenueByYear'
        ));
    }

    public function charts()
    {
        $categories = $this->categoryRevenue();
        $catLabels = $categories->map(fn ($row) => $row->category_name ?? 'Danh mục #'.$row->category_id)->all();
        $catRevenue = $categories->pluck('total_revenue')->map(fn ($value) => (float) $value)->all();

        $daily = $this->dailyRevenue();
        $byDate = $daily->keyBy('date');
        $byMonth = $this->periodRevenue($daily, 'month')->keyBy('month');
        $byYear = $this->periodRevenue($daily, 'year');

        $startDay = Carbon::now()->startOfDay()->subDays(29);
        $startMonth = Carbon::now()->startOfMonth()->subMonths(11);
        $revDateLabels = [];
        $revDateData = [];
        $revMonthLabels = [];
        $revMonthData = [];

        for ($i = 0; $i < 30; $i++) {
            $date = $startDay->copy()->addDays($i)->toDateString();
            $revDateLabels[] = $date;
            $revDateData[] = (float) ($byDate->get($date)?->total_revenue ?? 0);
        }

        for ($i = 0; $i < 12; $i++) {
            $month = $startMonth->copy()->addMonths($i);
            $revMonthLabels[] = $month->format('m/Y');
            $revMonthData[] = (float) ($byMonth->get($month->format('Y-m'))?->total_revenue ?? 0);
        }

        $revYearLabels = $byYear->pluck('year')->all();
        $revYearData = $byYear->pluck('total_revenue')->map(fn ($value) => (float) $value)->all();

        $gateway = DB::table('payment_transactions')->select('gateway')
            ->whereColumn('order_id', 'orders.id')->where('status', 'paid')->orderByDesc('id')->limit(1);

        $paid = $this->paidOrders()->select('orders.total_price')->selectSub($gateway, 'gateway')
            ->selectRaw("CASE WHEN orders.status = 'cod_paid' THEN 'cod' ELSE 'momo' END as legacy_gateway");

        $methodRevenue = DB::query()->fromSub($paid, 'paid_orders')
            ->selectRaw('COALESCE(gateway, legacy_gateway) as method, SUM(total_price) as revenue')
            ->groupByRaw('COALESCE(gateway, legacy_gateway)')->pluck('revenue', 'method');

        $paymentMethodLabels = ['MoMo', 'COD'];
        $paymentMethodRevenue = [(float) $methodRevenue->get('momo', 0), (float) $methodRevenue->get('cod', 0)];

        // Biểu đồ Top 10 sản phẩm bán chạy (theo số lượng đã bán)
        $topProducts = $this->topProducts(10);
        $topProductLabels = $topProducts->pluck('name')
            ->map(fn ($name) => mb_strlen($name) > 28 ? mb_substr($name, 0, 27) . '…' : $name)
            ->all();
        $topProductQty = $topProducts->pluck('total_qty')->map(fn ($value) => (int) $value)->all();

        return view('admin.reports.charts', compact(
            'catLabels', 'catRevenue', 'revDateLabels', 'revDateData',
            'revMonthLabels', 'revMonthData', 'revYearLabels', 'revYearData',
            'paymentMethodLabels', 'paymentMethodRevenue',
            'topProductLabels', 'topProductQty'
        ));
    }
}
