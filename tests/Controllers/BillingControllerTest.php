<?php

namespace OhMyBrew\ShopifyApp\Test\Controllers;

use OhMyBrew\ShopifyApp\ShopifyApp;
use OhMyBrew\ShopifyApp\Models\Shop;
use OhMyBrew\ShopifyApp\Test\Stubs\ApiStub;
use OhMyBrew\ShopifyApp\Test\TestCase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;

class BillingControllerTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

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
        // Stub the responses
        ApiStub::stubResponses([
            'post_recurring_application_charges',
            'post_recurring_application_charges_activate',
        ]);

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

        // Run the call
        $updatedAt = $this->shop->updated_at;
        $response = $this->call('get', '/billing/process/1', ['charge_id' => 1]);

        // Refresh the model
        $this->shop->refresh();

        // Assert we've redirected and shop has been updated
        $response->assertRedirect();
        $this->assertFalse($updatedAt === $this->shop->updated_at);
    }

    public function testUsageChargeSuccess()
    {
        // Stub the responses
        ApiStub::stubResponses([
            'post_recurring_application_charges_usage_charges_alt',
            'post_recurring_application_charges_usage_charges_alt2',
        ]);

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
