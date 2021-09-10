<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Osiset\ShopifyApp\Util;

class AddIntervalColumnToChargesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(Util::getShopifyConfig('table_names.charges', 'charges'), function (Blueprint $table) {
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
        Schema::table(Util::getShopifyConfig('table_names.charges', 'charges'), function (Blueprint $table) {
            $table->dropColumn('interval');
        });
    }
}
