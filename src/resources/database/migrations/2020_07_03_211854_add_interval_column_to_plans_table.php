<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIntervalColumnToPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(config('shopify-app.table_names.plans', 'plans'), function (Blueprint $table) {
            $table->string('interval')->nullable()->after('price');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(config('shopify-app.table_names.plans', 'plans'), function (Blueprint $table) {
            $table->dropColumn('interval');
        });
    }
}
