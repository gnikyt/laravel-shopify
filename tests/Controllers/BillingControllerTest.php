<?php

namespace OhMyBrew\ShopifyApp\Test\Controllers;

use Carbon\Carbon;
use OhMyBrew\ShopifyApp\Controllers\BillingController;
use OhMyBrew\ShopifyApp\Models\Shop;
use OhMyBrew\ShopifyApp\Models\Charge;
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
        $shop = Shop::where('shopify_domain', 'example.myshopify.com')->first();
        $response = $this->call('get', '/billing/process', ['charge_id' => 1029266947]);

        $response->assertStatus(302);
        $this->assertEquals(1029266947, $shop->charges()->get()->last()->charge_id);
    }

    public function testShopDeclinesBilling()
    {
        $shop = Shop::where('shopify_domain', 'example.myshopify.com')->first();
        $response = $this->call('get', '/billing/process', ['charge_id' => 10292]);
        $lastCharge = $shop->charges()->get()->last();

        $response->assertStatus(403);
        $this->assertEquals(10292, $lastCharge->charge_id);
        $this->assertEquals('declined', $lastCharge->status);
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
            $method->invoke($controller, $this->shop)
        );
    }

    public function testReturnsBasePlanDetailsWithUsage()
    {
        config(['shopify-app.billing_capped_amount' => 100.00]);
        config(['shopify-app.billing_terms' => '$1 for 100 emails.']);

        $controller = new BillingController();
        $method = new ReflectionMethod(BillingController::class, 'planDetails');
        $method->setAccessible(true);

        // Based on default config
        $this->assertEquals(
            [
                'name'          => config('shopify-app.billing_plan'),
                'price'         => config('shopify-app.billing_price'),
                'test'          => config('shopify-app.billing_test'),
                'trial_days'    => config('shopify-app.billing_trial_days'),
                'capped_amount' => config('shopify-app.billing_capped_amount'),
                'terms'         => config('shopify-app.billing_terms'),
                'return_url'    => url(config('shopify-app.billing_redirect')),
            ],
            $method->invoke($controller, $this->shop)
        );
    }

    public function testReturnsBasePlanDetailsChangedByCancelledCharge()
    {
        $shop = new Shop();
        $shop->shopify_domain = 'test-cancelled-shop.myshopify.com';
        $shop->save();

        $charge = new Charge();
        $charge->charge_id = 267921978;
        $charge->test = false;
        $charge->name = 'Base Plan Cancelled';
        $charge->status = 'cancelled';
        $charge->type = 1;
        $charge->price = 25.00;
        $charge->trial_days = 7;
        $charge->trial_ends_on = Carbon::today()->addWeeks(1)->format('Y-m-d');
        $charge->cancelled_on = Carbon::today()->addDays(2)->format('Y-m-d');
        $charge->shop_id = $shop->id;
        $charge->save();

        $controller = new BillingController();
        $method = new ReflectionMethod(BillingController::class, 'planDetails');
        $method->setAccessible(true);
    
        // Based on default config
        $this->assertEquals(
            [
                'name'       => config('shopify-app.billing_plan'),
                'price'      => config('shopify-app.billing_price'),
                'test'       => config('shopify-app.billing_test'),
                'trial_days' => 5,
                'return_url' => url(config('shopify-app.billing_redirect')),
            ],
            $method->invoke($controller, $shop)
        );
    }

    public function testReturnsBaseChargeType()
    {
        $controller = new BillingController();
        $method = new ReflectionMethod(BillingController::class, 'chargeType');
        $method->setAccessible(true);

        // Based on default config
        $this->assertEquals(config('shopify-app.billing_type'), $method->invoke($controller));
    }
}
