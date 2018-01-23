<?php namespace OhMyBrew\ShopifyApp\Test\Middleware;

use OhMyBrew\ShopifyApp\Middleware\AuthShop;
use Illuminate\Support\Facades\Input;
use OhMyBrew\ShopifyApp\Test\TestCase;

class AuthShopMiddlewareTest extends TestCase
{
    public function testShopHasNoAccessShouldAbort()
    {
        $called = false;
        $result = (new AuthShop)->handle(request(), function ($request) use (&$called) {
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
        (new AuthShop)->handle(request(), function ($request) use (&$called) {
            // Should be called
            $called = true;
        });

        $this->assertEquals(true, $called);
    }

    public function testShopsWhichDoNotMatchShouldKillSessionAndDirectToReAuthenticate()
    {
        // Set a shop
        session(['shopify_domain' => 'example.myshopify.com']);
        Input::merge(['shop' => 'example-different-shop.myshopify.com']);

        $called = false;
        (new AuthShop)->handle(request(), function ($request) use (&$called) {
            // Should never be called
            $called = true;
        });

        $this->assertFalse($called);
        $this->assertEquals('example-different-shop.myshopify.com', session('shop'));
    }
}
