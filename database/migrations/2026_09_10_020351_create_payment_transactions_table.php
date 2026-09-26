<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();

            // 1 đơn hàng có thể có nhiều lần giao dịch (thanh toán lại khi lần trước thất bại)
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();

            // Cổng thanh toán: cod | momo (sau này có thể mở rộng vnpay, zalopay...)
            $table->string('gateway')->default('momo');

            // Mã đơn gửi sang cổng thanh toán. MoMo bắt buộc DUY NHẤT trên mỗi partner,
            // nên mỗi lần bấm "Thanh toán lại" sẽ sinh 1 mã mới -> không trùng với lần trước.
            $table->string('gateway_order_id')->nullable();

            // Mã giao dịch do cổng thanh toán trả về (MoMo: transId)
            $table->string('transaction_id')->nullable();

            $table->decimal('amount', 15, 2)->default(0);

            // pending | paid | failed
            $table->string('status')->default('pending');

            // resultCode & message MoMo trả về (0 = thành công)
            $table->string('result_code')->nullable();
            $table->string('message')->nullable();

            // Toàn bộ payload gửi đi / nhận về, phục vụ đối soát và debug
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();

            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

            // Tra cứu giao dịch từ callback/IPN của MoMo (theo orderId) và lọc theo trạng thái
            $table->index('gateway_order_id');
            $table->index('transaction_id');
            $table->index(['gateway', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payment_transactions');
    }
}
