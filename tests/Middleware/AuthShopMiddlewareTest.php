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
        $this->assertTrue(strpos($result, 'Redirecting to http://localhost/authenticate') !== false);
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

        $this->assertTrue($called);
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
        $this->assertTrue(strpos($result, 'Redirecting to http://localhost/authenticate') !== false);
    }

    public function testShopTrashedShouldNotPassMiddleware()
    {
        // Set a shop
        session(['shopify_domain' => 'trashed-shop.myshopify.com']);

        $called = false;
        $result = (new AuthShop())->handle(request(), function ($request) use (&$called) {
            // Shouldn never be called
            $called = true;
        });

        $this->assertFalse($called);
        $this->assertTrue(strpos($result, 'Redirecting to http://localhost/authenticate') !== false);
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

    public function testShouldSaveReturnUrl()
    {
        // Set a shop
        session(['shopify_domain' => 'no-token.myshopify.com']);

        // Duplicate the request so we can mod the request URI
        $request = request()->duplicate(null, null, null, null, null, array_merge(request()->server->all(), ['REQUEST_URI' => '/orders']));

        $called = false;
        $result = (new AuthShop())->handle($request, function ($request) use (&$called) {
            // Shouldn never be called
            $called = true;
        });

        $this->assertFalse($called);
        $this->assertEquals('http://localhost/orders', session('return_to'));
        $this->assertTrue(strpos($result, 'Redirecting to http://localhost/authenticate') !== false);
    }
}
