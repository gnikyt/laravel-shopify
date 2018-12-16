<?php

namespace OhMyBrew\ShopifyApp\Test\Services;

use OhMyBrew\ShopifyApp\Libraries\BillingPlan;
use OhMyBrew\ShopifyApp\Models\Plan;
use OhMyBrew\ShopifyApp\Models\Shop;
use OhMyBrew\ShopifyApp\Test\Stubs\ApiStub;
use OhMyBrew\ShopifyApp\Test\TestCase;
use Illuminate\Support\Facades\Config;

class BillingPlanTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        // Stub in our API class
        Config::set('shopify-app.api_class', new ApiStub());

        // Base shop to use
        $this->shop = Shop::find(1);
    }

    public function testShouldReturnConfirmationUrl()
    {
        $this->assertEquals(
            'https://example.myshopify.com/admin/charges/1029266947/confirm_recurring_application_charge?signature=BAhpBANeWT0%3D--64de8739eb1e63a8f848382bb757b20343eb414f',
            (new BillingPlan($this->shop, Plan::find(1)))->getConfirmationUrl()
        );
    }

    public function testShouldReturnConfirmationUrlWhenUsageIsEnabled()
    {
        $this->assertEquals(
            'https://example.myshopify.com/admin/charges/1017262355/confirm_application_charge?signature=BAhpBBMxojw%3D--1139a82a3433b1a6771786e03f02300440e11883',
            (new BillingPlan($this->shop, Plan::find(3)))->getConfirmationUrl()
        );
    }

    /**
     * @expectedException \ArgumentCountError
     */
    public function testShouldThrowExceptionForMissingPlan()
    {
        new BillingPlan($this->shop);
    }

    public function testShouldReturnChargeParams()
    {
        $this->assertEquals(
            [
                'test'          => false,
                'trial_days'    => '7',
                'name'          => 'Capped Plan',
                'price'         => '5',
                'return_url'    => secure_url(config('shopify-app.billing_redirect'), ['plan_id' => 4]),
                'capped_amount' => '100',
                'terms'         => '$1 for 500 emails',
            ],
            (new BillingPlan($this->shop, Plan::find(4)))->getChargeParams()
        );
    }

    public function testShouldActivatePlan()
    {
        $response = (new BillingPlan($this->shop, Plan::find(1)))->setChargeId(1029266947)->activate();

        $this->assertTrue(is_object($response));
        $this->assertEquals('active', $response->status);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Can not activate plan without a charge ID.
     */
    public function testShouldNotActivatePlanAndThrowException()
    {
        (new BillingPlan($this->shop, Plan::find(1)))->activate();
    }

    public function testShouldGetChargeDetails()
    {
        $response = (new BillingPlan($this->shop, Plan::find(1)))->setChargeId(1029266947)->getCharge();

        $this->assertTrue(is_object($response));
        $this->assertEquals('accepted', $response->status);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Can not get charge information without charge ID.
     */
    public function testShouldNotGetChargeDetailsAndThrowException()
    {
        (new BillingPlan($this->shop, Plan::find(1)))->getCharge();
    }
}
