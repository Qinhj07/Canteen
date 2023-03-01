<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChargeLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('charge_logs', function (Blueprint $table) {
            $table->id();
            $table->string('openid', 63)->comment('openid');
            $table->string('phone', 15)->comment('号码');
            $table->string('order_id', 32)->comment('oid')->unique();
            $table->integer('type')->default(1)->comment("1充值2余额调整3其他");
            $table->decimal('amount')->default(0.00);
            $table->decimal('real_pay')->default(0.00);
            $table->integer('status')->default(0)->comment('0待支付1已支付8支付异常9已退款');
            $table->decimal('check_money')->default(0.00);
            $table->timestamp('check_at')->nullable();
            $table->string('comment')->nullable();
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
        Schema::dropIfExists('charge_logs');
    }
}
