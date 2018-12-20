<?php

namespace OhMyBrew\ShopifyApp\Test\Services;

use Illuminate\Support\Facades\Config;
use OhMyBrew\ShopifyApp\Models\Charge;
use OhMyBrew\ShopifyApp\Models\Plan;
use OhMyBrew\ShopifyApp\Models\Shop;
use OhMyBrew\ShopifyApp\Services\UsageCharge;
use OhMyBrew\ShopifyApp\Test\Stubs\ApiStub;
use OhMyBrew\ShopifyApp\Test\TestCase;

class UsageChargeTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        // Stub in our API class
        Config::set('shopify-app.api_class', new ApiStub());
    }

    public function testActivateAndSave()
    {
        // Stub the responses
        ApiStub::stubResponses([
            'get_usage_charge',
        ]);

        // Create the shop
        $plan = factory(Plan::class)->states('type_recurring')->create();
        $shop = factory(Shop::class)->create([
            'plan_id' => $plan->id,
        ]);
        $charge = factory(Charge::class)->states('type_recurring')->create([
            'plan_id' => $plan->id,
            'shop_id' => $shop->id,
        ]);

        // Do the call
        $uc = new UsageCharge($shop, ['price' => '1.00', 'description' => 'Test']);
        $result = $uc->activate();
        $uc->save();

        $this->assertTrue(is_object($result));
    }

    /**
     * @expectedException Exception
     */
    public function testActivateFailureForNonRecurringCharges()
    {
        // Stub the responses
        ApiStub::stubResponses([
            'get_usage_charge',
        ]);

        // Create the shop
        $plan = factory(Plan::class)->states('type_onetime')->create();
        $shop = factory(Shop::class)->create([
            'plan_id' => $plan->id,
        ]);
        $charge = factory(Charge::class)->states('type_onetime')->create([
            'plan_id' => $plan->id,
            'shop_id' => $shop->id,
        ]);

        // Do the call
        $uc = new UsageCharge($shop, ['price' => '1.00', 'description' => 'Test']);
        $uc->activate();
    }

    /**
     * @expectedException Exception
     */
    public function testSaveFailureForNoResponse()
    {
        // Create the shop
        $shop = factory(Shop::class)->create();

        // Do the call
        $uc = new UsageCharge($shop, ['price' => '1.00', 'description' => 'Test']);
        $uc->save();
    }
}
