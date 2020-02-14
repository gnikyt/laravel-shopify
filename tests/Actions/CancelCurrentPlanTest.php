<?php

namespace OhMyBrew\ShopifyApp\Test\Actions;

use OhMyBrew\ShopifyApp\Test\TestCase;
use OhMyBrew\ShopifyApp\Storage\Models\Plan;
use OhMyBrew\ShopifyApp\Storage\Models\Charge;
use OhMyBrew\ShopifyApp\Actions\CancelCurrentPlan;

class CancelCurrentPlanTest extends TestCase
{
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
        $plan = factory(Plan::class)->states('type_recurring')->create();

        // Create the shop with the plan attached
        $shop = factory($this->model)->create([
            'plan_id' => $plan->getId()->toNative()
        ]);

        $result = call_user_func(
            $this->action,
            $shop->getId()
        );

        $this->assertFalse($result);
    }
}
