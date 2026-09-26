<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentOptionToPaymentTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            // Phương thức thanh toán khách đã chọn ở cổng MoMo (chính là requestType gửi sang MoMo):
            // captureWallet | payWithATM | payWithCC | payWithMethod.
            // Null với giao dịch COD và các giao dịch MoMo tạo trước khi có tính năng chọn phương thức.
            $table->string('payment_option')->nullable()->after('gateway');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropColumn('payment_option');
        });
    }
}
