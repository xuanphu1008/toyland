<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderStatusLogsTable extends Migration
{
    /**
     * Nhật ký thay đổi trạng thái đơn hàng - phục vụ yêu cầu
     * "Theo dõi trạng thái đơn hàng từ khi đặt đến khi giao thành công".
     */
    public function up()
    {
        Schema::create('order_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();

            // Người thực hiện (admin hoặc chính khách hàng khi tự hủy). Null = hệ thống tự động.
            $table->unsignedBigInteger('changed_by')->nullable();

            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->string('note')->nullable();

            $table->timestamps();

            $table->index(['order_id', 'id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_status_logs');
    }
}
