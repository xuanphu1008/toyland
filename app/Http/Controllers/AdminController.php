<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;

class AdminController extends Controller
{
    public function index()
    {
        $totalProducts = Product::count();
        $totalCategories = Category::count();
        $outOfStock = Product::where('quantity', '<=', 0)->count();

        // Số liệu vận hành để admin nắm nhanh tình hình trong ngày
        $pendingOrders = Order::where('status', 'pending')->count();
        $shippingOrders = Order::whereIn('status', ['confirmed', 'packed', 'shipping', 'returning'])->count();
        $totalOrders = Order::count();
        $totalCustomers = User::where('role', 'user')->count();

        $revenueToday = (int) Order::whereDate('created_at', today())
            ->whereNotIn('status', Order::EXCLUDED_FROM_SALES)
            ->sum('total_price');

        $revenueMonth = (int) Order::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->whereNotIn('status', Order::EXCLUDED_FROM_SALES)
            ->sum('total_price');

        $newReviews = Review::whereDate('created_at', '>=', now()->subDays(7))->count();

        // Đơn mới nhất cần xử lý
        $latestOrders = Order::with('user')->latest('id')->limit(5)->get();

        // Top 5 bán chạy hiển thị ngay trên bảng điều khiển
        $bestSellers = Product::bestSellers(5);

        return view('admin.dashboard', compact(
            'totalProducts', 'totalCategories', 'outOfStock',
            'pendingOrders', 'shippingOrders', 'totalOrders', 'totalCustomers',
            'revenueToday', 'revenueMonth', 'newReviews', 'latestOrders', 'bestSellers'
        ));
    }
}
