<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePayOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pay_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_id', 31)->comment('订单号')->unique();
            $table->string('openid', 63)->comment('openid');
            $table->string('phone', 15)->comment('phone');
            $table->decimal('price', 10, 2)->comment('原价');
            $table->decimal('real_pay', 10, 2)->comment('实际支付价格');
            $table->integer('pay_type')->comment('支付方式1微信2余额')->default(1);
            $table->integer('status')->comment('1待支付2支付完成9支付异常');
            $table->string('check_money')->comment('接口返回金额/分');
            $table->timestamp('check_at')->comment('接口返回支付时间');
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pay_orders');
    }
}
