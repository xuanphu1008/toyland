<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSocialFieldsToUsersTable extends Migration
{
    /**
     * Đăng nhập bằng tài khoản Google.
     * password để nullable vì tài khoản tạo qua Google không có mật khẩu.
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->after('email_verified_at');
            $table->string('avatar')->nullable()->after('google_id');

            $table->index('google_id');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['google_id']);
            $table->dropColumn(['google_id', 'avatar']);
        });
    }
}
