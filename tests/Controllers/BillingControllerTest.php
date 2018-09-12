<?php

namespace OhMyBrew\ShopifyApp\Test\Controllers;

use Carbon\Carbon;
use OhMyBrew\ShopifyApp\Controllers\BillingController;
use OhMyBrew\ShopifyApp\Models\Charge;
use OhMyBrew\ShopifyApp\Models\Plan;
use OhMyBrew\ShopifyApp\Models\Shop;
use OhMyBrew\ShopifyApp\ShopifyApp;
use OhMyBrew\ShopifyApp\Test\Stubs\ApiStub;
use OhMyBrew\ShopifyApp\Test\TestCase;
use ReflectionMethod;

class BillingControllerTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        // Stub in our API class
        config(['shopify-app.api_class' => new ApiStub()]);

        // Create the main class
        $this->shopifyApp = new ShopifyApp($this->app);

        // Base shop for all tests here
        $this->shop = Shop::where('shopify_domain', 'example.myshopify.com')->first();
        session(['shopify_domain' => $this->shop->shopify_domain]);
    }

    public function testSendsShopToBillingScreen()
    {
        $response = $this->get('/billing');
        $response->assertViewHas(
            'url',
            'https://example.myshopify.com/admin/charges/1029266947/confirm_recurring_application_charge?signature=BAhpBANeWT0%3D--64de8739eb1e63a8f848382bb757b20343eb414f'
        );
    }

    public function testShopAcceptsBilling()
    {
        // Use the base shop
        $shop = Shop::where('shopify_domain', 'example.myshopify.com')->first();
        $chargeId = 1029266947;

        // example.myshopify.com has previous charge defined in TestCase setup
        $oldCharge = $shop->charges()->whereIn('type', [Charge::CHARGE_RECURRING, Charge::CHARGE_ONETIME])->orderBy('created_at', 'desc')->first();

        // Run with a new charge
        $response = $this->call('get', '/billing/process/1', ['charge_id' => $chargeId]);

        // Get the new charge and refresh the old one
        $newCharge = $shop->charges()->get()->last();
        $oldCharge->refresh();

        $response->assertStatus(302);
        $this->assertEquals($chargeId, $newCharge->charge_id);
        $this->assertEquals('cancelled', $oldCharge->status);
    }

    public function testShopDeclinesBilling()
    {
        // Make the call and grab the last charge
        $response = $this->call('get', '/billing/process/1', ['charge_id' => 10292]);
        $lastCharge = $this->shop->charges()->get()->last();

        // Should now match
        $this->assertEquals(10292, $lastCharge->charge_id);
        $this->assertEquals('declined', $lastCharge->status);
        $response->assertViewHas('message', 'It seems you have declined the billing charge for this application');
    }

    public function testReturnOnInstallFlaggedPlan()
    {
        $controller = new BillingController();
        $method = new ReflectionMethod(BillingController::class, 'getPlan');
        $method->setAccessible(true);

        // Based on default config
        $this->assertEquals(Plan::find(1), $method->invoke($controller, null));
    }

    public function testReturnPlanPassedToController()
    {
        $controller = new BillingController();
        $method = new ReflectionMethod(BillingController::class, 'getPlan');
        $method->setAccessible(true);

        // Based on default config
        $this->assertEquals(Plan::find(2), $method->invoke($controller, 2));
    }

    public function testReturnsLastChargeForShop()
    {
        $controller = new BillingController();
        $method = new ReflectionMethod(BillingController::class, 'getLastCharge');
        $method->setAccessible(true);

        // Based on default config
        $this->assertInstanceOf(Charge::class, $method->invoke($controller, $this->shop));
    }

    public function testUsageChargeSuccessWithRedirect()
    {
        // Create a new charge for the shop to make a usage charge against
        $charge = new Charge();
        $charge->charge_id = 12939009;
        $charge->name = 'Base Plan';
        $charge->type = Charge::CHARGE_RECURRING;
        $charge->price = 25.00;
        $charge->shop_id = $this->shop->id;
        $charge->plan_id = Plan::find(1)->id;
        $charge->created_at = Carbon::now()->addMinutes(5);
        $charge->save();

        // Setup the data for the usage charge and the signature for it
        $data = ['description' => 'One email', 'price' => 1.00, 'redirect' => 'https://localhost/usage-success'];
        $signature = $this->shopifyApp->createHmac(['data' => $data, 'buildQuery' => true]);

        $response = $this->call('post', '/billing/usage-charge', array_merge($data, ['signature' => $signature]));
        $lastCharge = $this->shop->charges()->get()->last();

        $response->assertStatus(302);
        $response->assertRedirect($data['redirect']);
        $this->assertEquals(Charge::CHARGE_USAGE, $lastCharge->type);
        $this->assertEquals($data['description'], $lastCharge->description);
        $this->assertEquals($data['price'], $lastCharge->price);
    }

    public function testUsageChargeSuccessWithNoRedirect()
    {
        // Create a new charge for the shop to make a usage charge against
        $charge = new Charge();
        $charge->charge_id = 21828118;
        $charge->name = 'Base Plan';
        $charge->type = Charge::CHARGE_RECURRING;
        $charge->price = 25.00;
        $charge->shop_id = $this->shop->id;
        $charge->plan_id = Plan::find(1)->id;
        $charge->created_at = Carbon::now()->addMinutes(5);
        $charge->save();

        // Setup the data for the usage charge and the signature for it
        $data = ['description' => 'One email', 'price' => 1.00];
        $signature = $this->shopifyApp->createHmac(['data' => $data, 'buildQuery' => true]);

        $response = $this->call('post', '/billing/usage-charge', array_merge($data, ['signature' => $signature]));
        $lastCharge = $this->shop->charges()->get()->last();

        $response->assertStatus(302);
        $response->assertRedirect('http://localhost');
        $response->assertSessionHas('success');
        $this->assertEquals(Charge::CHARGE_USAGE, $lastCharge->type);
        $this->assertEquals($data['description'], $lastCharge->description);
        $this->assertEquals($data['price'], $lastCharge->price);
    }
}
