<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Kiểm duyệt đánh giá: admin có thể ẩn/hiện, trả lời hoặc xóa đánh giá.
 */
class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'visible' => ['nullable', Rule::in(['1', '0'])],
        ]);

        $query = Review::with(['user', 'product']);

        if ($request->filled('search')) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('comment', 'like', '%'.$search.'%')
                  ->orWhereHas('product', fn ($p) => $p->where('name', 'like', '%'.$search.'%'))
                  ->orWhereHas('user', fn ($u) => $u->where('name', 'like', '%'.$search.'%'));
            });
        }

        if ($request->filled('rating')) {
            $query->where('rating', $filters['rating']);
        }

        if ($request->filled('visible')) {
            $query->where('is_visible', $filters['visible'] === '1');
        }

        $reviews = $query->latest()->paginate(15)->withQueryString();

        $stats = [
            'total' => Review::count(),
            'hidden' => Review::where('is_visible', false)->count(),
            'average' => round((float) Review::where('is_visible', true)->avg('rating'), 1),
        ];

        return view('admin.reviews.index', compact('reviews', 'filters', 'stats'));
    }

    // Ẩn/hiện đánh giá (không xóa dữ liệu)
    public function toggle(Review $review)
    {
        $review->update(['is_visible' => ! $review->is_visible]);

        return back()->with('success', $review->is_visible
            ? 'Đã hiển thị lại đánh giá.'
            : 'Đã ẩn đánh giá khỏi trang sản phẩm.');
    }

    public function reply(Request $request, Review $review)
    {
        $data = $request->validate([
            'reply' => ['required', 'string', 'max:1000'],
        ], [], ['reply' => 'Nội dung phản hồi']);

        $review->update([
            'reply' => $data['reply'],
            'replied_at' => now(),
        ]);

        return back()->with('success', 'Đã gửi phản hồi tới đánh giá của khách.');
    }

    public function destroy(Review $review)
    {
        $review->delete();

        return back()->with('success', 'Đã xóa đánh giá.');
    }
}
