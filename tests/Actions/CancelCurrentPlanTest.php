<?php

namespace Osiset\ShopifyApp\Test\Actions;

use Osiset\ShopifyApp\Actions\CancelCurrentPlan;
use Osiset\ShopifyApp\Storage\Models\Plan;
use Osiset\ShopifyApp\Test\TestCase;
use Osiset\ShopifyApp\Util;

class CancelCurrentPlanTest extends TestCase
{
    /**
     * @var \Osiset\ShopifyApp\Actions\CancelCurrentPlan
     */
    protected $action;

    public function setUp(): void
    {
        parent::setUp();

        $this->action = $this->app->make(CancelCurrentPlan::class);
    }

    public function testCancelWithNoPlan(): void
    {
        // Create the shop with no plan attached
        $shop = factory($this->model)->create();

        $result = call_user_func(
            $this->action,
            $shop->getId()
        );

        $this->assertFalse($result);
    }

    public function testCancelWithPlanButNoCharge(): void
    {
        // Create a plan
        $plan = factory(Util::getShopifyConfig('models.plan', Plan::class))->states('type_recurring')->create();

        // Create the shop with the plan attached
        $shop = factory($this->model)->create([
            'plan_id' => $plan->getId()->toNative(),
        ]);

        $result = call_user_func(
            $this->action,
            $shop->getId()
        );

        $this->assertFalse($result);
    }
}
