<?php

namespace OhMyBrew\ShopifyApp\Test\Actions;

use OhMyBrew\ShopifyApp\Test\TestCase;
use OhMyBrew\ShopifyApp\Storage\Models\Plan;
use OhMyBrew\ShopifyApp\Actions\CancelCharge;
use OhMyBrew\ShopifyApp\Exceptions\ChargeNotRecurringOrOnetimeException;
use OhMyBrew\ShopifyApp\Objects\Values\ChargeReference;
use OhMyBrew\ShopifyApp\Storage\Models\Charge;

class CancelChargeTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->action = $this->app->make(CancelCharge::class);
    }

    public function testCancel(): void
    {
        // Create a charge reference
        $chargeRef = new ChargeReference(123456);

        // Create a plan
        $plan = factory(Plan::class)->states('type_recurring')->create();

        // Create the shop with the plan attached
        $shop = factory($this->model)->create([
            'plan_id' => $plan->getId()->toNative()
        ]);

        // Create a charge for the plan and shop
        factory(Charge::class)->states('type_recurring')->create([
            'charge_id' => $chargeRef->toNative(),
            'plan_id'   => $plan->getId()->toNative(),
            'user_id'   => $shop->getId()->toNative()
        ]);

        $result = call_user_func($this->action, $chargeRef);

        $this->assertTrue($result);
    }

    public function testCancelOfNonRecurringNonOnetime(): void
    {
        $this->expectException(ChargeNotRecurringOrOnetimeException::class);

        // Create a charge reference
        $chargeRef = new ChargeReference(123456);

        // Create a plan
        $plan = factory(Plan::class)->states('type_recurring')->create();

        // Create the shop with the plan attached
        $shop = factory($this->model)->create([
            'plan_id' => $plan->getId()->toNative()
        ]);

        // Create a charge for the plan and shop
        factory(Charge::class)->states('type_usage')->create([
            'charge_id' => $chargeRef->toNative(),
            'plan_id'   => $plan->getId()->toNative(),
            'user_id'   => $shop->getId()->toNative()
        ]);

        call_user_func($this->action, $chargeRef);
    }
}
