<?php

namespace OhMyBrew\ShopifyApp\Test\Services;

use Illuminate\Support\Facades\URL;
use OhMyBrew\ShopifyApp\Models\Plan;
use OhMyBrew\ShopifyApp\Models\Shop;
use Illuminate\Support\Facades\Config;
use OhMyBrew\ShopifyApp\Models\Charge;
use OhMyBrew\ShopifyApp\Test\TestCase;
use OhMyBrew\ShopifyApp\Test\Stubs\ApiStub;
use OhMyBrew\ShopifyApp\Services\BillingPlan;

class BillingPlanTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        // Stub in our API class
        Config::set('shopify-app.api_class', new ApiStub());

        // Base shop to use
        $this->shop = Shop::find(1);

        // Charge ID we're using that matches the fixtures
        $this->recurringChargeId = 1029266947;
        $this->singleChargeId = 1017262355;
    }

    public function testShouldReturnConfirmationUrl()
    {
        $this->assertEquals(
            "https://example.myshopify.com/admin/charges/{$this->recurringChargeId}/confirm_recurring_application_charge?signature=BAhpBANeWT0%3D--64de8739eb1e63a8f848382bb757b20343eb414f",
            (new BillingPlan($this->shop, Plan::find(1)))->confirmationUrl()
        );
    }

    public function testShouldReturnConfirmationUrlWhenUsageIsEnabled()
    {
        $this->assertEquals(
            "https://example.myshopify.com/admin/charges/{$this->singleChargeId}/confirm_application_charge?signature=BAhpBBMxojw%3D--1139a82a3433b1a6771786e03f02300440e11883",
            (new BillingPlan($this->shop, Plan::find(3)))->confirmationUrl()
        );
    }

    public function testShouldReturnChargeParams()
    {
        $bp = new BillingPlan($this->shop, Plan::find(4));

        // Input should match output
        $this->assertEquals(
            [
                'test'          => false,
                'trial_days'    => '7',
                'name'          => 'Capped Plan',
                'price'         => '5',
                'return_url'    => URL::Secure(Config::get('shopify-app.billing_redirect'), ['plan_id' => 4]),
                'capped_amount' => '100',
                'terms'         => '$1 for 500 emails',
            ],
            $bp->chargeParams()
        );
    }

    public function testShouldActivatePlan()
    {
        // Activate the charge via API
        $bp = new BillingPlan($this->shop, Plan::find(1));
        $response = $bp->setChargeId($this->recurringChargeId)->activate();

        $this->assertTrue(is_object($response));
        $this->assertEquals('active', $response->status);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Can not activate plan without a charge ID.
     */
    public function testShouldNotActivatePlanAndThrowExceptionForMissingChargeId()
    {
        // We're missing the charge ID
        $bp = new BillingPlan($this->shop, Plan::find(1));
        $bp->activate();
    }

    public function testShouldGetChargeDetails()
    {
        // Should get the charge details from the API
        $bp = new BillingPlan($this->shop, Plan::find(1));
        $response = $bp->setChargeId($this->recurringChargeId)->getCharge();

        $this->assertTrue(is_object($response));
        $this->assertEquals('accepted', $response->status);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Can not get charge information without charge ID.
     */
    public function testShouldNotGetChargeDetailsAndThrowException()
    {
        $bp = new BillingPlan($this->shop, Plan::find(1));
        $bp->getCharge();
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage No activation response was recieved.
     */
    public function testShouldNotSaveDueToMissingActivation()
    {
        $bp = new BillingPlan($this->shop, Plan::find(1));
        $bp->save();
    }

    public function testShouldSave()
    {
        // Get the shop's plan charge, this should change to cancelled
        $planCharge = $this->shop->planCharge();
        $status = $planCharge->status;

        // Should get a new charge
        $bp = new BillingPlan($this->shop, Plan::find(1));
        $bp->setChargeId($this->recurringChargeId);
        $bp->activate();
        $charge = $bp->save();

        // Reload the old charge
        $planCharge->refresh();

        $this->assertTrue($charge);
        $this->assertEquals('cancelled', $planCharge->status);
    }
}
