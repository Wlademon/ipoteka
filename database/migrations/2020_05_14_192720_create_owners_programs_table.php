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
            $table->bigInteger('program_id')->nullable()->unsigned();
            $table->foreign('program_id')->references('id')->on('programs')
                  ->onUpdate('no action')
                  ->onDelete('set null');
            $table->bigInteger('owner_id')->nullable()->unsigned();
            $table->foreign('owner_id')->references('id')->on('owners')
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
