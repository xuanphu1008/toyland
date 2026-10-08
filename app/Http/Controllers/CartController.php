<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * Khóa dòng giỏ hàng: "12" cho sản phẩm thường, "12-5" cho sản phẩm 12 biến thể 5.
     * Cùng một sản phẩm khác biến thể là hai dòng riêng.
     */
    public static function cartKey(int $productId, ?int $variantId = null): string
    {
        return $variantId ? $productId . '-' . $variantId : (string) $productId;
    }

    /** Tìm biến thể khách chọn; sản phẩm có biến thể mà không chọn thì trả lỗi */
    private function resolveVariant(Request $request, Product $product, &$error = null): ?ProductVariant
    {
        $product->loadMissing('variants');

        if ($product->variants->isEmpty()) {
            return null;
        }

        $variant = $product->variants->firstWhere('id', (int) $request->input('variant_id'));

        if (! $variant) {
            $error = "Vui lòng chọn phân loại cho sản phẩm \"{$product->name}\".";
        }

        return $variant;
    }

    public function index()
    {
        $cart = session('cart', []);

        if (!empty($cart)) {
            $products = Product::with('variants')->whereIn('id', collect($cart)->pluck('product_id')->unique())->get()->keyBy('id');

            foreach ($cart as $key => &$item) {
                $product = $products[$item['product_id']] ?? null;
                $variant = ! empty($item['variant_id']) && $product ? $product->variants->firstWhere('id', $item['variant_id']) : null;

                // Sản phẩm bị xóa, hoặc biến thể bị xóa/không còn -> bỏ khỏi giỏ
                if (! $product || (! empty($item['variant_id']) && ! $variant)) {
                    unset($cart[$key]);
                    continue;
                }

                // Luôn đồng bộ ảnh/tên mới nhất từ DB
                $item['image'] = $variant && $variant->image ? $variant->image : $product->image;
                $item['name'] = $product->name;
                $item['variant_name'] = $variant?->name;

                // ĐỒNG BỘ LẠI GIÁ theo giá hiệu lực hiện tại (flash sale có hạn).
                // Giá biến thể = giá hiệu lực của sản phẩm + chênh lệch.
                $item['price'] = $variant ? $variant->effective_price : $product->effective_price;
                $item['base_price'] = $variant ? $variant->base_price : (int) $product->price;
                $item['on_sale'] = $product->is_on_sale;
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

        $variant = $this->resolveVariant($request, $product, $variantError);
        $error = $variantError ?: $this->mergeProductIntoCart($cart, $product, $variant, $requestedQty);

        if ($error) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $error], 422);
            }

            return back()->with('error', $error);
        }

        session(['cart' => $cart]);

        $label = $product->name . ($variant ? " ({$variant->name})" : '');
        $message = "Đã thêm sản phẩm \"{$label}\" vào giỏ hàng.";

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

        $variant = $this->resolveVariant($request, $product, $variantError);
        $error = $variantError ?: $this->mergeProductIntoCart($cart, $product, $variant, $requestedQty);

        if ($error) {
            return back()->with('error', $error);
        }

        session(['cart' => $cart]);

        return redirect()->route('checkout.index');
    }

    // Gộp số lượng sản phẩm vào giỏ hàng trong session, trả về thông báo lỗi (nếu có) hoặc null nếu thành công
    private function mergeProductIntoCart(array &$cart, Product $product, ?ProductVariant $variant, int $requestedQty): ?string
    {
        $key = self::cartKey($product->id, $variant?->id);
        $stock = $variant ? $variant->quantity : $product->quantity;
        $label = $product->name . ($variant ? " ({$variant->name})" : '');

        $newQty = ($cart[$key]['quantity'] ?? 0) + $requestedQty;

        if ($stock <= 0 || $newQty > $stock) {
            return "Sản phẩm \"{$label}\" chỉ còn {$stock} trong kho, không đủ số lượng bạn yêu cầu.";
        }

        $cart[$key] = [
            'product_id' => $product->id,
            'variant_id' => $variant?->id,
            'variant_name' => $variant?->name,
            'name' => $product->name,
            'image' => $variant && $variant->image ? $variant->image : $product->image,
            // Giá hiệu lực, không phải giá gốc - xem chú thích ở index()
            'price' => $variant ? $variant->effective_price : $product->effective_price,
            'base_price' => $variant ? $variant->base_price : (int) $product->price,
            'on_sale' => $product->is_on_sale,
            'quantity' => $newQty,
        ];

        return null;
    }

    public function update(Request $request, Product $product)
    {
        $cart = session('cart', []);
        $variantId = $request->input('variant_id') ? (int) $request->input('variant_id') : null;
        $key = self::cartKey($product->id, $variantId);

        if (!isset($cart[$key])) {
            return response()->json(['success' => false, 'message' => 'Sản phẩm không có trong giỏ hàng.'], 404);
        }

        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $quantity = (int) $request->quantity;
        $variant = $variantId ? ProductVariant::where('product_id', $product->id)->find($variantId) : null;
        $stock = $variant ? $variant->quantity : $product->quantity;

        if ($quantity > $stock) {
            return response()->json([
                'success' => false,
                'message' => "Sản phẩm \"{$product->name}\" chỉ còn {$stock} trong kho.",
            ], 422);
        }

        $cart[$key]['quantity'] = $quantity;
        session(['cart' => $cart]);

        $cartTotal = 0;
        foreach ($cart as $item) {
            $cartTotal += $item['price'] * $item['quantity'];
        }

        // Dùng đúng đơn giá đang lưu trong giỏ (giá hiệu lực, đã tính sale và biến thể)
        $lineTotal = $cart[$key]['price'] * $quantity;

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

        unset($cart[self::cartKey($product->id, $request->input('variant_id') ? (int) $request->input('variant_id') : null)]);

        session(['cart' => $cart]);

        return back()->with('success', 'Đã xóa sản phẩm khỏi giỏ hàng.');
    }
}
