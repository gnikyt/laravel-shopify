<?php namespace OhMyBrew\ShopifyApp\Test;

use OhMyBrew\ShopifyApp\ShopifyApp;

class ShopifyAppControllerTest extends TestCase
{
    public function testShopWithoutSession()
    {
        // No session, no API instance, thus no shop
        $shopifyApp = new ShopifyApp($this->app);
        $this->assertFalse($shopifyApp->shop());
    }

    public function testShopWithSession()
    {
        session(['shopify_domain' => 'example.myshopify.com']);
        $shopifyApp = new ShopifyApp($this->app);

        // First run should store the API to shop var
        $run1 = $shopifyApp->shop();

        // Second run should retrive shop var
        $run2 = $shopifyApp->shop();

        $this->assertEquals($run1, $run2);
    }
}