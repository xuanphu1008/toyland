<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ví ToyLand + yêu cầu hoàn tiền.
 *
 *  - users.wallet_balance: số dư hiện tại (đồng, số nguyên).
 *  - wallet_transactions: SỔ CÁI chỉ ghi thêm. Mỗi lần số dư đổi là một dòng, kèm
 *    số dư sau khi đổi, nên số dư luôn đối chiếu lại được bằng tổng sổ cái.
 *  - refund_requests: mọi trường hợp hoàn tiền (hủy đơn, MoMo không nhận được,
 *    thanh toán trùng, trả hàng sau khi nhận) đều đi qua một bảng này.
 */
class CreateWalletAndRefundTables extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('wallet_balance')->default(0);
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            // Không cascade: xóa tài khoản không được xóa sổ cái tiền
            $table->foreignId('user_id')->constrained('users');
            $table->string('type', 30);               // refund_in | order_payment | order_cancel_return | adjust
            $table->bigInteger('amount');              // dương = cộng ví, âm = trừ ví
            $table->unsignedBigInteger('balance_after');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('refund_request_id')->nullable();
            $table->string('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'id']);
            $table->index('order_id');
        });

        Schema::create('refund_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders');
            $table->foreignId('user_id')->constrained('users');
            $table->unsignedBigInteger('payment_transaction_id')->nullable();

            // order_cancelled | payment_not_received | duplicate_payment | return_goods
            $table->string('type', 30);
            $table->string('source', 12)->default('customer');   // system | customer
            $table->string('reason_code', 30)->nullable();       // lý do trả hàng
            $table->text('reason')->nullable();
            $table->string('momo_trans_id', 60)->nullable();     // mã giao dịch MoMo do khách cung cấp
            $table->unsignedBigInteger('amount');
            $table->string('destination', 12)->default('wallet');

            // requested | approved | in_transit | received | refunded | rejected | cancelled
            $table->string('status', 20)->default('requested');

            // --- Riêng luồng trả hàng ---
            $table->boolean('restock')->nullable();              // hàng nhận về còn bán được không
            $table->string('return_carrier', 60)->nullable();
            $table->string('return_tracking_code', 60)->nullable();
            $table->timestamp('ship_back_deadline')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('received_at')->nullable();

            $table->unsignedBigInteger('handled_by')->nullable();
            $table->timestamp('handled_at')->nullable();
            $table->text('admin_note')->nullable();
            $table->unsignedBigInteger('wallet_transaction_id')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index(['order_id', 'type']);
            $table->index('payment_transaction_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('refund_requests');
        Schema::dropIfExists('wallet_transactions');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('wallet_balance');
        });
    }
}
