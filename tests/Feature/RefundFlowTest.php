<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\RefundRequest;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\InsufficientWalletBalance;
use App\Services\MomoService;
use App\Services\Orders\OrderWorkflow;
use App\Services\Orders\WorkflowException;
use App\Services\RefundException;
use App\Services\RefundService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Hoàn tiền, ví, trả hàng. Chạy: php artisan test --filter=RefundFlowTest */
class RefundFlowTest extends TestCase
{
    use DatabaseTransactions;

    private OrderWorkflow $wf;
    private RefundService $refunds;
    private WalletService $wallet;
    private User $user;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        config(['toyland.shipping_carrier' => 'simulated']);
        $this->app->forgetInstance(\App\Services\Shipping\ShippingCarrier::class);
        $this->wf = app(OrderWorkflow::class);
        $this->refunds = app(RefundService::class);
        $this->wallet = app(WalletService::class);
        $this->user = User::factory()->create(['role' => 'user', 'wallet_balance' => 0, 'points' => 0, 'lifetime_spent' => 0]);
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    private function order(string $method = 'momo', string $status = 'awaiting_payment', string $pay = 'pending', int $total = 200000): Order
    {
        $product = Product::first();
        $product->update(['quantity' => 50]);
        $product->decrement('quantity', 2);

        $o = Order::create([
            'user_id' => $this->user->id, 'total_price' => $total, 'status' => $status, 'payment_method' => $method,
            'payment_status' => $pay, 'shipping_address' => 'Hà Nội', 'customer_phone' => '0900000000',
            'shipping_status' => 'not_shipped', 'ghn_total_fee' => 30000,
            'payment_expires_at' => $status === 'awaiting_payment' ? now()->addMinutes(30) : null,
        ]);
        OrderItem::create(['order_id' => $o->id, 'product_id' => $product->id, 'quantity' => 2, 'price' => 85000]);
        PaymentTransaction::create(['order_id' => $o->id, 'gateway' => $method, 'amount' => $total,
            'status' => $pay === 'paid' ? 'paid' : 'pending']);

        return $o;
    }

    private function stock(Order $o): int
    {
        return (int) Product::find($o->items()->first()->product_id)->quantity;
    }

    private function balance(): int
    {
        return (int) User::find($this->user->id)->wallet_balance;
    }

    /** Đơn đã giao thành công (COD đã thu hộ) để thử trả hàng */
    private function delivered(): Order
    {
        $o = $this->order('cod', 'pending');
        foreach ([['confirm', 'admin'], ['pack', 'admin'], ['pick', 'carrier'], ['out_for_delivery', 'carrier'], ['deliver', 'carrier']] as [$a, $r]) {
            $o = $this->wf->apply($o, $a, $r);
        }

        return $o;
    }

    // ----- Ví -----

    public function test_vi_so_cai_khop_so_du_va_khong_cho_am(): void
    {
        $this->wallet->credit($this->user->id, 100000, 'refund_in', ['note' => 'x']);
        $this->wallet->debit($this->user->id, 30000, 'order_payment', ['note' => 'y']);
        $this->assertSame(70000, $this->balance());
        $this->assertSame(70000, $this->wallet->ledgerSum($this->user->id));

        $this->expectException(InsufficientWalletBalance::class);
        $this->wallet->debit($this->user->id, 70001, 'order_payment');
    }

    // ----- Hủy đơn đã thanh toán -----

    public function test_huy_don_momo_da_tra_tien_mo_yeu_cau_va_hoan_vao_vi_dung_mot_lan(): void
    {
        $o = $this->order('momo', 'pending', 'paid');
        $o = $this->wf->apply($o, 'cancel', 'admin', ['note' => 'Hết hàng']);

        $this->assertSame('refund_pending', $o->payment_status);
        $r = RefundRequest::where('order_id', $o->id)->firstOrFail();
        $this->assertSame('order_cancelled', $r->type);
        $this->assertSame('requested', $r->status);
        $this->assertSame(200000, (int) $r->amount);

        $this->refunds->payout($r, $this->admin, null);
        $this->assertSame(200000, $this->balance());
        $this->assertSame('refunded', $o->fresh()->payment_status);
        $this->assertSame('refunded', PaymentTransaction::where('order_id', $o->id)->first()->status);

        $this->expectException(RefundException::class); // bấm lần hai không hoàn thêm
        $this->refunds->payout($r, $this->admin, null);
    }

