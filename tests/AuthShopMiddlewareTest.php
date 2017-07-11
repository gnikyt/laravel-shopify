<?php namespace OhMyBrew\ShopifyApp\Test;

use OhMyBrew\ShopifyApp\Middleware\AuthShop;

class AuthShopMiddlewareTest extends TestCase
{
    public function testShopHasNoAccessShouldAbort()
    {
        $middleware = new AuthShop;
        $next = function() { };
        $result = $middleware->handle(request(), $next);

        $this->assertEquals(true, strpos($result, 'Redirecting to http://localhost/authenticate') !== false);
    }

    public function testShopHasWithAccessShouldPassMiddleware()
    {
        // Set a shop
        session(['shopify_domain' => 'example.myshopify.com']);

        $self = $this;
        $middleware = new AuthShop;
        $next = function($request) use(&$self) {
            // $next should be invoked since shop is authenticated
            $self->assertNotNull($request);
        };
        $middleware->handle(request(), $next);
    }
}