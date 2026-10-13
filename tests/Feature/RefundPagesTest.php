<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\RefundRequest;
use App\Models\User;
use App\Services\Orders\OrderWorkflow;
use App\Services\RefundService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Các trang ví / hoàn tiền / trả hàng hiển thị được và thao tác qua HTTP chạy đúng */
class RefundPagesTest extends TestCase
{
    use DatabaseTransactions;

    public function test_trang_vi_don_hang_va_admin_hoan_tien_hien_thi_va_thao_tac(): void
    {
        Mail::fake();
        config(['toyland.shipping_carrier' => 'simulated']);
        $this->app->forgetInstance(\App\Services\Shipping\ShippingCarrier::class);

        $user = User::factory()->create(['role' => 'user', 'wallet_balance' => 50000, 'email_verified_at' => now()]);
        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        $product = Product::first();
        $product->update(['quantity' => 50]);

        $o = Order::create([
            'user_id' => $user->id, 'total_price' => 120000, 'status' => 'pending', 'payment_method' => 'cod',
            'payment_status' => 'pending', 'shipping_address' => 'Hà Nội', 'customer_phone' => '0900000000',
            'shipping_status' => 'not_shipped', 'ghn_total_fee' => 20000,
        ]);
        OrderItem::create(['order_id' => $o->id, 'product_id' => $product->id, 'quantity' => 1, 'price' => 100000]);
        PaymentTransaction::create(['order_id' => $o->id, 'gateway' => 'cod', 'amount' => 120000, 'status' => 'pending']);
        $wf = app(OrderWorkflow::class);
        foreach ([['confirm', 'admin'], ['pack', 'admin'], ['pick', 'carrier'], ['out_for_delivery', 'carrier'], ['deliver', 'carrier']] as [$a, $r]) {
            $wf->apply($o, $a, $r);
        }

        // Khách: trang ví + trang đơn có khối trả hàng + gửi yêu cầu
        $this->actingAs($user)->get(route('wallet.index'))->assertOk()->assertSee('50.000');
        $this->actingAs($user)->get(route('orders.show', $o->id))->assertOk()->assertSee('Trả hàng');
        $this->actingAs($user)->post(route('refunds.return', $o->id), ['reason_code' => 'defective', 'description' => 'Nút bấm không hoạt động'])
            ->assertRedirect(route('orders.show', $o->id))->assertSessionHas('success');
        $r = RefundRequest::where('order_id', $o->id)->firstOrFail();
        $this->actingAs($user)->get(route('orders.show', $o->id))->assertOk()->assertSee('Chờ shop xử lý');

        // Admin: danh sách + chi tiết + duyệt
        $this->actingAs($admin)->get(route('admin.refunds.index', ['status' => 'open']))->assertOk()->assertSee('#' . $o->id);
        $this->actingAs($admin)->get(route('admin.refunds.show', $r->id))->assertOk()->assertSee('Duyệt cho trả hàng');
        $this->actingAs($admin)->post(route('admin.refunds.approve-return', $r->id))->assertSessionHas('success');

        // Khách nhập mã vận đơn trả
        $this->actingAs($user)->get(route('orders.show', $o->id))->assertOk()->assertSee('Tôi đã gửi hàng');
        $this->actingAs($user)->patch(route('refunds.ship', $r->id), ['carrier' => 'GHN', 'tracking' => 'ABC123'])->assertSessionHas('success');

        // Admin nhận hàng + chốt kiểm hàng đạt
        $this->actingAs($admin)->post(route('admin.refunds.receive', $r->id))->assertSessionHas('success');
        $this->actingAs($admin)->get(route('admin.refunds.show', $r->id))->assertOk()->assertSee('Chốt kết quả');
        $this->actingAs($admin)->post(route('admin.refunds.inspect', $r->id), ['result' => 'accept', 'restock' => '0'])->assertSessionHas('success');

        $this->assertSame(170000, (int) $user->fresh()->wallet_balance);
        $this->actingAs($user)->get(route('wallet.index'))->assertOk()->assertSee('170.000');
        $this->actingAs($user)->get(route('orders.show', $o->id))->assertOk()->assertSee('Đã hoàn');

        // Khách thường không vào được khu admin
        $this->actingAs($user)->get(route('admin.refunds.index'))->assertRedirect();
    }
}
