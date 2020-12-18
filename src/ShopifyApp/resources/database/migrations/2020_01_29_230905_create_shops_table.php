<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('shopify_grandfathered')->default(false);
            $table->string('shopify_namespace')->nullable(true)->default(null);
            $table->boolean('shopify_freemium')->default(false);
            $table->integer('plan_id')->unsigned()->nullable();

            if (! Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes();
            }

            $table->foreign('plan_id')->references('id')->on('plans');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('users_plan_id_foreign');
            $table->dropColumn([
                'shopify_grandfathered',
                'shopify_namespace',
                'shopify_freemium',
                'plan_id',
            ]);

            $table->dropSoftDeletes();
        });
    }
}
