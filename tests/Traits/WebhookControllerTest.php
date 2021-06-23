<?php

namespace Osiset\ShopifyApp\Test\Traits;

use App\Jobs\OrdersCreateJob;
use Illuminate\Support\Facades\Queue;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;
use Osiset\ShopifyApp\Test\TestCase;
use stdClass;

require_once __DIR__.'/../Stubs/OrdersCreateJob.php';

class WebhookControllerTest extends TestCase
{
    public function testSuccess(): void
    {
        // Fake the queue
        Queue::fake();

        // Mock headers that match Shopify
        $shop = factory($this->model)->create(['name' => 'example.myshopify.com']);
        $headers = [
            'HTTP_CONTENT_TYPE'          => 'application/json',
            'HTTP_X_SHOPIFY_SHOP_DOMAIN' => $shop->name,
            'HTTP_X_SHOPIFY_HMAC_SHA256' => 'hvTE9wpDzMcDnPEuHWvYZ58ElKn5vHs0LomurfNIuUc=', // Matches fixture data and API secret
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
        Queue::assertPushed(OrdersCreateJob::class, function ($job) use ($shop) {
            return ShopDomain::fromNative($job->shopDomain)->isSame($shop->getDomain())
                && $job->data instanceof stdClass
                && $job->data->email === 'jon@doe.ca';
        });
    }

    public function testFailure(): void
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
