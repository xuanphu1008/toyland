<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            // Khóa ngoại liên kết với bảng categories
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');

            $table->string('name');
            $table->string('brand')->nullable();      // Thương hiệu: LEGO, Bandai, Fisher-Price...
            $table->string('age_range')->nullable();  // Độ tuổi phù hợp: "3-5 tuổi", "6+", "12+"
            $table->string('material')->nullable();   // Chất liệu: nhựa ABS, gỗ tự nhiên, vải bông...
            $table->string('origin')->nullable();     // Xuất xứ: Đan Mạch, Nhật Bản, Việt Nam...
            $table->string('gender')->nullable();     // Giới tính phù hợp: boy | girl | unisex
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->integer('price')->default(0);
            $table->integer('quantity')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
}
