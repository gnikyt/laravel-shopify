<?php

namespace OhMyBrew\ShopifyApp\Test\Libraries;

use OhMyBrew\ShopifyApp\Libraries\BillingPlan;
use OhMyBrew\ShopifyApp\Models\Shop;
use OhMyBrew\ShopifyApp\Test\Stubs\ApiStub;
use OhMyBrew\ShopifyApp\Test\TestCase;

class BillingPlanTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        // Stub in our API class
        config(['shopify-app.api_class' => new ApiStub()]);

        // Base shop and plan
        $this->shop = Shop::find(1);
        $this->plan = [
            'name'       => 'Basic Plan',
            'price'      => 3.00,
            'trial_days' => 0,
            'return_url' => 'http://example.com/',
        ];
    }

    public function testShouldReturnConfirmationUrl()
    {
        $url = (new BillingPlan($this->shop))->setDetails($this->plan)->getConfirmationUrl();

        $this->assertEquals(
            'https://example.myshopify.com/admin/charges/1029266947/confirm_recurring_application_charge?signature=BAhpBANeWT0%3D--64de8739eb1e63a8f848382bb757b20343eb414f',
            $url
        );
    }

    public function testShouldReturnConfirmationUrlWhenUsageIsEnabled()
    {
        $plan = array_merge($this->plan, [
            'capped_amount' => 100.00,
            'terms'         => '$1 for 500 emails',
        ]);
        $url = (new BillingPlan($this->shop))->setDetails($plan)->getConfirmationUrl();

        $this->assertEquals(
            'https://example.myshopify.com/admin/charges/1029266947/confirm_recurring_application_charge?signature=BAhpBANeWT0%3D--64de8739eb1e63a8f848382bb757b20343eb414f',
            $url
        );
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Plan details are missing for confirmation URL request.
     */
    public function testShouldNotReturnConfirmationUrlAndThrowException()
    {
        (new BillingPlan($this->shop))->getConfirmationUrl();
    }

    public function testShouldActivatePlan()
    {
        $response = (new BillingPlan($this->shop))->setChargeId(1029266947)->activate();

        $this->assertEquals(true, is_object($response));
        $this->assertEquals('accepted', $response->status);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Can not activate plan without a charge ID.
     */
    public function testShouldNotActivatePlanAndThrowException()
    {
        (new BillingPlan($this->shop))->activate();
    }

    public function testShouldGetChargeDetails()
    {
        $response = (new BillingPlan($this->shop))->setChargeId(1029266947)->getCharge();

        $this->assertEquals(true, is_object($response));
        $this->assertEquals('accepted', $response->status);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Can not get charge information without charge ID.
     */
    public function testShouldNotGetChargeDetailsAndThrowException()
    {
        (new BillingPlan($this->shop))->getCharge();
    }
}
