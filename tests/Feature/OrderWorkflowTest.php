<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\User;
use App\Services\Orders\OrderWorkflow;
use App\Services\Orders\WorkflowException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Kiểm thử nghiệp vụ luồng đơn hàng trên CSDL thật (mỗi ca được hoàn tác).
 * Chạy: php artisan test --filter=OrderWorkflowTest
 */
class OrderWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    private OrderWorkflow $wf;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        config(['toyland.shipping_carrier' => 'simulated']);
        $this->app->forgetInstance(\App\Services\Shipping\ShippingCarrier::class);
        $this->wf = app(OrderWorkflow::class);
    }

    private function makeOrder(string $method = 'cod', int $qty = 2): Order
    {
        $user = User::where('role', 'user')->first() ?? User::factory()->create(['role' => 'user']);
        $product = Product::first();
        $product->update(['quantity' => 50]);
        $product->decrement('quantity', $qty); // checkout trừ kho ngay

        $momo = $method === 'momo';
        $order = Order::create([
            'user_id' => $user->id, 'total_price' => 200000, 'status' => $momo ? 'awaiting_payment' : 'pending',
            'payment_method' => $method, 'payment_status' => 'pending', 'shipping_address' => 'Hà Nội',
            'customer_phone' => '0900000000', 'shipping_status' => 'not_shipped',
            'payment_expires_at' => $momo ? now()->addMinutes(30) : null,
        ]);
        OrderItem::create(['order_id' => $order->id, 'product_id' => $product->id, 'quantity' => $qty, 'price' => 100000]);
        PaymentTransaction::create(['order_id' => $order->id, 'gateway' => $method, 'amount' => 200000, 'status' => 'pending']);

        return $order;
    }

    private function stock(Order $o): int
    {
        return (int) Product::find($o->items()->first()->product_id)->quantity;
    }

    private function toPacked(Order $o): Order
    {
        $this->wf->apply($o, 'confirm', 'admin');

        return $this->wf->apply($o, 'pack', 'admin');
    }

    public function test_cod_giao_thanh_cong_thu_ho_va_cong_diem(): void
    {
        $o = $this->makeOrder('cod');
        $o = $this->toPacked($o);
        $this->assertMatchesRegularExpression('/^TL\d{6}\d{6}$/', $o->ghn_order_code);
        $this->assertSame('simulated', $o->carrier);

        foreach (['pick', 'out_for_delivery', 'deliver'] as $a) {
            $o = $this->wf->apply($o, $a, 'carrier');
        }
        $this->assertSame('completed', $o->status);
        $this->assertSame('paid', $o->payment_status);
        $this->assertGreaterThan(0, $o->points_earned);
        $this->assertSame('paid', PaymentTransaction::where('order_id', $o->id)->first()->status);
    }

    public function test_giao_that_bai_3_lan_roi_hoan_tra_kho(): void
    {
        $o = $this->toPacked($this->makeOrder('cod'));
        $before = $this->stock($o);
        $this->wf->apply($o, 'pick', 'carrier');

        for ($i = 1; $i <= 3; $i++) {
            $this->wf->apply($o, 'out_for_delivery', 'carrier');
            $o = $this->wf->apply($o, 'fail_delivery', 'carrier', ['note' => 'Không nghe máy']);
            $this->assertSame($i, (int) $o->delivery_attempts);
        }

        try {
            $this->wf->apply($o, 'out_for_delivery', 'carrier');
            $this->fail('Phải chặn giao lần thứ 4');
        } catch (WorkflowException $e) {
            $this->assertStringContainsString('chuyển hoàn', $e->getMessage());
        }

        $this->wf->apply($o, 'start_return', 'carrier', ['note' => 'Khách từ chối']);
        $o = $this->wf->apply($o, 'confirm_returned', 'admin');

        $this->assertSame('returned', $o->status);
        $this->assertSame($before + 2, $this->stock($o));
        $this->assertSame('cancelled', $o->payment_status);
    }

    public function test_momo_tra_tien_roi_huy_thi_cho_hoan_tien(): void
    {
        $o = $this->makeOrder('momo');
        $o->update(['payment_status' => 'paid']);
        PaymentTransaction::where('order_id', $o->id)->update(['status' => 'paid']);
        $this->wf->apply($o, 'mark_paid', 'system');

        $o = $this->wf->apply($o, 'cancel', 'admin', ['note' => 'Hết hàng']);
        $this->assertSame('cancelled', $o->status);
        $this->assertSame('refund_pending', $o->payment_status);
    }

    public function test_momo_qua_han_tu_huy_va_tra_kho_va_tien_ve_muon_khong_hoi_sinh(): void
    {
        $o = $this->makeOrder('momo');
        $before = $this->stock($o);

        try { // chưa hết hạn thì không được hủy
            $this->wf->apply($o, 'expire', 'system');
            $this->fail();
        } catch (WorkflowException $e) {
            $this->assertStringContainsString('còn trong thời gian', $e->getMessage());
        }

        $o->update(['payment_expires_at' => now()->subMinute()]);
        $this->assertSame(1, $this->wf->releaseExpired(true));
        $o->refresh();
        $this->assertSame('cancelled', $o->status);
        $this->assertSame($before + 2, $this->stock($o));

        $this->wf->paymentReceived($o);
        $o->refresh();
        $this->assertSame('cancelled', $o->status);
        $this->assertSame('refund_pending', $o->payment_status);
    }

    public function test_khach_huy_va_phan_quyen(): void
    {
        $o = $this->makeOrder('cod');
        $uid = $o->user_id;

        $this->expectException(WorkflowException::class);
        try {
            $this->wf->apply($o, 'pick', 'admin'); // shop không được làm việc của shipper
        } catch (WorkflowException $e) {
            $this->assertStringContainsString('không được phép', $e->getMessage());
        }

        try {
            $this->wf->apply($o, 'cancel', 'customer', ['note' => 'x', 'user_id' => $uid + 999]);
        } catch (WorkflowException $e) {
            $this->assertStringContainsString('không có quyền', $e->getMessage());
        }

        $before = $this->stock($o);
        $o = $this->wf->apply($o, 'cancel', 'customer', ['note' => 'Đổi ý', 'user_id' => $uid]);
        $this->assertSame('cancelled', $o->status);
        $this->assertSame($before + 2, $this->stock($o));

        $this->wf->apply($o, 'cancel', 'admin', ['note' => 'lần 2']); // đã hủy -> lỗi
    }

    public function test_khong_nhay_coc_va_huy_phai_co_ly_do(): void
    {
        $o = $this->makeOrder('cod');
        foreach ([['pack', 'admin'], ['deliver', 'carrier']] as [$a, $r]) {
            try {
                $this->wf->apply($o, $a, $r);
                $this->fail("$a không được chạy khi đơn mới");
            } catch (WorkflowException $e) {
                $this->assertTrue(true);
            }
        }
        $this->expectException(WorkflowException::class);
        $this->wf->apply($o, 'cancel', 'admin'); // thiếu lý do
    }

    public function test_hang_loat_don_sai_buoc_bi_bo_qua_va_khong_tra_kho_hai_lan(): void
    {
        $a = $this->makeOrder('cod');
        $b = $this->makeOrder('cod');
        $this->wf->apply($b, 'confirm', 'admin');

        $r = $this->wf->bulk([$a->id, $b->id, $b->id, 999999], 'confirm', 'admin');
        $this->assertSame([$a->id], $r['ok']);
        $this->assertArrayHasKey($b->id, $r['failed']);
        $this->assertArrayHasKey(999999, $r['failed']);

        $c = $this->makeOrder('cod');
        $stock = $this->stock($c);
        $this->wf->bulk([$c->id, $c->id], 'cancel', 'admin', ['note' => 'Test']);
        $this->assertSame($stock + 2, $this->stock($c)); // chỉ trả một lần
    }

    public function test_available_chi_hien_nut_hop_le(): void
    {
        $o = $this->toPacked($this->makeOrder('cod'));
        $this->assertSame(['pick'], array_keys($this->wf->available($o->fresh(), 'carrier')));
        $this->assertArrayHasKey('cancel', $this->wf->available($o->fresh(), 'admin'));
    }
}
