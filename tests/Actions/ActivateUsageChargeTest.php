<?php

namespace OhMyBrew\ShopifyApp\Test\Actions;

use OhMyBrew\ShopifyApp\Test\TestCase;
use OhMyBrew\ShopifyApp\Storage\Models\Plan;
use OhMyBrew\ShopifyApp\Storage\Models\Charge;
use OhMyBrew\ShopifyApp\Test\Stubs\Api as ApiStub;
use OhMyBrew\ShopifyApp\Actions\ActivateUsageCharge;
use OhMyBrew\ShopifyApp\Objects\Transfers\UsageChargeDetails;
use OhMyBrew\ShopifyApp\Exceptions\ChargeNotRecurringException;

class ActivateUsageChargeTest extends TestCase
{
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
            'plan_id' => $plan->getId()->toNative()
        ]);

        // Create a charge for the plan and shop
        factory(Charge::class)->states('type_recurring')->create([
            'charge_id' => 12345,
            'plan_id'   => $plan->getId()->toNative(),
            'user_id'   => $shop->getId()->toNative()
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

        $this->assertIsInt($result);
    }

    public function testRunWithoutRecurringCharge(): void
    {
        $this->expectException(ChargeNotRecurringException::class);

        // Create a plan
        $plan = factory(Plan::class)->states('type_onetime')->create();

        // Create the shop with the plan attached
        $shop = factory($this->model)->create([
            'plan_id' => $plan->getId()->toNative()
        ]);

        // Create a charge for the plan and shop
        factory(Charge::class)->states('type_onetime')->create([
            'charge_id' => 12345,
            'plan_id'   => $plan->getId()->toNative(),
            'user_id'   => $shop->getId()->toNative()
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
}