    public function test_tien_momo_ve_muon_sau_khi_huy_mo_yeu_cau_hoan(): void
    {
        $o = $this->order('momo');
        $o->update(['payment_expires_at' => now()->subMinute()]);
        $this->wf->releaseExpired(true);

        PaymentTransaction::where('order_id', $o->id)->update(['status' => 'paid']);
        $this->wf->paymentReceived($o->fresh());

        $this->assertSame('refund_pending', $o->fresh()->payment_status);
        $this->assertSame(1, RefundRequest::where('order_id', $o->id)->where('status', 'requested')->count());
    }

    public function test_thanh_toan_trung_mo_yeu_cau_hoan_khoan_thu_hai_va_giu_don(): void
    {
        $o = $this->order('momo', 'pending', 'paid'); // đã có 1 khoản paid
        $dup = PaymentTransaction::create(['order_id' => $o->id, 'gateway' => 'momo', 'amount' => 200000, 'status' => 'initiated']);

        app(MomoService::class)->markPaid($dup, ['transId' => 'DUP1']);

        $r = RefundRequest::where('order_id', $o->id)->firstOrFail();
        $this->assertSame('duplicate_payment', $r->type);
        $this->refunds->payout($r, $this->admin, null);
        $this->assertSame(200000, $this->balance());
        $this->assertSame('paid', $o->fresh()->payment_status); // khoản gốc vẫn hợp lệ
    }

    // ----- Khách báo bị trừ tiền -----

    public function test_bao_tru_tien_don_dang_giu_hang_shop_xac_nhan_thi_don_di_tiep(): void
    {
        $o = $this->order('momo');
        $r = $this->refunds->requestPaymentNotReceived($this->user, $o, 'MM123456', 'Bị trừ 200k');

        try { // đơn còn giữ hàng: chưa được hoàn tiền
            $this->refunds->payout($r, $this->admin, null);
            $this->fail('Đơn còn giữ hàng thì không được hoàn');
        } catch (RefundException $e) {
            $this->assertStringContainsString('giữ hàng', $e->getMessage());
        }

        $this->refunds->resolveByConfirmingPayment($r, $this->admin);
        $o->refresh();
        $this->assertSame('pending', $o->status);
        $this->assertSame('paid', $o->payment_status);
        $this->assertSame(0, $this->balance());
    }

    public function test_bao_tru_tien_don_da_huy_shop_hoan_vao_vi_va_tien_ve_muon_khong_hoan_lan_hai(): void
    {
        $o = $this->order('momo');
        $r = $this->refunds->requestPaymentNotReceived($this->user, $o, 'MM999', null);
        $o->update(['payment_expires_at' => now()->subMinute()]);
        $this->wf->releaseExpired(true);

        $this->refunds->payout($r->fresh(), $this->admin, null);
        $this->assertSame(200000, $this->balance());
        $this->assertSame('refunded', $o->fresh()->payment_status);

        // Sau đó MoMo mới báo thành công: cùng khoản tiền đó, không hoàn lần hai
        PaymentTransaction::where('order_id', $o->id)->update(['status' => 'paid']);
        $this->wf->paymentReceived($o->fresh());
        $this->assertSame(200000, $this->balance());
    }

    public function test_bao_tru_tien_khong_hop_le(): void
    {
        $cod = $this->order('cod', 'pending');
        $this->expectException(RefundException::class);
        $this->refunds->requestPaymentNotReceived($this->user, $cod, 'X1', null);
    }

    // ----- Thanh toán bằng ví -----

