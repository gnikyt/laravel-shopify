<?php

namespace Osiset\ShopifyApp\Test\Http\Middleware;

use Illuminate\Support\Facades\Request;
use Osiset\ShopifyApp\Http\Middleware\AuthWebhook as AuthWebhookMiddleware;
use Osiset\ShopifyApp\Test\TestCase;

class AuthWebhookTest extends TestCase
{
    public function testDenysForMissingShopHeader(): void
    {
        $currentRequest = Request::instance();
        $newRequest = $currentRequest->duplicate(
            // Query Params
            [],
            // Request Params
            null,
            // Attributes
            null,
            // Cookies
            null,
            // Files
            null,
            // Server vars
            // This valid referer should be ignored as there is a get variable
            array_merge(Request::server(), [
                'HTTP_X_Shopify_Hmac_Sha256' => '1234',
            ])
        );
        Request::swap($newRequest);

        // Run the middleware
        $response = ($this->app->make(AuthWebhookMiddleware::class))->handle(request(), function () {
            // ...
        });

        // Assert we get a proper response
        $this->assertSame(401, $response->status());
    }

    public function testDenysForMissingHmacHeader(): void
    {
        $currentRequest = Request::instance();
        $newRequest = $currentRequest->duplicate(
            // Query Params
            [],
            // Request Params
            null,
            // Attributes
            null,
            // Cookies
            null,
            // Files
            null,
            // Server vars
            // This valid referer should be ignored as there is a get variable
            array_merge(Request::server(), [
                'HTTP_X_Shopify_Shop_Domain' => 'exapmle.myshopify.com',
            ])
        );
        Request::swap($newRequest);

        // Run the middleware
        $response = ($this->app->make(AuthWebhookMiddleware::class))->handle(request(), function () {
            // ...
        });

        // Assert we get a proper response
        $this->assertSame(401, $response->status());
    }

    public function testRuns(): void
    {
        $currentRequest = Request::instance();
        $newRequest = $currentRequest->duplicate(
            // Query Params
            [],
            // Request Params
            null,
            // Attributes
            null,
            // Cookies
            null,
            // Files
            null,
            // Server vars
            // This valid referer should be ignored as there is a get variable
            array_merge(Request::server(), [
                'HTTP_X_Shopify_Hmac_Sha256' => 'vhwRQys0GZozEzsl9+bjquUnCkjSE7r1YPgl9S0CY1E=',
                'HTTP_X_Shopify_Shop_Domain' => 'example.myshopify.com',
            ])
        );
        Request::swap($newRequest);

        // Run the middleware
        $called = false;
        $response = ($this->app->make(AuthWebhookMiddleware::class))->handle(request(), function () use (&$called) {
            $called = true;
        });

        // Assert we get a proper response
        $this->assertTrue($called);
    }
}
