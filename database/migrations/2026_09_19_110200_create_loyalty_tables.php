<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoyaltyTables extends Migration
{
    public function up()
    {
        // Điểm tích lũy & tổng chi tiêu để xếp hạng thành viên
        Schema::table('users', function (Blueprint $table) {
            $table->integer('points')->default(0)->after('role');
            $table->integer('lifetime_spent')->default(0)->after('points');
        });

        // Nhật ký cộng/trừ điểm - minh bạch cho khách và dễ đối soát
        Schema::create('point_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('order_id')->nullable();

            $table->integer('points');              // dương = cộng, âm = trừ
            $table->string('reason');
            $table->timestamps();

            $table->index(['user_id', 'id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('point_transactions');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['points', 'lifetime_spent']);
        });
    }
}
