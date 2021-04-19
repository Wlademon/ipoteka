<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateProgramsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('programs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('company_id')->unsigned();
            $table->foreign('company_id')->references('id')->on('companies')
                ->onDelete('cascade');
            $table->string('program_code');
            $table->string('program_name');
            $table->string('description', 255)->nullable();
            $table->json('risks')->nullable();
            $table->json('issues')->nullable();
            $table->json('conditions')->nullable();
            $table->float('insured_sum', 12, 2);
            $table->boolean('is_child')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
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
        Schema::dropIfExists('programs');
    }
}
