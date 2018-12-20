<?php

namespace OhMyBrew\ShopifyApp\Test\Middleware;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use OhMyBrew\ShopifyApp\Middleware\AuthShop;
use OhMyBrew\ShopifyApp\Models\Shop;
use OhMyBrew\ShopifyApp\Test\TestCase;

class AuthShopMiddlewareTest extends TestCase
{
    public function testShopHasNoAccessShouldAbort()
    {
        // Run the middleware
        $result = $this->runAuthShop();

        // Assert it was not called and we redirect
        $this->assertFalse($result[1]);
        $this->assertTrue(strpos($result[0], 'Redirecting to http://localhost/authenticate') !== false);
    }

    public function testShopHasWithAccessShouldPassMiddleware()
    {
        // Set a shop
        $shop = factory(Shop::class)->create();
        Session::put('shopify_domain', $shop->shopify_domain);

        // Run the middleware
        $result = $this->runAuthShop();

        // Assert it was not called
        $this->assertTrue($result[1]);
    }

    public function testShopWithNoTokenShouldNotPassMiddleware()
    {
        // Set a shop
        $shop = factory(Shop::class)->create([
            'shopify_token' => null,
        ]);
        Session::put('shopify_domain', $shop->shopify_domain);

        // Run the middleware
        $result = $this->runAuthShop();

        // Assert it was not called and a redirect happened
        $this->assertFalse($result[1]);
        $this->assertTrue(strpos($result[0], 'Redirecting to http://localhost/authenticate') !== false);
    }

    public function testShopTrashedShouldNotPassMiddleware()
    {
        // Set a shop
        $shop = factory(Shop::class)->create();
        $shop->delete();
        Session::put('shopify_domain', $shop->shopify_domain);

        // Run the middleware
        $result = $this->runAuthShop();

        // Assert it was not called a redirect happens
        $this->assertFalse($result[1]);
        $this->assertTrue(strpos($result[0], 'Redirecting to http://localhost/authenticate') !== false);
    }

    public function testShopsWhichDoNotMatchShouldKillSessionAndDirectToReAuthenticate()
    {
        // Set a shop
        $shop = factory(Shop::class)->create();
        Session::put('shopify_domain', $shop->shopify_domain);

        // Go in as a new shop
        Input::merge(['shop' => 'example-different-shop.myshopify.com']);

        // Run the middleware
        $result = $this->runAuthShop();

        // Assert it was not called and the new shop was passed
        $this->assertFalse($result[1]);
        $this->assertEquals('example-different-shop.myshopify.com', Request::get('shop'));
    }

    public function testHeadersForEsdkShouldBeAdjusted()
    {
        // Set a shop
        $shop = factory(Shop::class)->create();
        Session::put('shopify_domain', $shop->shopify_domain);

        // Run the middleware
        $result = $this->runAuthShop();

        // Assert the headers were modified
        $this->assertEquals('CP="Not used"', $result[0]->headers->get('p3p'));
        $this->assertNull($result[0]->headers->get('x-frame-options'));
    }

    public function testHeadersForDisabledEsdk()
    {
        // Set a shop
        $shop = factory(Shop::class)->create();
        Session::put('shopify_domain', $shop->shopify_domain);

        // Disable ESDL
        Config::set('shopify-app.esdk_enabled', false);

        // Run the middleware
        $result = $this->runAuthShop();

        // Assert the headers were not modified
        $this->assertNull($result[0]->headers->get('p3p'));
        $this->assertNull($result[0]->headers->get('x-frame-options'));
    }

    public function testShouldSaveReturnUrl()
    {
        // Set a shop
        $shop = factory(Shop::class)->create([
            'shopify_token' => null,
        ]);
        Session::put('shopify_domain', $shop->shopify_domain);

        // Duplicate the request so we can mod the request URI
        $currentRequest = Request::instance();
        Request::swap($currentRequest->duplicate(null, null, null, null, null, array_merge(Request::server(), ['REQUEST_URI' => '/orders'])));

        // Run the middleware
        $result = $this->runAuthShop();

        // Assert it was not called and a redirect happened
        $this->assertFalse($result[1]);
        $this->assertEquals('http://localhost/orders', Session::get('return_to'));
        $this->assertTrue(strpos($result[0], 'Redirecting to http://localhost/authenticate') !== false);

        // Reset
        // Request::swap($currentRequest);
    }

    private function runAuthShop(Closure $cb = null)
    {
        $called = false;
        $response = (new AuthShop())->handle(Request::instance(), function ($request) use (&$called, $cb) {
            $called = true;

            if ($cb) {
                $cb($request);
            }
        });

        return [$response, $called];
    }
}
