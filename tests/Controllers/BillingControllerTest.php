<?php

namespace OhMyBrew\ShopifyApp\Test\Controllers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use OhMyBrew\ShopifyApp\Models\Charge;
use OhMyBrew\ShopifyApp\Models\Plan;
use OhMyBrew\ShopifyApp\Models\Shop;
use OhMyBrew\ShopifyApp\ShopifyApp;
use OhMyBrew\ShopifyApp\Test\Stubs\ApiStub;
use OhMyBrew\ShopifyApp\Test\TestCase;

class BillingControllerTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        // Stub in our API class
        Config::set('shopify-app.api_class', new ApiStub());

        // Create the main class
        $this->shopifyApp = new ShopifyApp($this->app);
    }

    public function testSendsShopToBillingScreen()
    {
        // Stub the responses
        ApiStub::stubResponses([
            'post_recurring_application_charges',
            'post_recurring_application_charges_activate',
        ]);

        // Create the shop
        $shop = factory(Shop::class)->create();
        Session::put('shopify_domain', $shop->shopify_domain);

        // Create a on-install plan
        factory(Plan::class)->states('type_recurring', 'installable')->create();

        // Run the call
        $response = $this->get('/billing');
        $response->assertViewHas(
            'url',
            'https://example.myshopify.com/admin/charges/1029266947/confirm_recurring_application_charge?signature=BAhpBANeWT0%3D--64de8739eb1e63a8f848382bb757b20343eb414f'
        );
    }

    public function testShopAcceptsBilling()
    {
        // Stub the responses
        ApiStub::stubResponses([
            'post_recurring_application_charges',
            'post_recurring_application_charges_activate',
        ]);

        // Create the shop
        $shop = factory(Shop::class)->create();
        Session::put('shopify_domain', $shop->shopify_domain);

        // Make the plan
        $plan = factory(Plan::class)->states('type_recurring')->create();

        // Run the call
        $updatedAt = $shop->updated_at;
        $response = $this->call('get', "/billing/process/{$plan->id}", ['charge_id' => 1]);

        // Refresh the model
        $shop->refresh();

        // Assert we've redirected and shop has been updated
        $response->assertRedirect();
        $this->assertFalse($updatedAt === $shop->updated_at);
    }

    public function testUsageChargeSuccess()
    {
        // Stub the responses
        ApiStub::stubResponses([
            'post_recurring_application_charges_usage_charges_alt',
            'post_recurring_application_charges_usage_charges_alt2',
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
        Session::put('shopify_domain', $shop->shopify_domain);

        // Setup the data for the usage charge and the signature for it
        $data = ['description' => 'One email', 'price' => 1.00, 'redirect' => 'https://localhost/usage-success'];
        $signature = $this->shopifyApp->createHmac(['data' => $data, 'buildQuery' => true]);

        // Run the call
        $response = $this->call('post', '/billing/usage-charge', array_merge($data, ['signature' => $signature]));
        $response->assertRedirect($data['redirect']);
        $response->assertSessionHas('success');

        // Run again with no redirect
        $data = ['description' => 'One email', 'price' => 1.00];
        $signature = $this->shopifyApp->createHmac(['data' => $data, 'buildQuery' => true]);

        // Run the call
        $response = $this->call('post', '/billing/usage-charge', array_merge($data, ['signature' => $signature]));
        $response->assertRedirect('http://localhost');
        $response->assertSessionHas('success');
    }
}
