<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReceiptsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique();
            $table->json('menus')->comment('餐品内容数组');
            $table->integer('meal_type')->default(1)->comment('1早餐2午餐3晚餐4其他');
            $table->integer('status')->default(1)->comment('1可用2不可用');
            $table->date('used_at')->comment('使用日期');
            $table->time('start_at')->nullable()->comment('开始用餐时间');
            $table->time('end_at')->nullable()->comment('截止用餐时间');
            $table->timestamp('book_limited_at')->comment('预定截至日期')->nullable();
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
        Schema::dropIfExists('receipts');
    }
}
