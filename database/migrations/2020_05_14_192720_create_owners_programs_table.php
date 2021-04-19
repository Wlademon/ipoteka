<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOwnersProgramsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('owners_programs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('programs_id')->nullable()->unsigned();
            $table->foreign('programs_id')->references('id')->on('programs')
                  ->onUpdate('no action')
                  ->onDelete('set null');
            $table->bigInteger('owners_id')->nullable()->unsigned();
            $table->foreign('owners_id')->references('id')->on('owners')
                  ->onUpdate('no action')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('owners_programs');
    }
}
