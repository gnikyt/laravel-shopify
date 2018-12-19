<?php

namespace OhMyBrew\ShopifyApp\Test\Middleware;

use OhMyBrew\ShopifyApp\Middleware\Billable;
use OhMyBrew\ShopifyApp\Test\TestCase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;

class BillableMiddlewareTest extends TestCase
{
    public function testEnabledBillingWithUnpaidShop()
    {
        // Enable billing and set a shop
        Config::set('shopify-app.billing_enabled', true);
        Session::put('shopify_domain', 'new-shop.myshopify.com');

        // Run the middleware
        $result = $this->runBillable();

        // Assert it was not called and redirect happened
        $this->assertFalse($result[1]);
        $this->assertTrue(strpos($result[0], 'Redirecting to http://localhost/billing') !== false);
    }

    public function testEnabledBillingWithPaidShop()
    {
        // Enable billing and set a shop
        Config::set('shopify-app.billing_enabled', true);
        Session::put('shopify_domain', 'example.myshopify.com');

        // Run the middleware
        $result = $this->runBillable();

        // Assert it was called
        $this->assertTrue($result[1]);
    }

    public function testEnabledBillingWithGrandfatheredShop()
    {
        // Enable billing and set a shop
        Config::set('shopify-app.billing_enabled', true);
        Session::put('shopify_domain', 'grandfathered.myshopify.com');

        // Run the middleware
        $result = $this->runBillable();

        // Assert it was called
        $this->assertTrue($result[1]);
    }

    public function testEnabledBillingWithFreemiumShop()
    {
        // Enable billing and set a shop
        Config::set('shopify-app.billing_enabled', true);
        Session::put('shopify_domain', 'freemium-shop.myshopify.com');

        // Run the middleware
        $result = $this->runBillable();

        // Assert it was called
        $this->assertTrue($result[1]);
    }

    public function testDisabledBillingShouldPassOn()
    {
        // Ensure billing is disabled and set a shop
        Config::set('shopify-app.billing_enabled', false);
        Session::put('shopify_domain', 'example.myshopify.com');

        // Run the middleware
        $result = $this->runBillable();

        $this->assertTrue($result[1]);
    }

    public function testShopWithNoPlanShouldRedirect()
    {
        // Ensure billing is disabled and set a shop
        Config::set('shopify-app.billing_enabled', true);
        Session::put('shopify_domain', 'planless-shop.myshopify.com');

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
