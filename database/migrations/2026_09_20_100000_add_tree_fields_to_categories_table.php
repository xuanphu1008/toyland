<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTreeFieldsToCategoriesTable extends Migration
{
    public function up()
    {
        Schema::table('categories', function (Blueprint $table) {
            // Danh mục cha. CỐ Ý KHÔNG đặt khóa ngoại tự trỏ (self-referencing FK):
            // SQL Server không cho phép ON DELETE CASCADE/SET NULL trên khóa ngoại tự trỏ
            // (lỗi "may cause cycles or multiple cascade paths"). Ràng buộc được xử lý ở
            // tầng ứng dụng: xóa danh mục cha sẽ gỡ parent_id của các con.
            if (! Schema::hasColumn('categories', 'parent_id')) {
                $table->unsignedBigInteger('parent_id')->nullable()->index()->after('id');
            }

            if (! Schema::hasColumn('categories', 'slug')) {
                $table->string('slug')->nullable()->after('name');
            }

            // Emoji hiển thị trên menu & thẻ danh mục cho sinh động
            if (! Schema::hasColumn('categories', 'icon')) {
                $table->string('icon', 16)->nullable()->after('slug');
            }

            if (! Schema::hasColumn('categories', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('description');
            }
        });
    }

    public function down()
    {
        Schema::table('categories', function (Blueprint $table) {
            // SQL Server không cho xóa cột còn dính index -> gỡ index trước
            if (Schema::hasColumn('categories', 'parent_id')) {
                $table->dropIndex('categories_parent_id_index');
            }

            foreach (['parent_id', 'slug', 'icon', 'sort_order'] as $column) {
                if (Schema::hasColumn('categories', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
