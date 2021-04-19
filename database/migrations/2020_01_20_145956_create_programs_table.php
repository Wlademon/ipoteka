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
            $table->string('program_code',64);
            $table->string('program_name',255);
            $table->string('description', 255)->nullable();
            $table->longText('risks')->nullable();
            $table->longText('issues')->nullable();
            $table->longText('conditions')->nullable();
            $table->boolean('is_property')->index();
            $table->boolean('is_life')->index();
            $table->boolean('is_title')->index();
            $table->boolean('is_active')->default(false)->index();
            $table->boolean('is_recommended')->default(false)->index();
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
