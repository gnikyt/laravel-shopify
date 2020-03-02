<?php

namespace Osiset\ShopifyApp\Test\Http\Middleware;

use Osiset\ShopifyApp\Test\TestCase;
use Illuminate\Support\Facades\Request;
use Osiset\ShopifyApp\Http\Middleware\AuthWebhook as AuthWebhookMiddleware;

class AuthWebhookTest extends TestCase
{
    public function testDenysForMissingShopHeader(): void
    {
        Request::instance()->header('x-shopify-hmac-sha256', '1234');

        // Run the middleware
        $response = ($this->app->make(AuthWebhookMiddleware::class))->handle(request(), function ($request) {
            // ...
        });

        // Assert we get a proper response
        $this->assertEquals(401, $response->status());
    }

    public function testDenysForMissingHmacHeader(): void
    {
        Request::instance()->header('x-shopify-shop-domain', 'example.myshopify.com');

        // Run the middleware
        $response = ($this->app->make(AuthWebhookMiddleware::class))->handle(request(), function ($request) {
            // ...
        });

        // Assert we get a proper response
        $this->assertEquals(401, $response->status());
    }

    public function testRuns(): void
    {
        Request::instance()->header('x-shopify-shop-domain', 'example.myshopify.com');
        Request::instance()->header('x-shopify-hmac-256', 'thNnmggU2ex3L5XXeMNfxf8Wl8STcVZTxscSFEKSxa0=%');

        // Run the middleware
        $response = ($this->app->make(AuthWebhookMiddleware::class))->handle(request(), function ($request) {
            // ...
        });

        // Assert we get a proper response
        $this->assertEquals(401, $response->status());
    }
}