<?php

namespace OhMyBrew\ShopifyApp\Test;

use OhMyBrew\BasicShopifyAPI;
use OhMyBrew\ShopifyApp\ShopifyApp;
use OhMyBrew\ShopifyApp\Test\TestCase;
use OhMyBrew\ShopifyApp\Services\ShopSession;
use OhMyBrew\ShopifyApp\Objects\Values\ShopDomain;
use OhMyBrew\ShopifyApp\Contracts\Queries\Shop as IShopQuery;

class ShopifyAppTest extends TestCase
{
    protected $shopifyApp;
    protected $shopQuery;

    public function setUp(): void
    {
        parent::setUp();

        // Shop querier instance
        $this->shopQuery = $this->app->make(IShopQuery::class);

        // ShopApp instance
        $this->shopifyApp = new ShopifyApp(
            $this->app,
            $this->shopQuery,
            $this->app->make(ShopSession::class)
        );
    }

    public function testShopWithoutSession(): void
    {
        // No session, no API instance, thus no shop
        $this->assertNull($this->shopifyApp->shop());
    }

    public function testShopWithSession(): void
    {
        // Set the session
        $this->app['session']->put('shopify_domain', 'example.myshopify.com');

        // First run should store the shop object to shop var
        $run1 = $this->shopifyApp->shop();

        // Second run should retrive shop var
        $run2 = $this->shopifyApp->shop();

        $this->assertEquals($run1, $run2);
    }

    public function testCreatesNewShopWithSessionIfItDoesNotExist(): void
    {
        // Setup the domain to test
        $domain = new ShopDomain('example-nonexistant.myshopify.com');

        // Set the session
        $this->app['session']->put('shopify_domain', $domain->toNative());

        // Shop should not exist
        $this->assertNull($this->shopQuery->getByDomain($domain));

        // Calling shop() should trigger a create of the non-existant shop
        $this->shopifyApp->shop();

        // Shop should now exist
        $this->assertNotNull($this->shopQuery->getByDomain($domain));
    }

    public function testReturnsApiInstance(): void
    {
        $this->assertEquals(BasicShopifyAPI::class, get_class($this->shopifyApp->api()));
    }

    public function testReturnsApiInstanceWithRateLimiting(): void
    {
        $this->app['config']->set('shopify-app.api_rate_limiting_enabled', true);

        $this->assertTrue($this->shopifyApp->api()->isRateLimitingEnabled());
    }

    public function testShopSanitize(): void
    {
        $domains = ['my-shop', 'my-shop.myshopify.com', 'MY-shOp.myshopify.com', 'https://my-shop.myshopify.com/abc/xyz', 'https://my-shop.myshopify.com', 'http://my-shop.myshopify.com'];
        $domains_2 = ['my-shop', 'my-shop.myshopify.io', 'https://my-shop.myshopify.io', 'http://my-shop.myshopify.io'];
        $domains_3 = ['', false, null];

        // Test for standard myshopify.com
        foreach ($domains as $domain) {
            $this->assertEquals('my-shop.myshopify.com', $this->shopifyApp->sanitizeShopDomain($domain));
        }

        // Test if someone changed the domain
        $this->app['config']->set('shopify-app.myshopify_domain', 'myshopify.io');
        foreach ($domains_2 as $domain) {
            $this->assertEquals('my-shop.myshopify.io', $this->shopifyApp->sanitizeShopDomain($domain));
        }

        // Test for empty shops
        foreach ($domains_3 as $domain) {
            $this->assertNull($this->shopifyApp->sanitizeShopDomain($domain));
        }
    }

    public function testHmacCreator()
    {
        // Set the secret to use for HMAC creations
        $secret = 'hello';
        $this->app['config']->set('shopify-app.api_secret', $secret);

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

    public function testDebugger()
    {
        $this->shopifyApp->debug('test');
        $this->assertFalse($this->shopifyApp->debug('test'));

        $this->app['config']->set('shopify-app.debug', true);
        $this->assertTrue($this->shopifyApp->debug('test'));
    }
}
