<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Osiset\ShopifyApp\Util;

class CreateShopsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table(Util::getShopsTable(), function (Blueprint $table) {
            $table->boolean('shopify_grandfathered')->default(false);
            $table->string('shopify_namespace')->nullable(true)->default(null);
            $table->boolean('shopify_freemium')->default(false);
            $table->integer('plan_id')->unsigned()->nullable();

            if (! Schema::hasColumn(Util::getShopsTable(), 'deleted_at')) {
                $table->softDeletes();
            }

            if (! Schema::hasColumn(Util::getShopsTable(), 'name')) {
                $table->string('name')->nullable();
            }

            if (! Schema::hasColumn(Util::getShopsTable(), 'email')) {
                $table->string('email')->nullable();
            }

            if (! Schema::hasColumn(Util::getShopsTable(), 'password')) {
                $table->string('password', 100)->nullable();
            }

            $table->foreign('plan_id')->references('id')->on(Util::getShopifyConfig('table_names.plans', 'plans'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table(Util::getShopsTable(), function (Blueprint $table) {
            $table->dropForeign(Util::getShopsTable().'_plan_id_foreign');
            $table->dropColumn([
                'name',
                'email',
                'password',
                'shopify_grandfathered',
                'shopify_namespace',
                'shopify_freemium',
                'plan_id',
            ]);

            $table->dropSoftDeletes();
        });
    }
}
