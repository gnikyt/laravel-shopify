<?php

namespace OhMyBrew\ShopifyApp\Test\Controllers;

use Illuminate\Support\Facades\Queue;
use OhMyBrew\ShopifyApp\Controllers\WebhookController;
use OhMyBrew\ShopifyApp\Test\TestCase;
use ReflectionMethod;

require_once __DIR__.'/../Stubs/OrdersCreateJobStub.php';

class WebhookControllerTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        // Mock headers that match Shopify
        $this->headers = [
            'HTTP_CONTENT_TYPE'          => 'application/json',
            'HTTP_X_SHOPIFY_SHOP_DOMAIN' => 'example.myshopify.com',
            'HTTP_X_SHOPIFY_HMAC_SHA256' => 'hDJhTqHOY7d5WRlbDl4ehGm/t4kOQKtR+5w6wm+LBQw=', // Matches fixture data and API secret
        ];
    }

    public function testShouldReturn201ResponseOnSuccess()
    {
        // Fake the queue
        Queue::fake();

        // Create a webhook call and pass in our own headers and data
        $response = $this->call(
            'post',
            '/webhook/orders-create',
            [],
            [],
            [],
            $this->headers,
            file_get_contents(__DIR__.'/../fixtures/webhook.json')
        );

        // Check it was created and job was pushed
        $response->assertStatus(201);
        Queue::assertPushed(\App\Jobs\OrdersCreateJob::class);
    }

    public function testShouldReturnErrorResponseOnFailure()
    {
        // Create a webhook call and pass in our own headers and data
        $response = $this->call(
            'post',
            '/webhook/products-create',
            [],
            [],
            [],
            $this->headers,
            file_get_contents(__DIR__.'/../fixtures/webhook.json')
        );

        // Check it contains error and exception matches
        $response->assertStatus(500);
        $this->assertEquals('Missing webhook job: \App\Jobs\ProductsCreateJob', $response->exception->getMessage());
    }

    public function testShouldCaseTypeToClass()
    {
        $controller = new WebhookController();
        $method = new ReflectionMethod(WebhookController::class, 'getJobClassFromType');
        $method->setAccessible(true);

        // Map URL path to job
        $types = [
            'orders-create'     => 'OrdersCreateJob',
            'super-duper-order' => 'SuperDuperOrderJob',
            'order'             => 'OrderJob',
        ];

        // Confirm mapping
        foreach ($types as $type => $className) {
            $this->assertEquals("\\App\\Jobs\\$className", $method->invoke($controller, $type));
        }
    }

    public function testWebhookShouldRecieveData()
    {
        // Fake the queue
        Queue::fake();

        // Create a webhook call and pass in our own headers and data
        $response = $this->call(
            'post',
            '/webhook/orders-create',
            [],
            [],
            [],
            $this->headers,
            file_get_contents(__DIR__.'/../fixtures/webhook.json')
        );

        // Check it was created, and job was ushed with matching data from fixture
        $response->assertStatus(201);
        Queue::assertPushed(\App\Jobs\OrdersCreateJob::class, function ($job) {
            return $job->shopDomain === 'example.myshopify.com'
                   && $job->data instanceof \stdClass
                   && $job->data->email === 'jon@doe.ca';
        });
    }
}
