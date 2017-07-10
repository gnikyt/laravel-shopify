<?php namespace OhMyBrew\ShopifyApp\Test;

use OhMyBrew\ShopifyApp\ShopifyApp;
use OhMyBrew\ShopifyApp\Models\Shop;

class ShopifyAppControllerTest extends TestCase
{
    public function testShopWithoutSession()
    {
        // No session, no API instance, thus no shop
        $shopifyApp = new ShopifyApp($this->app);
        $this->assertNull($shopifyApp->shop());
    }

    public function testShopWithSession()
    {
        session(['shopify_domain' => 'example.myshopify.com']);
        $shopifyApp = new ShopifyApp($this->app);

        // First run should store the shop object to shop var
        $run1 = $shopifyApp->shop();

        // Second run should retrive shop var
        $run2 = $shopifyApp->shop();

        $this->assertEquals($run1, $run2);
    }

    public function testCreatesNewShopWithSessionIfItDoesNotExist()
    {
        session(['shopify_domain' => 'example-nonexistant.myshopify.com']);
        $this->assertEquals(null, Shop::where('shopify_domain', 'example-nonexistant.myshopify.com')->first());

        $shopifyApp = new ShopifyApp($this->app);
        $shopifyApp->shop();

        $this->assertNotNull(Shop::where('shopify_domain', 'example-nonexistant.myshopify.com')->first());
    }

    public function testReturnsApiInstance()
    {
        $shopifyApp = new ShopifyApp($this->app);
        $this->assertEquals(\OhMyBrew\BasicShopifyAPI::class, get_class($shopifyApp->api()));
    }

    public function testShopSanitize()
    {
        $domains = ['my-shop', 'my-shop.myshopify.com', 'https://my-shop.myshopify.com', 'http://my-shop.myshopify.com'];
        $domains_2 = ['my-shop', 'my-shop.myshopify.io', 'https://my-shop.myshopify.io', 'http://my-shop.myshopify.io'];

        // Test for standard myshopify.com
        $shopifyApp = new ShopifyApp($this->app);
        foreach ($domains as $domain) {
            $this->assertEquals('my-shop.myshopify.com', $shopifyApp->sanitizeShopDomain($domain));
        }

        // Test if someone changed the domain
        config(['shopify-app.myshopify_domain' => 'myshopify.io']);
        foreach ($domains_2 as $domain) {
            $this->assertEquals('my-shop.myshopify.io', $shopifyApp->sanitizeShopDomain($domain));
        }
    }
}