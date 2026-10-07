<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Các cột phục vụ luồng xử lý đơn hàng (App\Services\Orders\OrderWorkflow):
 *  - payment_expires_at : đơn MoMo được GIỮ HÀNG tới mốc này; quá hạn chưa trả tiền thì tự hủy, trả kho
 *  - carrier            : đơn vị vận chuyển tạo vận đơn (simulated = mô phỏng nội bộ, ghn = Giao Hàng Nhanh)
 *  - waybill_created_at : lúc tạo mã vận đơn (khi shop đóng gói xong)
 *  - picked_at / delivered_at / returned_at : các mốc do đơn vị vận chuyển báo về
 *  - delivery_attempts  : số lần đi giao; last_delivery_note: ghi chú lần giao gần nhất (vd lý do thất bại)
 */
class AddWorkflowFieldsToOrdersTable extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'payment_expires_at')) {
                $table->timestamp('payment_expires_at')->nullable()->after('payment_status');
            }
            if (! Schema::hasColumn('orders', 'carrier')) {
                $table->string('carrier', 30)->nullable()->after('ghn_order_code');
            }
            if (! Schema::hasColumn('orders', 'waybill_created_at')) {
                $table->timestamp('waybill_created_at')->nullable()->after('carrier');
            }
            if (! Schema::hasColumn('orders', 'picked_at')) {
                $table->timestamp('picked_at')->nullable()->after('waybill_created_at');
            }
            if (! Schema::hasColumn('orders', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable()->after('picked_at');
            }
            if (! Schema::hasColumn('orders', 'returned_at')) {
                $table->timestamp('returned_at')->nullable()->after('delivered_at');
            }
            if (! Schema::hasColumn('orders', 'delivery_attempts')) {
                $table->unsignedTinyInteger('delivery_attempts')->default(0)->after('returned_at');
            }
            if (! Schema::hasColumn('orders', 'last_delivery_note')) {
                $table->string('last_delivery_note')->nullable()->after('delivery_attempts');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            // Tìm đơn MoMo quá hạn giữ hàng mỗi lần có người đặt hàng -> cần index
            $table->index(['status', 'payment_expires_at'], 'orders_status_payment_expires_index');
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_status_payment_expires_index');
            $table->dropColumn([
                'payment_expires_at', 'carrier', 'waybill_created_at', 'picked_at',
                'delivered_at', 'returned_at', 'delivery_attempts', 'last_delivery_note',
            ]);
        });
    }
}
