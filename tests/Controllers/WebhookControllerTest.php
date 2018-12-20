<?php

namespace OhMyBrew\ShopifyApp\Test\Controllers;

use Illuminate\Support\Facades\Queue;
use OhMyBrew\ShopifyApp\Models\Shop;
use OhMyBrew\ShopifyApp\Test\TestCase;

require_once __DIR__.'/../Stubs/OrdersCreateJobStub.php';

class WebhookControllerTest extends TestCase
{
    public function testSuccess()
    {
        // Fake the queue
        Queue::fake();

        // Mock headers that match Shopify
        $shop = factory(Shop::class)->create();
        $headers = [
            'HTTP_CONTENT_TYPE'          => 'application/json',
            'HTTP_X_SHOPIFY_SHOP_DOMAIN' => $shop->shopify_domain,
            'HTTP_X_SHOPIFY_HMAC_SHA256' => 'hDJhTqHOY7d5WRlbDl4ehGm/t4kOQKtR+5w6wm+LBQw=', // Matches fixture data and API secret
        ];

        // Create a webhook call and pass in our own headers and data
        $response = $this->call(
            'post',
            '/webhook/orders-create',
            [],
            [],
            [],
            $headers,
            file_get_contents(__DIR__.'/../fixtures/webhook.json')
        );

        // Check it was created and job was pushed
        $response->assertStatus(201);
        Queue::assertPushed(\App\Jobs\OrdersCreateJob::class, function ($job) use ($shop) {
            return $job->shopDomain === $shop->shopify_domain
                   && $job->data instanceof \stdClass
                   && $job->data->email === 'jon@doe.ca';
        });
    }

    public function testFailure()
    {
        // Create a webhook call and pass in our own headers and data
        $response = $this->call(
            'post',
            '/webhook/products-create',
            [],
            [],
            [],
            [],
            file_get_contents(__DIR__.'/../fixtures/webhook.json')
        );
        $response->assertStatus(401);
    }
}
