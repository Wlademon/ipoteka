<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateContractsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('company_id')->unsigned();
            $table->foreign('company_id')->references('id')->on('companies')
                ->onDelete('cascade');
            $table->bigInteger('program_id')->unsigned();
            $table->foreign('program_id')->references('id')->on('programs')
                ->onDelete('cascade');
            $table->string('number');
            $table->bigInteger('uw_contract_id')->nullable()->unsigned();
            $table->smallInteger('type', false, true);
            $table->longText('options')->nullable();
            $table->tinyInteger('status', false, true)->index();
            $table->dateTime('active_from');
            $table->dateTime('active_to');
            $table->dateTime('signed_at');
            $table->integer('insured_sum', false, true);
            $table->integer('premium', false, true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contracts');
    }
}
