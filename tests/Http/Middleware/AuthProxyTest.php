<?php

namespace Osiset\ShopifyApp\Test\Http\Middleware;

use Osiset\ShopifyApp\Test\TestCase;
use Illuminate\Support\Facades\Request;
use Osiset\ShopifyApp\Http\Middleware\AuthProxy as AuthProxyMiddleware;

class AuthProxyTest extends TestCase
{
    protected $queryParams;

    public function setUp(): void
    {
        parent::setUp();

        // Make the shop
        factory($this->model)->create(['name' => 'shop-name.myshopify.com']);

        // From Shopify's docs
        $this->queryParams = [
            'extra'       => ['1', '2'],
            'shop'        => 'shop-name.myshopify.com',
            'path_prefix' => '/apps/awesome_reviews',
            'timestamp'   => '1317327555',
            'signature'   => 'a9718877bea71c2484f91608a7eaea1532bdf71f5c56825065fa4ccabe549ef3',
        ];

        // Set the app secret to match Shopify's docs
        $this->app['config']->set('shopify-app.api_secret', 'hush');
    }

    public function testRuns(): void
    {
        Request::merge($this->queryParams);

        // Run the middleware
        $result = $this->runAuthProxy();

        // Confirm full run
        $this->assertTrue($result[1]);
    }

    public function testDenysForMissingShop(): void
    {
        // Remove shop from params
        $query = $this->queryParams;
        unset($query['shop']);
        Request::merge($query);

        // Run the middleware
        $result = $this->runAuthProxy();

        // Assert it was not processed and our status
        $this->assertFalse($result[1]);
        $this->assertEquals(401, $result[0]->status());
    }

    public function testDoesNotRunForInvalidSignature(): void
    {
        // Make the signature invalid
        $query = $this->queryParams;
        $query['oops'] = 'i-did-it-again';
        Request::merge($query);

        // Run the middleware
        $result = $this->runAuthProxy();

        // Assert it was not processed and our status
        $this->assertFalse($result[1]);
        $this->assertEquals(401, $result[0]->status());
    }

    private function runAuthProxy(Closure $cb = null): array
    {
        $called = false;
        $response = ($this->app->make(AuthProxyMiddleware::class))->handle(Request::instance(), function ($request) use (&$called, $cb) {
            $called = true;
        });

        return [$response, $called];
    }
}