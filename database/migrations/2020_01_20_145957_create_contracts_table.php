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
                ->onDelete('cascade')->onUpdate('cascade');
            $table->bigInteger('program_id')->unsigned();
            $table->foreign('program_id')->references('id')->on('programs')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->smallInteger('type', false, true);
            $table->longText('options')->nullable();
            $table->tinyInteger('status', false, true)->index();
            $table->dateTime('active_from');
            $table->dateTime('active_to');
            $table->dateTime('signed_at');
            $table->float('remainingDebt', 14, 2);
            $table->float('premium', 14, 2)->nullable();
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
