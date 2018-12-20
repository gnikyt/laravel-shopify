<?php

namespace OhMyBrew\ShopifyApp\Test;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use OhMyBrew\ShopifyApp\Models\Shop;
use OhMyBrew\ShopifyApp\ShopifyApp;

class ShopifyAppTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->shopifyApp = new ShopifyApp($this->app);
    }

    public function testShopWithoutSession()
    {
        // No session, no API instance, thus no shop
        $this->assertNull($this->shopifyApp->shop());
    }

    public function testShopWithSession()
    {
        Session::put('shopify_domain', 'example.myshopify.com');

        // First run should store the shop object to shop var
        $run1 = $this->shopifyApp->shop();

        // Second run should retrive shop var
        $run2 = $this->shopifyApp->shop();

        $this->assertEquals($run1, $run2);
    }

    public function testCreatesNewShopWithSessionIfItDoesNotExist()
    {
        Session::put('shopify_domain', 'example-nonexistant.myshopify.com');

        $this->assertNull(Shop::where('shopify_domain', 'example-nonexistant.myshopify.com')->first());

        $this->shopifyApp->shop();

        $this->assertNotNull(Shop::where('shopify_domain', 'example-nonexistant.myshopify.com')->first());
    }

    public function testReturnsApiInstance()
    {
        $this->assertEquals(\OhMyBrew\BasicShopifyAPI::class, get_class($this->shopifyApp->api()));
    }

    public function testReturnsApiInstanceWithRateLimiting()
    {
        Config::set('shopify-app.api_rate_limiting_enabled', true);

        $this->assertTrue($this->shopifyApp->api()->isRateLimitingEnabled());
    }

    public function testShopSanitize()
    {
        $domains = ['my-shop', 'my-shop.myshopify.com', 'https://my-shop.myshopify.com', 'http://my-shop.myshopify.com'];
        $domains_2 = ['my-shop', 'my-shop.myshopify.io', 'https://my-shop.myshopify.io', 'http://my-shop.myshopify.io'];
        $domains_3 = ['', false, null];

        // Test for standard myshopify.com
        foreach ($domains as $domain) {
            $this->assertEquals('my-shop.myshopify.com', $this->shopifyApp->sanitizeShopDomain($domain));
        }

        // Test if someone changed the domain
        Config::set('shopify-app.myshopify_domain', 'myshopify.io');
        foreach ($domains_2 as $domain) {
            $this->assertEquals('my-shop.myshopify.io', $this->shopifyApp->sanitizeShopDomain($domain));
        }

        // Test for empty shops
        foreach ($domains_3 as $domain) {
            $this->assertNull($this->shopifyApp->sanitizeShopDomain($domain));
        }
    }

    public function testShouldUseDefaultModel()
    {
        $shop = factory(Shop::class)->create();
        Session::put('shopify_domain', $shop->shopify_domain);

        $shop = $this->shopifyApp->shop();

        $this->assertEquals(\OhMyBrew\ShopifyApp\Models\Shop::class, get_class($shop));
    }

    public function testShouldAllowForModelOverride()
    {
        $shop = factory(Shop::class)->create();
        Session::put('shopify_domain', $shop->shopify_domain);
        Config::set('shopify-app.shop_model', \OhMyBrew\ShopifyApp\Test\Stubs\ShopModelStub::class);

        $shop = $this->shopifyApp->shop();

        $this->assertEquals(\OhMyBrew\ShopifyApp\Test\Stubs\ShopModelStub::class, get_class($shop));
        $this->assertEquals('hello', $shop->hello());
    }

    public function testHmacCreator()
    {
        // Set the secret to use for HMAC creations
        $secret = 'hello';
        Config::set('shopify-app.api_secret', $secret);

        // Raw data
        $data = 'one-two-three';
        $this->assertEquals(
            hash_hmac('sha256', $data, $secret, true),
            $this->shopifyApp->createHmac(['data' => $data, 'raw' => true])
        );

        // Raw data encoded
        $data = 'one-two-three';
        $this->assertEquals(
            base64_encode(hash_hmac('sha256', $data, $secret, true)),
            $this->shopifyApp->createHmac(['data' => $data, 'raw' => true, 'encode' => true])
        );

        // Query build (sorts array and builds query string)
        $data = ['one' => 1, 'two' => 2, 'three' => 3];
        $this->assertEquals(
            hash_hmac('sha256', 'one=1three=3two=2', $secret, false),
            $this->shopifyApp->createHmac(['data' => $data, 'buildQuery' => true])
        );
    }
}
