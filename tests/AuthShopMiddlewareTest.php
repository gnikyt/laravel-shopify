<?php namespace OhMyBrew\ShopifyApp\Test;

use OhMyBrew\ShopifyApp\Middleware\AuthShop;

class AuthShopMiddlewareTest extends TestCase
{
    /**
    * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
    */
    public function testShopHasNoAccessShouldAbort()
    {
        $middleware = new AuthShop;
        $next = function() { };
        $middleware->handle(request(), $next);
    }

    public function testShopHasWithAccessShouldPassMiddleware()
    {
        // Set a shop
        session(['shopify_domain' => 'example.myshopify.com']);

        $self = $this;
        $middleware = new AuthShop;
        $next = function($request) use(&$self) {
            $self->assertNotNull($request);
        };
        $middleware->handle(request(), $next);
    }
}