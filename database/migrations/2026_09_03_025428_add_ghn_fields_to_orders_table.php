<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGhnFieldsToOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Trạng thái vận chuyển GHN: not_shipped, processing, shipping, delivered, cancelled...
            $table->string('shipping_status')->default('not_shipped')->after('payment_status');
            $table->string('ghn_order_code')->nullable()->after('shipping_status');
            $table->integer('ghn_total_fee')->default(0)->after('ghn_order_code');
            $table->integer('to_district_id')->nullable()->after('customer_phone');
            $table->string('to_ward_code')->nullable()->after('to_district_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'shipping_status',
                'ghn_order_code',
                'ghn_total_fee',
                'to_district_id',
                'to_ward_code',
            ]);
        });
    }
}
