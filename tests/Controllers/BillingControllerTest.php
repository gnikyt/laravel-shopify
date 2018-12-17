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
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use ReflectionMethod;

class BillingControllerTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();$this->withoutExceptionHandling();

        // Stub in our API class
        Config::set('shopify-app.api_class', new ApiStub());

        // Create the main class
        $this->shopifyApp = new ShopifyApp($this->app);

        // Base shop for all tests here
        $this->shop = Shop::where('shopify_domain', 'example.myshopify.com')->first();
        Session::put('shopify_domain', $this->shop->shopify_domain);
    }

    public function testSendsShopToBillingScreen()
    {
        $response = $this->get('/billing');
        $response->assertViewHas(
            'url',
            'https://example.myshopify.com/admin/charges/1029266947/confirm_recurring_application_charge?signature=BAhpBANeWT0%3D--64de8739eb1e63a8f848382bb757b20343eb414f'
        );
    }

    public function testShopAcceptsBillingRecurring()
    {
        // Use the base shop
        $shop = Shop::where('shopify_domain', 'example.myshopify.com')->first();
        $planCharge = $shop->planCharge();
        $chargeId = 1029266947;

        // Run with a new charge
        $response = $this->call('get', '/billing/process/1', ['charge_id' => $chargeId]);

        // Get the new charge and refresh the old one
        $newCharge = $shop->charges()->get()->last();
        $planCharge->refresh();

        $response->assertStatus(302);
        $this->assertEquals($chargeId, $newCharge->charge_id);
        $this->assertEquals('cancelled', $planCharge->status);
    }

    public function testShopAcceptsBillingOneTime()
    {
        // Use the base shop
        $shop = Shop::where('shopify_domain', 'example.myshopify.com')->first();
        $planCharge = $shop->planCharge();
        $chargeId = 675931192;

        // Run with a new charge
        $response = $this->call('get', '/billing/process/3', ['charge_id' => $chargeId]);

        // Get the new charge and refresh the old one
        $newCharge = $shop->charges()->get()->last();
        $planCharge->refresh();

        $response->assertStatus(302);
        $this->assertEquals($chargeId, $newCharge->charge_id);
        $this->assertEquals('cancelled', $planCharge->status);
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
/*
    public function testUsageChargeFailureWithNonRecurringCharge()
    {
        // Create a new charge for the shop to make a usage charge against
        $charge = new Charge();
        $charge->charge_id = 25630290;
        $charge->name = 'Base Plan';
        $charge->type = Charge::CHARGE_ONETIME;
        $charge->price = 25.00;
        $charge->shop_id = $this->shop->id;
        $charge->plan_id = Plan::find(1)->id;
        $charge->created_at = Carbon::now()->addMinutes(5);
        $charge->save();

        // Setup the data for the usage charge and the signature for it
        $data = ['description' => 'One email', 'price' => 1.00];
        $signature = $this->shopifyApp->createHmac(['data' => $data, 'buildQuery' => true]);

        $response = $this->call('post', '/billing/usage-charge', array_merge($data, ['signature' => $signature]));

        $response->assertStatus(200);
        $response->assertViewHas('message');
    }*/
/*
    public function testUsageChargeFailureWithSignatureError()
    {
        // Create a new charge for the shop to make a usage charge against
        $charge = new Charge();
        $charge->charge_id = 78398939;
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
        $data['price'] = 0.00;

        $response = $this->call('post', '/billing/usage-charge', array_merge($data, ['signature' => $signature]));

        $response->assertStatus(200);
        $response->assertViewHas('message');
    }*/
}
