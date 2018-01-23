<?php namespace OhMyBrew\ShopifyApp\Test\Middleware;

use OhMyBrew\ShopifyApp\Middleware\Billable;
use Illuminate\Support\Facades\Input;
use OhMyBrew\ShopifyApp\Test\TestCase;

class BillableMiddlewareTest extends TestCase
{
    public function testEnabledBillingWithUnpaidShop()
    {
        // Enable billing and set a shop
        config(['shopify-app.billing_enabled' => true]);
        session(['shopify_domain' => 'new-shop.myshopify.com']);

        $called = false;
        $result = (new Billable)->handle(request(), function ($request) use (&$called) {
            // Should never be called
            $called = true;
        });

        $this->assertFalse($called);
        $this->assertEquals(true, strpos($result, 'Redirecting to http://localhost/billing') !== false);
    }

    public function testEnabledBillingWithPaidShop()
    {
        // Enable billing and set a shop
        config(['shopify-app.billing_enabled' => true]);
        session(['shopify_domain' => 'example.myshopify.com']);

        $called = false;
        $result = (new Billable)->handle(request(), function ($request) use (&$called) {
            // Should be called
            $called = true;
        });

        $this->assertTrue($called);
    }

    public function testEnabledBillingWithGrandfatheredShop()
    {
        // Enable billing and set a shop
        config(['shopify-app.billing_enabled' => true]);
        session(['shopify_domain' => 'grandfathered.myshopify.com']);

        $called = false;
        $result = (new Billable)->handle(request(), function ($request) use (&$called) {
            // Should be called
            $called = true;
        });

        $this->assertTrue($called);
    }

    public function testDisabledBillingShouldPassOn()
    {
        // Ensure billing is disabled and set a shop
        config(['shopify-app.billing_enabled' => false]);
        session(['shopify_domain' => 'example.myshopify.com']);

        $called = false;
        $result = (new Billable)->handle(request(), function ($request) use (&$called) {
            // Should be called
            $called = true;
        });

        $this->assertTrue($called);
    }
}
