<?php

namespace OhMyBrew\ShopifyApp\Test\Middleware;

use Illuminate\Support\Facades\Input;
use OhMyBrew\ShopifyApp\Middleware\AuthShop;
use OhMyBrew\ShopifyApp\Test\TestCase;

class AuthShopMiddlewareTest extends TestCase
{
    public function testShopHasNoAccessShouldAbort()
    {
        $called = false;
        $result = (new AuthShop())->handle(request(), function ($request) use (&$called) {
            // Should never be called
            $called = true;
        });

        $this->assertFalse($called);
        $this->assertEquals(true, strpos($result, 'Redirecting to http://localhost/authenticate') !== false);
    }

    public function testShopHasWithAccessShouldPassMiddleware()
    {
        // Set a shop
        session(['shopify_domain' => 'example.myshopify.com']);

        $called = false;
        (new AuthShop())->handle(request(), function ($request) use (&$called) {
            // Should be called
            $called = true;
        });

        $this->assertEquals(true, $called);
    }

    public function testShopWithNoTokenShouldNotPassMiddleware()
    {
        // Set a shop
        session(['shopify_domain' => 'no-token.myshopify.com']);

        $called = false;
        $result = (new AuthShop())->handle(request(), function ($request) use (&$called) {
            // Shouldn never be called
            $called = true;
        });

        $this->assertFalse($called);
        $this->assertEquals(true, strpos($result, 'Redirecting to http://localhost/authenticate') !== false);
    }

    public function testShopsWhichDoNotMatchShouldKillSessionAndDirectToReAuthenticate()
    {
        // Set a shop
        session(['shopify_domain' => 'example.myshopify.com']);
        Input::merge(['shop' => 'example-different-shop.myshopify.com']);

        $called = false;
        (new AuthShop())->handle(request(), function ($request) use (&$called) {
            // Should never be called
            $called = true;
        });

        $this->assertFalse($called);
        $this->assertEquals('example-different-shop.myshopify.com', request('shop'));
    }

    public function testHeadersForEsdkShouldBeAdjusted()
    {
        // Set a shop
        session(['shopify_domain' => 'example.myshopify.com']);

        $response = (new AuthShop())->handle(
            request(),
            function ($request) use (&$called) {
                // Nothing to do here...
            }
        );

        $this->assertEquals('CP="Not used"', $response->headers->get('p3p'));
        $this->assertNull($response->headers->get('x-frame-options'));
    }

    public function testHeadersForDisabledEsdk()
    {
        // Set a shop
        session(['shopify_domain' => 'example.myshopify.com']);
        config(['shopify-app.esdk_enabled' => false]);

        $response = (new AuthShop())->handle(
            request(),
            function ($request) use (&$called) {
                // Nothing to do here...
            }
        );

        $this->assertNull($response->headers->get('p3p'));
        $this->assertNull($response->headers->get('x-frame-options'));
    }
}
