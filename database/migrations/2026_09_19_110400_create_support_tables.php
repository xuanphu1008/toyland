<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSupportTables extends Migration
{
    public function up()
    {
        // Câu hỏi thường gặp
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('category')->default('Chung'); // Đặt hàng, Thanh toán, Vận chuyển, Đổi trả...
            $table->string('question');
            $table->text('answer');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        // Phiếu yêu cầu hỗ trợ (ticket)
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('order_id')->nullable();

            $table->string('code')->unique();               // Mã ticket hiển thị cho khách, vd TK-000012
            $table->string('subject');
            $table->string('category')->default('other');   // order | payment | shipping | product | other
            $table->string('priority')->default('normal');  // low | normal | high
            $table->string('status')->default('open');      // open | processing | answered | closed

            $table->text('content');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'id']);
        });

        // Các lượt trả lời qua lại trong 1 ticket
        Schema::create('ticket_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->boolean('is_staff')->default(false);
            $table->text('content');
            $table->timestamps();

            $table->index(['ticket_id', 'id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('ticket_replies');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('faqs');
    }
}