    public function test_don_tra_bang_vi_bi_huy_thi_hoan_ngay_khong_can_shop_duyet(): void
    {
        $this->wallet->credit($this->user->id, 300000, 'refund_in');
        $o = $this->order('wallet', 'pending', 'paid');
        $this->wallet->debit($this->user->id, 200000, 'order_payment', ['order_id' => $o->id]);
        $this->assertSame(100000, $this->balance());

        $o = $this->wf->apply($o, 'cancel', 'customer', ['note' => 'Đổi ý', 'user_id' => $this->user->id]);

        $this->assertSame(300000, $this->balance());
        $this->assertSame('refunded', $o->payment_status);
        $this->assertSame('refunded', RefundRequest::where('order_id', $o->id)->value('status'));
        $this->assertSame('order_cancel_return', WalletTransaction::where('order_id', $o->id)->orderByDesc('id')->value('type'));
        $this->assertSame($this->balance(), $this->wallet->ledgerSum($this->user->id));
    }

    // ----- Trả hàng sau khi nhận -----

    public function test_tra_hang_loi_shop_hoan_du_thu_hoi_diem_cong_kho(): void
    {
        $o = $this->delivered();
        $this->assertGreaterThan(0, $o->points_earned);
        $pointsBefore = (int) User::find($this->user->id)->points;
        $stockBefore = $this->stock($o);

        $r = $this->refunds->requestReturn($this->user, $o, 'defective', 'Bánh xe bị gãy ngay khi mở hộp');
        $this->assertSame(200000, (int) $r->amount);          // lỗi shop: hoàn đủ
        $this->assertSame('completed', $o->fresh()->status);  // chưa gửi hàng thì đơn chưa đổi

        $r = $this->refunds->approveReturn($r, $this->admin, null);
        $this->assertNotNull($r->ship_back_deadline);
        $r = $this->refunds->markShipped($this->user, $r, 'GHN', 'GHN12345');
        $r = $this->refunds->markReceived($r, $this->admin);
        $this->assertSame(0, $this->balance()); // chưa kiểm hàng thì chưa hoàn tiền

        $r = $this->refunds->finishInspection($r, $this->admin, true, true, null);

        $o->refresh();
        $this->assertSame('returned', $o->status);
        $this->assertSame('refunded', $o->payment_status);
        $this->assertSame(200000, $this->balance());
        $this->assertSame($stockBefore + 2, $this->stock($o));
        $this->assertSame($pointsBefore - $o->points_earned, (int) User::find($this->user->id)->points);
        $this->assertSame(0, (int) User::find($this->user->id)->lifetime_spent);

        $this->expectException(RefundException::class);
        $this->refunds->finishInspection($r, $this->admin, true, true, null); // không hoàn hai lần
    }

    public function test_tra_hang_doi_y_tru_phi_ship_va_hang_hong_khong_nhap_kho(): void
    {
        $o = $this->delivered();
        $r = $this->refunds->requestReturn($this->user, $o, 'change_of_mind', 'Con không thích màu này nữa');
        $this->assertSame(170000, (int) $r->amount); // 200.000 - 30.000 phí ship chiều đi

        $stock = $this->stock($o);
        $r = $this->refunds->approveReturn($r, $this->admin, null);
        $r = $this->refunds->markShipped($this->user, $r, 'VNPost', 'VN1');
        $r = $this->refunds->markReceived($r, $this->admin);
        $this->refunds->finishInspection($r, $this->admin, true, false, 'Hộp móp, không bán lại được');

        $this->assertSame($stock, $this->stock($o)); // không nhập kho
        $this->assertSame(170000, $this->balance());
    }

