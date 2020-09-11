<?php

namespace Osiset\ShopifyApp\Test\Actions;

use Osiset\ShopifyApp\Test\TestCase;
use Osiset\ShopifyApp\Storage\Models\Plan;
use Osiset\ShopifyApp\Actions\CancelCharge;
use Osiset\ShopifyApp\Exceptions\ChargeNotRecurringOrOnetimeException;
use Osiset\ShopifyApp\Objects\Values\ChargeReference;
use Osiset\ShopifyApp\Storage\Models\Charge;

class CancelChargeTest extends TestCase
{
    /**
     * @var \Osiset\ShopifyApp\Actions\CancelCharge
     */
    protected $action;

    public function setUp(): void
    {
        parent::setUp();

        $this->action = $this->app->make(CancelCharge::class);
    }

    public function testCancel(): void
    {
        // Create a charge reference
        $chargeRef = ChargeReference::fromNative(123456);

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
        $chargeRef = ChargeReference::fromNative(123456);

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
