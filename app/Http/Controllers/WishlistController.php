<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Danh sách yêu thích - cho phép khách lưu sản phẩm để mua sau.
 */
class WishlistController extends Controller
{
    public function index()
    {
        $items = Wishlist::with(['product.category'])
            ->where('user_id', Auth::id())
            ->latest('id')
            ->paginate(12);

        return view('wishlist.index', compact('items'));
    }

    /**
     * Bật/tắt trạng thái yêu thích của 1 sản phẩm.
     * Hỗ trợ cả AJAX (trả JSON) lẫn submit form thường (redirect back).
     */
    public function toggle(Request $request, Product $product)
    {
        $existing = Wishlist::where('user_id', Auth::id())
            ->where('product_id', $product->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $added = false;
            $message = 'Đã bỏ "' . $product->name . '" khỏi danh sách yêu thích.';
        } else {
            Wishlist::create(['user_id' => Auth::id(), 'product_id' => $product->id]);
            $added = true;
            $message = 'Đã thêm "' . $product->name . '" vào danh sách yêu thích.';
        }

        $count = Wishlist::where('user_id', Auth::id())->count();

        if ($request->ajax()) {
            return response()->json(['success' => true, 'added' => $added, 'message' => $message, 'count' => $count]);
        }

        return back()->with('success', $message);
    }

    public function destroy(Wishlist $wishlist)
    {
        abort_if((int) $wishlist->user_id !== (int) Auth::id(), 403);

        $wishlist->delete();

        return back()->with('success', 'Đã bỏ sản phẩm khỏi danh sách yêu thích.');
    }
}
