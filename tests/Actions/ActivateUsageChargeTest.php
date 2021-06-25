<?php

namespace Osiset\ShopifyApp\Test\Actions;

use Osiset\ShopifyApp\Actions\ActivateUsageCharge;
use Osiset\ShopifyApp\Exceptions\ChargeNotRecurringException;
use Osiset\ShopifyApp\Objects\Transfers\UsageChargeDetails;
use Osiset\ShopifyApp\Objects\Values\ChargeId;
use Osiset\ShopifyApp\Storage\Models\Charge;
use Osiset\ShopifyApp\Storage\Models\Plan;
use Osiset\ShopifyApp\Test\Stubs\Api as ApiStub;
use Osiset\ShopifyApp\Test\TestCase;

class ActivateUsageChargeTest extends TestCase
{
    /**
     * @var \Osiset\ShopifyApp\Actions\ActivateUsageCharge
     */
    protected $action;

    public function setUp(): void
    {
        parent::setUp();

        $this->action = $this->app->make(ActivateUsageCharge::class);
    }

    public function testRun(): void
    {
        // Create a plan
        $plan = factory(Plan::class)->states('type_recurring')->create();

        // Create the shop with the plan attached
        $shop = factory($this->model)->create([
            'plan_id' => $plan->getId()->toNative(),
        ]);

        // Create a charge for the plan and shop
        factory(Charge::class)->states('type_recurring')->create([
            'charge_id' => 12345,
            'plan_id'   => $plan->getId()->toNative(),
            'user_id'   => $shop->getId()->toNative(),
        ]);

        // Create the transfer
        $ucd = new UsageChargeDetails();
        $ucd->price = 12.00;
        $ucd->description = 'Hello!';

        // Setup API stub
        $this->setApiStub();
        ApiStub::stubResponses(['post_recurring_application_charges_usage_charges']);

        // Activate the charge
        $result = call_user_func(
            $this->action,
            $shop->getId(),
            $ucd
        );

        $this->assertInstanceOf(ChargeId::class, $result);
    }

    public function testRunWithoutRecurringCharge(): void
    {
        $this->expectExceptionObject(new ChargeNotRecurringException(
            'Can only create usage charges for recurring charge.', 0
        ));

        // Create a plan
        $plan = factory(Plan::class)->states('type_onetime')->create();

        // Create the shop with the plan attached
        $shop = factory($this->model)->create([
            'plan_id' => $plan->getId()->toNative(),
        ]);

        // Create a charge for the plan and shop
        factory(Charge::class)->states('type_onetime')->create([
            'charge_id' => 12345,
            'plan_id'   => $plan->getId()->toNative(),
            'user_id'   => $shop->getId()->toNative(),
        ]);

        // Create the transfer
        $ucd = new UsageChargeDetails();
        $ucd->price = 12.00;
        $ucd->description = 'Hello!';

        call_user_func(
            $this->action,
            $shop->getId(),
            $ucd
        );
    }

    public function testRunWithLimitReached(): void
    {
        // Create a plan
        $plan = factory(Plan::class)->states('type_recurring')->create();

        // Create the shop with the plan attached
        $shop = factory($this->model)->create([
            'plan_id' => $plan->getId()->toNative(),
        ]);

        // Create a charge for the plan and shop
        factory(Charge::class)->states('type_recurring')->create([
            'charge_id' => 12345,
            'plan_id'   => $plan->getId()->toNative(),
            'user_id'   => $shop->getId()->toNative(),
        ]);

        // Create the transfer
        $ucd = new UsageChargeDetails();
        $ucd->price = 12.00;
        $ucd->description = 'Hello!';

        // Setup API stub
        $this->setApiStub();
        ApiStub::stubResponses(['empty']);

        // Activate the charge
        $result = call_user_func(
            $this->action,
            $shop->getId(),
            $ucd
        );

        $this->assertFalse($result);
    }
}
