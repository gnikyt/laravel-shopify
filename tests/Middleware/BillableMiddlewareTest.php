<?php

namespace OhMyBrew\ShopifyApp\Test\Middleware;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use OhMyBrew\ShopifyApp\Middleware\Billable;
use OhMyBrew\ShopifyApp\Models\Charge;
use OhMyBrew\ShopifyApp\Models\Plan;
use OhMyBrew\ShopifyApp\Models\Shop;
use OhMyBrew\ShopifyApp\Test\TestCase;

class BillableMiddlewareTest extends TestCase
{
    public function testEnabledBillingWithUnpaidShop()
    {
        // Enable billing and set a shop
        $shop = factory(Shop::class)->create();
        Config::set('shopify-app.billing_enabled', true);
        Session::put('shopify_domain', $shop->shopify_domain);

        // Run the middleware
        $result = $this->runBillable();

        // Assert it was not called and redirect happened
        $this->assertFalse($result[1]);
        $this->assertTrue(strpos($result[0], 'Redirecting to http://localhost/billing') !== false);
    }

    public function testEnabledBillingWithPaidShop()
    {
        // Enable billing and set a shop
        $plan = factory(Plan::class)->states('type_recurring')->create();
        $shop = factory(Shop::class)->create([
            'plan_id' => $plan->id,
        ]);
        $charge = factory(Charge::class)->states('type_recurring')->create([
            'plan_id' => $plan->id,
            'shop_id' => $shop->id,
        ]);

        Config::set('shopify-app.billing_enabled', true);
        Session::put('shopify_domain', $shop->shopify_domain);

        // Run the middleware
        $result = $this->runBillable();

        // Assert it was called
        $this->assertTrue($result[1]);
    }

    public function testEnabledBillingWithGrandfatheredShop()
    {
        // Enable billing and set a shop
        $shop = factory(Shop::class)->states('grandfathered')->create();
        Config::set('shopify-app.billing_enabled', true);
        Session::put('shopify_domain', $shop->shopify_domain);

        // Run the middleware
        $result = $this->runBillable();

        // Assert it was called
        $this->assertTrue($result[1]);
    }

    public function testEnabledBillingWithFreemiumShop()
    {
        // Enable billing and set a shop
        $shop = factory(Shop::class)->states('freemium')->create();
        Config::set('shopify-app.billing_enabled', true);
        Session::put('shopify_domain', $shop->shopify_domain);

        // Run the middleware
        $result = $this->runBillable();

        // Assert it was called
        $this->assertTrue($result[1]);
    }

    public function testDisabledBillingShouldPassOn()
    {
        // Ensure billing is disabled and set a shop
        $shop = factory(Shop::class)->create();
        Config::set('shopify-app.billing_enabled', false);
        Session::put('shopify_domain', $shop->shopify_domain);

        // Run the middleware
        $result = $this->runBillable();

        $this->assertTrue($result[1]);
    }

    public function testShopWithNoPlanShouldRedirect()
    {
        // Ensure billing is disabled and set a shop
        $shop = factory(Shop::class)->create();
        Config::set('shopify-app.billing_enabled', true);
        Session::put('shopify_domain', $shop->shopify_domain);

        // Run the middleware
        $result = $this->runBillable();

        // Assert it was not called and redirect happened
        $this->assertFalse($result[1]);
        $this->assertTrue(strpos($result[0], '/billing') !== false);
    }

    private function runBillable(Closure $cb = null)
    {
        $called = false;
        $response = (new Billable())->handle(Request::instance(), function ($request) use (&$called, $cb) {
            $called = true;

            if ($cb) {
                $cb($request);
            }
        });

        return [$response, $called];
    }
}
