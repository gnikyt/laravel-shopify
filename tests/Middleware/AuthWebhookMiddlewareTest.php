<?php

namespace OhMyBrew\ShopifyApp\Test\Middleware;

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Request;
use OhMyBrew\ShopifyApp\Middleware\AuthWebhook;
use OhMyBrew\ShopifyApp\Test\TestCase;

require_once __DIR__.'/../Stubs/OrdersCreateJobStub.php';

class AuthWebhookMiddlewareTest extends TestCase
{
    public function testDenysForMissingShopHeader()
    {
        Request::instance()->header('x-shopify-hmac-sha256', '1234');

        // Run the middleware
        $response = (new AuthWebhook())->handle(request(), function ($request) {
            // ...
        });

        // Assert we get a proper response
        $this->assertEquals(401, $response->status());
    }

    public function testDenysForMissingHmacHeader()
    {
        Request::instance()->header('x-shopify-shop-domain', 'example.myshopify.com');

        // Run the middleware
        $response = (new AuthWebhook())->handle(request(), function ($request) {
            // ...
        });

        // Assert we get a proper response
        $this->assertEquals(401, $response->status());
    }

    public function testRuns()
    {
        // Fake the queue
        Queue::fake();

        // Run the call with our owm mocked Shopify headers and data
        $response = $this->call(
            'post',
            '/webhook/orders-create',
            [],
            [],
            [],
            [
                'HTTP_CONTENT_TYPE'          => 'application/json',
                'HTTP_X_SHOPIFY_SHOP_DOMAIN' => 'example.myshopify.com',
                'HTTP_X_SHOPIFY_HMAC_SHA256' => 'hDJhTqHOY7d5WRlbDl4ehGm/t4kOQKtR+5w6wm+LBQw=', // Matches fixture data and API secret
            ],
            file_get_contents(__DIR__.'/../fixtures/webhook.json')
        );

        $response->assertStatus(201);
    }

    public function testInvalidHmacWontRun()
    {
        // Fake the data
        Queue::fake();

        // Run the call with our owm mocked Shopify headers and data
        $response = $this->call(
            'post',
            '/webhook/orders-create',
            [],
            [],
            [],
            [
                'HTTP_CONTENT_TYPE'          => 'application/json',
                'HTTP_X_SHOPIFY_SHOP_DOMAIN' => 'example.myshopify.com',
                'HTTP_X_SHOPIFY_HMAC_SHA256' => 'hDJhTqHOY7d5WRlbDl4ehGm/t4kOQKtR+5w6wm+LBQw=', // Matches fixture data and API secret
            ],
            file_get_contents(__DIR__.'/../fixtures/webhook.json').'invalid'
        );

        $response->assertStatus(401);
    }
}
