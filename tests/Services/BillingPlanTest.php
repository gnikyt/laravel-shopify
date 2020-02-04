<?php

namespace OhMyBrew\ShopifyApp\Test\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use OhMyBrew\ShopifyApp\Models\Charge;
use OhMyBrew\ShopifyApp\Models\Plan;
use OhMyBrew\ShopifyApp\Models\Shop;
use OhMyBrew\ShopifyApp\Services\BillingPlan;
use OhMyBrew\ShopifyApp\Test\Stubs\ApiStub;
use OhMyBrew\ShopifyApp\Test\TestCase;

class BillingPlanTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // Stub in our API class
        Config::set('shopify-app.api_class', new ApiStub());
    }

    public function testShouldReturnConfirmationUrl()
    {
        // Stub the responses
        ApiStub::stubResponses([
            'post_recurring_application_charges_activate',
        ]);

        // Create a shop and plan
        $plan = factory(Plan::class)->states('type_recurring')->create();
        $shop = factory(Shop::class)->create();

        $this->assertEquals(
            'https://example.myshopify.com/admin/charges/1029266947/confirm_recurring_application_charge?signature=BAhpBANeWT0%3D--64de8739eb1e63a8f848382bb757b20343eb414f',
            (new BillingPlan($shop, $plan))->confirmationUrl()
        );
    }

    public function testShouldReturnChargeParams()
    {
        // Create a shop and plan
        $plan = factory(Plan::class)->states('type_recurring', 'trial', 'usage')->create();
        $shop = factory(Shop::class)->create();

        $bp = new BillingPlan($shop, $plan);

        // Input should match output
        $this->assertEquals(
            [
                'test'          => $plan->test,
                'trial_days'    => $plan->trial_days,
                'name'          => $plan->name,
                'price'         => $plan->price,
                'capped_amount' => $plan->capped_amount,
                'terms'         => $plan->terms,
                'return_url'    => URL::Secure(Config::get('shopify-app.billing_redirect'), ['plan_id' => $plan->id]),
            ],
            $bp->chargeParams()
        );
    }

    public function testShouldActivatePlan()
    {
        // Stub the responses
        ApiStub::stubResponses([
            'get_recurring_application_charge_activate',
        ]);

        // Create a shop and plan
        $plan = factory(Plan::class)->states('type_recurring')->create();
        $shop = factory(Shop::class)->create();

        // Activate the charge via API
        $bp = new BillingPlan($shop, $plan);
        $response = $bp->setChargeId(1234)->activate();

        $this->assertTrue(is_object($response));
        $this->assertEquals('active', $response->status);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Can not activate plan without a charge ID.
     */
    public function testShouldNotActivatePlanAndThrowExceptionForMissingChargeId()
    {
        // Create a shop and plan
        $plan = factory(Plan::class)->states('type_recurring')->create();
        $shop = factory(Shop::class)->create();

        // We're missing the charge ID
        $bp = new BillingPlan($shop, $plan);
        $bp->activate();
    }

    public function testShouldGetChargeDetails()
    {
        // Stub the responses
        ApiStub::stubResponses([
            'post_recurring_application_charges_activate',
        ]);

        // Create a shop and plan
        $plan = factory(Plan::class)->states('type_recurring')->create();
        $shop = factory(Shop::class)->create();

        // Should get the charge details from the API
        $bp = new BillingPlan($shop, $plan);
        $response = $bp->setChargeId(12345)->getCharge();

        $this->assertTrue(is_object($response));
        $this->assertEquals('accepted', $response->status);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Can not get charge information without charge ID.
     */
    public function testShouldNotGetChargeDetailsAndThrowException()
    {
        // Create a shop and plan
        $plan = factory(Plan::class)->states('type_recurring')->create();
        $shop = factory(Shop::class)->create();

        $bp = new BillingPlan($shop, $plan);
        $bp->getCharge();
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage No activation response was recieved.
     */
    public function testShouldNotSaveDueToMissingActivation()
    {
        // Create a shop and plan
        $plan = factory(Plan::class)->states('type_recurring')->create();
        $shop = factory(Shop::class)->create();

        $bp = new BillingPlan($shop, $plan);
        $bp->save();
    }

    public function testShouldSave()
    {
        // Stub the responses
        ApiStub::stubResponses([
            'post_recurring_application_charges_activate',
        ]);

        // Create a shop, plan, and charge
        $plan = factory(Plan::class)->states('type_recurring')->create([
            'trial_days' => 7,
        ]);
        $shop = factory(Shop::class)->create();

        // Should get a new charge
        $bp = new BillingPlan($shop, $plan);
        $bp->setChargeId(12345);
        $bp->activate();
        $charge = $bp->save();

        // Get the charge
        $shop->refresh();
        $planCharge = $shop->planCharge();
        $this->assertEquals('accepted', $planCharge->status);

        // Confirm trial days are still 7 since this is brand new charge and plan
        $this->assertEquals(7, $planCharge->trial_days);
    }

    public function testShouldSaveAndCancelLastCharge()
    {
        // Stub the responses
        ApiStub::stubResponses([
            'post_recurring_application_charges_activate',
        ]);
        // Create a shop, plan, and charge
        $plan = factory(Plan::class)->states('type_recurring')->create();
        $shop = factory(Shop::class)->create([
            'plan_id' => $plan->id,
        ]);
        $charge = factory(Charge::class)->states('type_recurring')->create([
            'plan_id'    => $plan->id,
            'shop_id'    => $shop->id,
            'created_at' => Carbon::now()->subWeek(),
        ]);
        // Get the shop's plan charge, this should change to cancelled
        $planCharge = $shop->planCharge();
        $status = $planCharge->status;
        // Should get a new charge
        $bp = new BillingPlan($shop, $plan);
        $bp->setChargeId(12345);
        $bp->activate();
        $charge = $bp->save();
        // Reload the old charge
        $planCharge->refresh();
        $this->assertTrue($charge);
        $this->assertEquals('cancelled', $planCharge->status);
        // Get the new charge
        $newPlanCharge = $shop->planCharge();
        $this->assertEquals('accepted', $newPlanCharge->status);
    }

    public function testShouldSaveWithAdjustedTrialDays()
    {
        // Stub the responses
        ApiStub::stubResponses([
            'post_recurring_application_charges_activate',
        ]);

        // Create a shop, plan, and charge
        $trialDays = 14; // Two weeks
        $plan = factory(Plan::class)->states('type_recurring')->create([
            'trial_days' => $trialDays,
        ]);
        $shop = factory(Shop::class)->create([
            'plan_id'    => $plan->id,
        ]);
        $charge = factory(Charge::class)->states('type_recurring')->create([
            'plan_id'       => $plan->id,
            'shop_id'       => $shop->id,
            'status'        => Charge::STATUS_CANCELLED,
            'cancelled_on'  => Carbon::now(),
            'created_at'    => Carbon::now()->subWeek(),
            'trial_ends_on' => Carbon::now()->subWeek()->addDays($trialDays),
        ]);

        // Get the shop's plan charge, this should change to cancelled
        $planCharge = $shop->planCharge();
        $status = $planCharge->status;

        // Should get a new charge
        $bp = new BillingPlan($shop, $plan);
        $bp->setChargeId(12345);
        $bp->activate();
        $charge = $bp->save();

        // Reload the old charge
        $planCharge->refresh();

        $this->assertTrue($charge);
        $this->assertEquals('cancelled', $planCharge->status);

        // Get the new charge
        $newPlanCharge = $shop->planCharge();
        $this->assertEquals('accepted', $newPlanCharge->status);

        // Trial days should be 7 since the shop used 7 of the 14 days before cancelling
        $this->assertEquals(7, $newPlanCharge->trial_days);
    }
}
