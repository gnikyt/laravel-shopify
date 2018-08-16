<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChargesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Thanks to @ncpope of Github.com
        Schema::create('charges', function (Blueprint $table) {
            $table->increments('id');

            // Filled in when the charge is created, provided by shopify, unique makes it indexed
            $table->bigInteger('charge_id')->unique();

            // Test mode or real
            $table->boolean('test');

            $table->string('status')->nullable();

            // Name of the charge (for recurring or one time charges)
            $table->string('name')->nullable();

            // Terms for the usage charges
            $table->string('terms')->nullable();

            // Integer value reprecenting a recurring, one time, usage, or application_credit.
            // This also allows us to store usage based charges not just subscription or one time charges.
            // We will be able to do things like create a charge history for a shop if they have multiple charges.
            // For instance, usage based or an app that has multiple purchases.
            $table->integer('type');

            // Store the amount of the charge, this helps if you are experimenting with pricing
            $table->decimal('price', 8, 2);

            // Store the amount of the charge, this helps if you are experimenting with pricing
            $table->decimal('capped_amount', 8, 2)->nullable();

            // Nullable in case of 0 trial days
            $table->integer('trial_days')->nullable();

            // The recurring application charge must be accepted or the returned value is null
            $table->timestamp('billing_on')->nullable();

            // When activation happened
            $table->timestamp('activated_on')->nullable();

            // Date the trial period ends
            $table->timestamp('trial_ends_on')->nullable();

            // Not supported on Shopify's initial billing screen, but good for future use
            $table->timestamp('cancelled_on')->nullable();

            // Provides created_at && updated_at columns
            $table->timestamps();

            // Allows for soft deleting
            $table->softDeletes();

            // Linking
            $table->integer('shop_id')->unsigned();
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('charges');
    }
}
