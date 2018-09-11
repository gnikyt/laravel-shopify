<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUsageChargeSupportToChargesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('charges', function (Blueprint $table) {
            // Description support
            $table->string('description')->nullable();

            // Linking
            $table->integer('reference_charge')->unsigned()->nullable();
            $table->foreign('reference_charge')->references('charge_id')->on('charges')->onDelete('cascade');
        });
    }
}
