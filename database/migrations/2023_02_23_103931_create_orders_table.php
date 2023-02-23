<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('oid', 31)->comment('订单号');
            $table->string('code', 31)->comment('用餐码')->unique();
            $table->string('openid', 31)->comment('openid');
            $table->string('phone', 15)->comment('下单号码');
            $table->string('receipt_id', 31)->comment('餐码, 关联餐品信息');
            $table->decimal('price', 10, 2)->comment('订单价格');
            $table->decimal('real_price', 10, 2)->comment('实际总价');
            $table->integer('status')->comment('1未使用0已使用8出售中9已售出')->default(1);
            $table->timestamp('use_at')->comment('使用时间')->nullable();
            $table->json('items')->comment('餐品信息');
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
        Schema::dropIfExists('orders');
    }
}
