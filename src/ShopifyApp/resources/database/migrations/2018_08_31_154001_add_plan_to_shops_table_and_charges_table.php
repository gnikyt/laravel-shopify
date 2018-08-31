<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('charges', function (Blueprint $table) {
            // Linking
            $table->integer('plan_id')->unsigned();
            $table->foreign('plan_id')->references('id')->on('plans');
        });

        Schema::create('shops', function (Blueprint $table) {
            // Linking
            $table->integer('plan_id')->unsigned();
            $table->foreign('plan_id')->references('id')->on('plans');
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
            $table->dropForeign(['plan_id']);
        });

        Schema::table('shops', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
        });
    }
}
