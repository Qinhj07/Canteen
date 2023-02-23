<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMunusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price')->default(0.00);
            $table->decimal('packing_price')->default(0.00);
            $table->string('introduce')->nullable();
            $table->string('image')->nullable();
            $table->integer('package')->default(0)->comment('是否打包0');
            $table->integer('type')->default(1)->comment('1主食2');
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
        Schema::dropIfExists('menus');
    }
}
