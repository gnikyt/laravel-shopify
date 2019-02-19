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

            // Linking to charge_id
            $table->bigInteger('reference_charge')->nullable();
        });

        Schema::table('charges', function (Blueprint $table) {
            // Linking to charge_id, seperate schema block due to contraint issue
            $table->foreign('reference_charge')->references('charge_id')->on('charges')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('charges', function (Blueprint $table) {
            if(DB::getDriverName() != 'sqlite') {
                $table->dropForeign('charges_reference_charge_foreign');
            }
            $table->dropColumn(['description', 'reference_charge']);
        });
    }
}
