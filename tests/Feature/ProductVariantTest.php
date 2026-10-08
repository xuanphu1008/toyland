<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Orders\OrderWorkflow;
use App\Services\ProductMerger;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Kiểm thử biến thể sản phẩm + gộp sản phẩm trùng.
 * Chạy: php artisan test --filter=ProductVariantTest
 */
class ProductVariantTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;
    private Product $product;
    private ProductVariant $red;
    private ProductVariant $blue;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->user = User::factory()->create(['role' => 'user', 'email_verified_at' => now()]);

        $this->product = Product::first();
        $this->product->variants()->delete();
        $this->product->update(['price' => 100000, 'sale_price' => null, 'quantity' => 15]);
        $this->red = $this->product->variants()->create(['name' => 'Đỏ', 'price_adjust' => 0, 'quantity' => 10, 'sort_order' => 0]);
        $this->blue = $this->product->variants()->create(['name' => 'Xanh', 'price_adjust' => 20000, 'quantity' => 5, 'sort_order' => 1]);
    }

    public function test_san_pham_co_phan_loai_bat_buoc_chon_phan_loai(): void
    {
        $r = $this->actingAs($this->user)->withHeader('X-Requested-With', 'XMLHttpRequest')->postJson(route('cart.add', $this->product->id), ['quantity' => 1]);
        $r->assertStatus(422);
        $this->assertEmpty(session('cart', []));
    }

    public function test_them_gio_dung_khoa_dong_gia_va_gioi_han_theo_kho_phan_loai(): void
    {
        $this->actingAs($this->user)->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->postJson(route('cart.add', $this->product->id), ['quantity' => 2, 'variant_id' => $this->blue->id])
            ->assertOk();

        $key = $this->product->id . '-' . $this->blue->id;
        $cart = session('cart');
        $this->assertArrayHasKey($key, $cart);
        $this->assertSame(120000, $cart[$key]['price']); // giá gốc + chênh lệch
        $this->assertSame('Xanh', $cart[$key]['variant_name']);

        // Xanh chỉ còn 5 -> thêm 4 nữa là vượt (2 + 4 = 6)
        $this->actingAs($this->user)->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->postJson(route('cart.add', $this->product->id), ['quantity' => 4, 'variant_id' => $this->blue->id])
            ->assertStatus(422);

        // Phân loại khác là dòng riêng, tồn kho riêng
        $this->actingAs($this->user)->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->postJson(route('cart.add', $this->product->id), ['quantity' => 8, 'variant_id' => $this->red->id])
            ->assertOk();
        $this->assertCount(2, session('cart'));

        // Cập nhật số lượng: thành tiền dùng đơn giá trong giỏ (120.000 x 3)
        $this->patchJson(route('cart.update', $this->product->id), ['quantity' => 3, 'variant_id' => $this->blue->id])
            ->assertOk()->assertJsonPath('line_total_formatted', '360.000 đ');

        // Xóa đúng dòng, không đụng dòng kia
        $this->delete(route('cart.remove', $this->product->id), ['variant_id' => $this->blue->id]);
        $this->assertArrayNotHasKey($key, session('cart'));
        $this->assertCount(1, session('cart'));
    }

    public function test_flash_sale_ap_cho_ca_san_pham_moi_phan_loai_cung_giam(): void
    {
        $this->product->update(['sale_price' => 80000, 'sale_starts_at' => null, 'sale_ends_at' => now()->addDay()]);
        $this->product->refresh()->load('variants');

        $this->assertSame(80000, $this->red->fresh()->effective_price);
        $this->assertSame(100000, $this->blue->fresh()->effective_price); // 80.000 + 20.000
        $this->assertSame(80000, $this->product->min_effective_price);
    }

    public function test_huy_don_tra_kho_dung_phan_loai(): void
    {
        $this->product->update(['quantity' => 13]);
        $this->blue->update(['quantity' => 3]); // khách vừa mua 2 cái xanh
        $order = Order::create([
            'user_id' => $this->user->id, 'total_price' => 240000, 'status' => 'pending',
            'payment_method' => 'cod', 'payment_status' => 'pending', 'shipping_address' => 'Hà Nội',
            'customer_phone' => '0900000000', 'shipping_status' => 'not_shipped',
        ]);
        OrderItem::create(['order_id' => $order->id, 'product_id' => $this->product->id, 'variant_id' => $this->blue->id,
            'variant_name' => 'Xanh', 'quantity' => 2, 'price' => 120000]);

        app(OrderWorkflow::class)->apply($order, 'cancel', 'admin', ['note' => 'Test']);

        $this->assertSame(5, $this->blue->fresh()->quantity);
        $this->assertSame(10, $this->red->fresh()->quantity);
        $this->assertSame(15, $this->product->fresh()->quantity);
        $this->assertSame('Xanh', $order->items()->first()->variant_name);
        $this->assertStringContainsString('(Xanh)', $order->items()->first()->display_name);
    }

    public function test_gop_san_pham_trung_va_thanh_phan_loai_chay_lai_an_toan(): void
    {
        $main = Product::first();
        $copy = $main->replicate();
        $copy->name = 'Bản trùng thử nghiệm';
        $copy->price = 150000;
        $copy->quantity = 7;
        $copy->save();

        // Đơn cũ trỏ vào bản trùng
        $order = Order::create([
            'user_id' => $this->user->id, 'total_price' => 150000, 'status' => 'pending',
            'payment_method' => 'cod', 'payment_status' => 'pending', 'shipping_address' => 'HN',
            'customer_phone' => '0900000000', 'shipping_status' => 'not_shipped',
        ]);
        $item = OrderItem::create(['order_id' => $order->id, 'product_id' => $copy->id, 'quantity' => 1, 'price' => 150000]);

        $merger = new class extends ProductMerger {
            public function runWith(array $map): void
            {
                // dùng phương thức công khai qua phản chiếu để kiểm thử bản đồ riêng
                $m = new \ReflectionMethod(ProductMerger::class, 'makeVariantsFor');
                $m->setAccessible(true);
                $main = \App\Models\Product::first();
                $m->invoke($this, $main, 'Loại thường', [$map['label'] => \App\Models\Product::where('name', $map['old'])->first()]);
            }
        };
        $main->variants()->delete();
        $main->update(['price' => 100000, 'quantity' => 4]);
        $merger->runWith(['label' => 'Loại cao cấp', 'old' => 'Bản trùng thử nghiệm']);

        $main->refresh()->load('variants');
        $this->assertCount(2, $main->variants);
        $this->assertSame(11, $main->quantity); // 4 + 7
        $this->assertSame(50000, $main->variants->last()->price_adjust);
        $this->assertNull(Product::where('name', 'Bản trùng thử nghiệm')->first());

        $item->refresh();
        $this->assertSame($main->id, $item->product_id);
        $this->assertSame('Loại cao cấp', $item->variant_name);

        // Chạy bộ gộp thật lần nữa: không lỗi, không đổi số liệu
        $before = ProductVariant::count();
        (new ProductMerger())->run();
        $this->assertSame($before, ProductVariant::count());
    }

    public function test_dat_hang_cod_tru_kho_dung_phan_loai_va_luu_ten_phan_loai(): void
    {
        config(['toyland.shipping_carrier' => 'simulated']);
        $this->actingAs($this->user)->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->postJson(route('cart.add', $this->product->id), ['quantity' => 2, 'variant_id' => $this->blue->id])->assertOk();

        $this->post(route('checkout.process'), [
            'new_name' => 'Khách Test', 'new_phone' => '0900000000', 'new_address' => '1 Phố Test',
            'payment_method' => 'cod', 'to_district_id' => 1442, 'to_ward_code' => '20308', 'ghn_total_fee' => 30000,
        ]);

        $item = OrderItem::where('product_id', $this->product->id)->where('variant_id', $this->blue->id)->latest('id')->first();
        $this->assertNotNull($item, 'Đơn phải được tạo, dòng hàng gắn đúng phân loại');
        $this->assertSame('Xanh', $item->variant_name);
        $this->assertSame(120000, (int) $item->price);
        $this->assertSame(3, $this->blue->fresh()->quantity);
        $this->assertSame(10, $this->red->fresh()->quantity);
        $this->assertSame(13, $this->product->fresh()->quantity);
    }
}
