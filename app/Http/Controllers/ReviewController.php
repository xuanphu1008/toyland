<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Hệ thống phản hồi & đánh giá của khách hàng.
 * Điều kiện bắt buộc: chỉ đánh giá được sản phẩm thuộc đơn hàng ĐÃ GIAO THÀNH CÔNG.
 */
class ReviewController extends Controller
{
    public function store(Request $request, Product $product)
    {
        $user = Auth::user();

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ], [], [
            'rating' => 'Số sao',
            'comment' => 'Nội dung đánh giá',
        ]);

        $order = $user->completedOrderFor($product->id);

        if (! $order) {
            return back()->with('error', 'Bạn chỉ có thể đánh giá sản phẩm sau khi đơn hàng chứa sản phẩm này được giao thành công.');
        }

        // updateOrCreate: khách sửa lại đánh giá cũ thay vì tạo thêm bản ghi trùng
        Review::updateOrCreate(
            ['user_id' => $user->id, 'product_id' => $product->id],
            [
                'order_id' => $order->id,
                'rating' => $data['rating'],
                'comment' => $data['comment'] ?? null,
                'is_visible' => true,
            ]
        );

        return back()->with('success', 'Cảm ơn bạn đã đánh giá sản phẩm!');
    }

    public function destroy(Review $review)
    {
        abort_if((int) $review->user_id !== (int) Auth::id(), 403);

        $review->delete();

        return back()->with('success', 'Đã xóa đánh giá của bạn.');
    }

    /**
     * Danh sách sản phẩm khách đã mua thành công nhưng chưa đánh giá,
     * kèm các đánh giá đã viết - hiển thị trong khu "Đánh giá của tôi".
     */
    public function index()
    {
        $user = Auth::user();

        $myReviews = $user->reviews()->with('product')->latest()->get();

        // Sản phẩm đã nhận hàng thành công
        $purchased = Product::whereHas('orderItems.order', function ($query) use ($user) {
            $query->where('user_id', $user->id)->where('status', 'completed');
        })->get();

        $reviewedIds = $myReviews->pluck('product_id')->all();
        $pending = $purchased->reject(fn ($product) => in_array($product->id, $reviewedIds, true))->values();

        return view('reviews.index', compact('myReviews', 'pending'));
    }
}
