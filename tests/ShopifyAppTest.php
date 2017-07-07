<?php namespace OhMyBrew\ShopifyApp\Test;

use OhMyBrew\ShopifyApp\ShopifyApp;

class ShopifyAppControllerTest extends TestCase
{
    public function testShopWithoutSession()
    {
        // No session, no API instance, thus no shop
        $shopifyApp = new ShopifyApp($this->app);
        $this->assertNull($shopifyApp->shop());
        $this->assertNull($shopifyApp->api());
    }

    public function testShopWithSession()
    {
        session(['shopify_domain' => 'example.myshopify.com']);
        $shopifyApp = new ShopifyApp($this->app);

        // First run should store the shop object to shop var
        $run1 = $shopifyApp->shop();

        // Second run should retrive shop var
        $run2 = $shopifyApp->shop();

        // First run should store the shop object to shop var
        $run3 = $shopifyApp->api();

        // Second run should retrive shop var
        $run4 = $shopifyApp->api();

        $this->assertEquals($run1, $run2);
        $this->assertEquals($run3, $run4);
    }
}