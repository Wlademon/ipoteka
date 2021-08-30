<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUwContractIdToContractsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->bigInteger('uw_contract_id')->nullable()->unsigned();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn('uw_contract_id');
        });
    }
}
