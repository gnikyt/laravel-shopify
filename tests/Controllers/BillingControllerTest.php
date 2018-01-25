<?php

namespace OhMyBrew\ShopifyApp\Test\Controllers;

use OhMyBrew\ShopifyApp\Controllers\BillingController;
use OhMyBrew\ShopifyApp\Models\Shop;
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

        // Base shop for all tests here
        session(['shopify_domain' => 'example.myshopify.com']);
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
        $shop = Shop::where('shopify_domain', 'example.myshopify.com')->first();
        $this->assertEquals(678298290, $shop->charge_id); // Based on seedDatabase()

        $response = $this->call('get', '/billing/process', ['charge_id' => 1029266947]);
        $shop = $shop->fresh(); // Reload model

        $response->assertStatus(302);
        $this->assertEquals(1029266947, $shop->charge_id);
    }

    public function testShopDeclinesBilling()
    {
        $response = $this->call('get', '/billing/process', ['charge_id' => 10292]);

        $response->assertStatus(403);
        $this->assertEquals(
            'It seems you have declined the billing charge for this application',
            $response->exception->getMessage()
        );
    }

    public function testReturnsBasePlanDetails()
    {
        $controller = new BillingController();
        $method = new ReflectionMethod(BillingController::class, 'planDetails');
        $method->setAccessible(true);

        // Based on default config
        $this->assertEquals(
            [
                'name'       => config('shopify-app.billing_plan'),
                'price'      => config('shopify-app.billing_price'),
                'test'       => config('shopify-app.billing_test'),
                'trial_days' => config('shopify-app.billing_trial_days'),
                'return_url' => url(config('shopify-app.billing_redirect')),

            ],
            $method->invoke($controller, 'planDetails')
        );
    }

    public function testReturnsBaseChargeType()
    {
        $controller = new BillingController();
        $method = new ReflectionMethod(BillingController::class, 'chargeType');
        $method->setAccessible(true);

        // Based on default config
        $this->assertEquals(config('shopify-app.billing_type'), $method->invoke($controller, 'chargeType'));
    }
}
