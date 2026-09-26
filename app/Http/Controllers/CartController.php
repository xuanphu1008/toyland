<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index()
    {
        $cart = session('cart', []);

        if (!empty($cart)) {
            $products = Product::whereIn('id', array_keys($cart))->get()->keyBy('id');

            foreach ($cart as $productId => &$item) {
                if (!isset($products[$productId])) {
                    // Sản phẩm đã bị xóa khỏi hệ thống -> bỏ khỏi giỏ
                    unset($cart[$productId]);
                    continue;
                }

                // Luôn đồng bộ ảnh/tên mới nhất từ DB, vá lại các giỏ hàng cũ
                // được lưu từ trước khi trường 'image' tồn tại trong session.
                $item['image'] = $products[$productId]->image;
                $item['name'] = $products[$productId]->name;

                // ĐỒNG BỘ LẠI GIÁ theo giá hiệu lực hiện tại.
                //
                // Bắt buộc phải làm vì flash sale có hạn: khách bỏ món vào giỏ
                // lúc đang giảm giá rồi để đó qua đêm, sáng hôm sau hết sale mà
                // giỏ vẫn giữ giá cũ thì shop bán lỗ. Ngược lại, món vừa vào
                // đợt giảm giá thì khách phải được hưởng ngay.
                //
                // Lưu thêm giá gốc để hiện gạch ngang cho khách thấy đang lời.
                $item['price'] = $products[$productId]->effective_price;
                $item['base_price'] = (int) $products[$productId]->price;
                $item['on_sale'] = $products[$productId]->is_on_sale;
            }
            unset($item);

            session(['cart' => $cart]);
        }

        return view('cart.index', compact('cart'));
    }

    public function add(Request $request, Product $product)
    {
        $cart = session('cart', []);
        $requestedQty = max(1, (int) $request->input('quantity', 1));

        $error = $this->mergeProductIntoCart($cart, $product, $requestedQty);

        if ($error) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $error], 422);
            }

            return back()->with('error', $error);
        }

        session(['cart' => $cart]);

        $message = "Đã thêm sản phẩm \"{$product->name}\" vào giỏ hàng.";

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'cart_count' => count($cart),
            ]);
        }

        return back()->with('success', $message);
    }

    // Dùng cho nút "Mua ngay": thêm sản phẩm vào giỏ rồi chuyển thẳng sang trang thanh toán
    public function buyNow(Request $request, Product $product)
    {
        $cart = session('cart', []);
        $requestedQty = max(1, (int) $request->input('quantity', 1));

        $error = $this->mergeProductIntoCart($cart, $product, $requestedQty);

        if ($error) {
            return back()->with('error', $error);
        }

        session(['cart' => $cart]);

        return redirect()->route('checkout.index');
    }

    // Gộp số lượng sản phẩm vào giỏ hàng trong session, trả về thông báo lỗi (nếu có) hoặc null nếu thành công
    private function mergeProductIntoCart(array &$cart, Product $product, int $requestedQty): ?string
    {
        $currentQtyInCart = $cart[$product->id]['quantity'] ?? 0;
        $newQty = $currentQtyInCart + $requestedQty;

        if ($product->quantity <= 0 || $newQty > $product->quantity) {
            return "Sản phẩm \"{$product->name}\" chỉ còn {$product->quantity} trong kho, không đủ số lượng bạn yêu cầu.";
        }

        $cart[$product->id] = [
            'product_id' => $product->id,
            'name' => $product->name,
            'image' => $product->image,
            // Giá hiệu lực, không phải giá gốc - xem chú thích ở index()
            'price' => $product->effective_price,
            'base_price' => (int) $product->price,
            'on_sale' => $product->is_on_sale,
            'quantity' => $newQty,
        ];

        return null;
    }

    public function update(Request $request, Product $product)
    {
        $cart = session('cart', []);

        if (!isset($cart[$product->id])) {
            return response()->json(['success' => false, 'message' => 'Sản phẩm không có trong giỏ hàng.'], 404);
        }

        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $quantity = (int) $request->quantity;

        if ($quantity > $product->quantity) {
            return response()->json([
                'success' => false,
                'message' => "Sản phẩm \"{$product->name}\" chỉ còn {$product->quantity} trong kho.",
            ], 422);
        }

        $cart[$product->id]['quantity'] = $quantity;
        session(['cart' => $cart]);

        $cartTotal = 0;
        foreach ($cart as $item) {
            $cartTotal += $item['price'] * $item['quantity'];
        }

        $lineTotal = $product->price * $quantity;

        return response()->json([
            'success' => true,
            'quantity' => $quantity,
            'line_total_formatted' => number_format($lineTotal, 0, ',', '.') . ' đ',
            'cart_total_formatted' => number_format($cartTotal, 0, ',', '.') . ' đ',
            'cart_count' => count($cart),
        ]);
    }

    public function remove(Request $request, Product $product)
    {
        $cart = session('cart', []);

        unset($cart[$product->id]);

        session(['cart' => $cart]);

        return back()->with('success', 'Đã xóa sản phẩm khỏi giỏ hàng.');
    }
}
