<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusFieldsToOrdersTable extends Migration
{
    /**
     * Bổ sung các cột phục vụ khâu Admin xử lý đơn hàng:
     * ghi chú nội bộ, thời điểm/lý do hủy và mốc đã hoàn kho.
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->text('admin_note')->nullable()->after('to_ward_code');
            $table->string('cancel_reason')->nullable()->after('admin_note');
            $table->timestamp('cancelled_at')->nullable()->after('cancel_reason');

            // Chống hoàn kho 2 lần cho cùng 1 đơn khi admin bấm hủy nhiều lần
            $table->timestamp('restocked_at')->nullable()->after('cancelled_at');
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['admin_note', 'cancel_reason', 'cancelled_at', 'restocked_at']);
        });
    }
}
