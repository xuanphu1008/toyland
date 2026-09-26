<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCouponsTables extends Migration
{
    public function up()
    {
        // Mã giảm giá / voucher
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();          // VD: TOYLAND10
            $table->string('description')->nullable();

            $table->string('type')->default('percent'); // percent | fixed
            $table->integer('value')->default(0);       // % nếu percent, số tiền nếu fixed

            $table->integer('min_order_amount')->default(0); // giá trị đơn tối thiểu
            $table->integer('max_discount')->nullable();     // trần giảm (chỉ áp dụng cho percent)

            $table->integer('usage_limit')->nullable();      // tổng lượt dùng, null = không giới hạn
            $table->integer('usage_limit_per_user')->default(1);
            $table->integer('used_count')->default(0);

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->index(['is_active', 'expires_at']);
        });

        // Nhật ký mỗi lần 1 mã được dùng - để kiểm tra giới hạn theo user và đối soát
        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained('coupons')->cascadeOnDelete();

            // Dùng khóa ngoại "no action" để tránh lỗi multiple cascade paths của SQL Server
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('order_id')->nullable();

            $table->integer('discount_amount')->default(0);
            $table->timestamps();

            $table->index(['coupon_id', 'user_id']);
        });

        // Lưu mã đã áp dụng và số tiền được giảm lên chính đơn hàng
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('coupon_id')->nullable()->after('ghn_total_fee');
            $table->integer('discount_amount')->default(0)->after('coupon_id');
            $table->integer('points_earned')->default(0)->after('discount_amount');
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['coupon_id', 'discount_amount', 'points_earned']);
        });

        Schema::dropIfExists('coupon_usages');
        Schema::dropIfExists('coupons');
    }
}
