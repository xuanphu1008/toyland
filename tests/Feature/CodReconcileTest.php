<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Đối soát COD: admin KHÔNG được tự chuyển "Đã thanh toán" sang "Chờ hoàn tiền".
 */
class CodReconcileTest extends TestCase
{
    use DatabaseTransactions;

    private function codOrder(string $orderStatus, string $payStatus, string $txStatus): array
    {
        $user = User::factory()->create(['role' => 'user']);
        $o = Order::create([
            'user_id' => $user->id, 'total_price' => 200000, 'status' => $orderStatus,
            'payment_method' => 'cod', 'payment_status' => $payStatus, 'shipping_address' => 'HN',
            'customer_phone' => '0900000000', 'shipping_status' => 'delivered',
        ]);
        OrderItem::create(['order_id' => $o->id, 'product_id' => Product::first()->id, 'quantity' => 1, 'price' => 200000]);
        $tx = PaymentTransaction::create(['order_id' => $o->id, 'gateway' => 'cod', 'amount' => 200000, 'status' => $txStatus]);

        return [$o, $tx];
    }

    private function update(Order $o, PaymentTransaction $tx, string $to)
    {
        $admin = User::where('role', 'admin')->first();

        return $this->actingAs($admin)->patch(route('admin.finance.update-status', $o->id), [
            'payment_status' => $to,
            'current_payment_status' => $o->payment_status,
            'current_order_status' => $o->status,
            'current_payment_id' => $tx->id,
        ]);
    }

    public function test_da_thanh_toan_khong_the_chuyen_tay_sang_cho_hoan_tien(): void
    {
        [$o, $tx] = $this->codOrder('completed', 'paid', 'paid');
        $this->update($o, $tx, 'refund_pending')->assertSessionHasErrors('payment_status');
        $this->assertSame('paid', $o->fresh()->payment_status);
        $this->assertSame('paid', $tx->fresh()->status);
    }

    public function test_cho_hoan_tien_va_da_hoan_khong_chon_tay_duoc(): void
    {
        [$o, $tx] = $this->codOrder('returned', 'refund_pending', 'refund_pending');
        $this->update($o, $tx, 'refunded')->assertSessionHasErrors('payment_status');
        $this->assertSame('refund_pending', $o->fresh()->payment_status);
    }

    public function test_doi_soat_khop_chuyen_da_thu_ho_sang_da_thanh_toan_va_ghi_nhat_ky(): void
    {
        Mail::fake();
        [$o, $tx] = $this->codOrder('completed', 'cod_collected', 'cod_collected');
        $this->update($o, $tx, 'paid')->assertSessionHasNoErrors();
        $this->assertSame('paid', $o->fresh()->payment_status);
        $this->assertSame('paid', $tx->fresh()->status);
        $this->assertDatabaseHas('order_status_logs', ['order_id' => $o->id]);
        Mail::assertNothingSent(); // đối soát nội bộ, không làm phiền khách
    }

    public function test_doi_soat_khi_don_chua_giao_xong_bi_chan(): void
    {
        [$o, $tx] = $this->codOrder('shipping', 'cod_collected', 'cod_collected');
        $this->update($o, $tx, 'paid')->assertSessionHasErrors('payment_status');
    }

    public function test_don_cho_giao_khong_co_buoc_chuyen_tay(): void
    {
        [$o, $tx] = $this->codOrder('shipping', 'pending', 'pending');
        $this->update($o, $tx, 'paid')->assertSessionHasErrors('payment_status');
    }
}
