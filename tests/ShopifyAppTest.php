<?php

namespace OhMyBrew\ShopifyApp\Test;

use Illuminate\Support\Facades\Session;
use OhMyBrew\ShopifyApp\Contracts\Queries\Shop as IShopQuery;
use OhMyBrew\ShopifyApp\Objects\Values\ShopDomain;
use OhMyBrew\ShopifyApp\Services\ShopSession;
use OhMyBrew\ShopifyApp\ShopifyApp;

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
        Session::put('shopify_domain', 'example.myshopify.com');

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
        Session::put('shopify_domain', $domain->toNative());

        // Shop should not exist
        $this->assertNull($this->shopQuery->getByDomain($domain));

        // Calling shop() should trigger a create of the non-existant shop
        $this->shopifyApp->shop();

        // Shop should now exist
        $this->assertNotNull($this->shopQuery->getByDomain($domain));
    }
}
