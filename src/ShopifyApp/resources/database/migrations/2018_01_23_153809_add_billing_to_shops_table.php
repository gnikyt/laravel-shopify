<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBillingToShopsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->bigInteger('charge_id')->nullable(true)->default(null);
            $table->boolean('grandfathered')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shops', function (Blueprint $table) {
            // Laravel doesn't seem to support multiple dropColumn commands
            // See: (https://github.com/laravel/framework/issues/2979#issuecomment-227468621)
            $table->dropColumn(['charge_id', 'grandfathered']);
        });
    }
}
