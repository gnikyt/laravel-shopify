<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Osiset\ShopifyApp\Util;

class CreatePlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Util::getShopifyConfig('table_names.plans', 'plans'), function (Blueprint $table) {
            $table->increments('id');

            // The type of plan, either PlanType::RECURRING (0) or PlanType::ONETIME (1)
            $table->string('type');

            // Name of the plan
            $table->string('name');

            // Price of the plan
            $table->decimal('price', 8, 2);

            // Store the amount of the charge, this helps if you are experimenting with pricing
            $table->decimal('capped_amount', 8, 2)->nullable();

            // Terms for the usage charges
            $table->string('terms')->nullable();

            // Nullable in case of 0 trial days
            $table->integer('trial_days')->nullable();

            // Is a test plan or not
            $table->boolean('test')->default(false);

            // On-install
            $table->boolean('on_install')->default(false);

            // Provides created_at && updated_at columns
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Util::getShopifyConfig('table_names.plans', 'plans'));
    }
}
