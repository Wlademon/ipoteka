<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('contract_id', false, true)->comment('Id контракта в таблице contracts');
            $table->uuid('order_id')->comment('order_id для оплаты uuid');
            $table->string('invoice_num', 20)->comment('invoice_num для оплаты');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('contract_id')->references('id')->on('contracts')->onDelete('cascade')->onUpdate('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payments');
    }
}