    public function test_hang_tra_khong_dat_khong_hoan_tien_va_don_giu_nguyen(): void
    {
        $o = $this->delivered();
        $r = $this->refunds->requestReturn($this->user, $o, 'not_as_described', 'Màu khác hẳn trên hình ảnh');
        $r = $this->refunds->approveReturn($r, $this->admin, null);
        $r = $this->refunds->markShipped($this->user, $r, 'GHN', 'T1');
        $r = $this->refunds->markReceived($r, $this->admin);

        try {
            $this->refunds->finishInspection($r, $this->admin, false, false, '');
            $this->fail('Từ chối phải có lý do');
        } catch (RefundException $e) {
            $this->assertTrue(true);
        }

        $r = $this->refunds->finishInspection($r, $this->admin, false, false, 'Hàng đã qua sử dụng, thiếu phụ kiện');
        $this->assertSame('rejected', $r->status);
        $this->assertSame(0, $this->balance());
        $this->assertSame('completed', $o->fresh()->status);
    }

    public function test_dieu_kien_tra_hang(): void
    {
        // quá 7 ngày
        $o = $this->delivered();
        $o->update(['delivered_at' => now()->subDays(8)]);
        try {
            $this->refunds->requestReturn($this->user, $o->fresh(), 'defective', 'Hàng bị lỗi nặng rồi');
            $this->fail();
        } catch (RefundException $e) {
            $this->assertStringContainsString('quá', $e->getMessage());
        }

        // đơn chưa giao
        $p = $this->order('cod', 'pending');
        $this->assertFalse($this->refunds->canRequestReturn($p)[0]);

        // người khác không được trả hộ
        $other = User::factory()->create(['role' => 'user']);
        $o2 = $this->delivered();
        $this->expectException(RefundException::class);
        $this->refunds->requestReturn($other, $o2, 'defective', 'Hàng bị lỗi nặng rồi');
    }

    public function test_khong_yeu_cau_tra_hang_hai_lan_va_khach_huy_duoc_truoc_khi_gui(): void
    {
        $o = $this->delivered();
        $r = $this->refunds->requestReturn($this->user, $o, 'wrong_item', 'Giao nhầm sang mẫu khác hẳn');
        $this->assertFalse($this->refunds->canRequestReturn($o->fresh())[0]);

        $this->refunds->cancelByCustomer($this->user, $r);
        $this->assertSame('cancelled', $r->fresh()->status);
        $this->assertTrue($this->refunds->canRequestReturn($o->fresh())[0]); // hủy rồi xin lại được
    }

    public function test_khach_khong_gui_hang_dung_han_thi_yeu_cau_tu_huy(): void
    {
        $o = $this->delivered();
        $r = $this->refunds->requestReturn($this->user, $o, 'wrong_item', 'Giao nhầm sang mẫu khác hẳn');
        $r = $this->refunds->approveReturn($r, $this->admin, null);
        $r->update(['ship_back_deadline' => now()->subHour()]);

        try {
            $this->refunds->markShipped($this->user, $r->fresh(), 'GHN', 'X');
            $this->fail('Quá hạn không được báo gửi hàng');
        } catch (RefundException $e) {
            $this->assertTrue(true);
        }

        $this->assertSame(1, $this->refunds->expireStaleReturns());
        $this->assertSame('cancelled', $r->fresh()->status);
    }

    public function test_don_da_giao_khong_tu_chuyen_returned_ngoai_luong_tra_hang(): void
    {
        $o = $this->delivered();
        $this->expectException(WorkflowException::class);
        $this->wf->apply($o, 'return_accepted', 'admin');
    }

    public function test_khong_the_hoan_tien_bang_cach_di_tat(): void
    {
        $o = $this->delivered();
        $r = $this->refunds->requestReturn($this->user, $o, 'wrong_item', 'Giao nhầm sang mẫu khác hẳn');
        $this->expectException(RefundException::class);
        $this->refunds->payout($r, $this->admin, null); // trả hàng phải qua nhận + kiểm hàng
    }

    public function test_khoan_he_thong_tao_thi_shop_khong_tu_choi_duoc(): void
    {
        $o = $this->order('momo', 'pending', 'paid');
        $this->wf->apply($o, 'cancel', 'admin', ['note' => 'Hết hàng']);
        $r = RefundRequest::where('order_id', $o->id)->firstOrFail();
        $this->expectException(RefundException::class);
        $this->refunds->reject($r, $this->admin, 'Không hoàn');
    }
}
