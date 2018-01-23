<?php namespace OhMyBrew\ShopifyApp\Test\Controllers;

use \ReflectionMethod;
use Illuminate\Support\Facades\Queue;
use OhMyBrew\ShopifyApp\Test\TestCase;

require_once __DIR__.'/../Stubs/OrdersCreateJobStub.php';

class WebhookControllerTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->headers = [
            'HTTP_CONTENT_TYPE' => 'application/json',
            'HTTP_X_SHOPIFY_SHOP_DOMAIN' => 'example.myshopify.com',
            'HTTP_X_SHOPIFY_HMAC_SHA256' => 'hDJhTqHOY7d5WRlbDl4ehGm/t4kOQKtR+5w6wm+LBQw=', // Matches fixture data and API secret
        ];
    }

    public function testShouldReturn201ResponseOnSuccess()
    {
        Queue::fake();

        $response = $this->call(
            'post',
            '/webhook/orders-create',
            [],
            [],
            [],
            $this->headers,
            file_get_contents(__DIR__.'/../fixtures/webhook.json')
        );
        $response->assertStatus(201);

        Queue::assertPushed(\App\Jobs\OrdersCreateJob::class);
    }


    public function testShouldReturnErrorResponseOnFailure()
    {
        $response = $this->call(
            'post',
            '/webhook/products-create',
            [],
            [],
            [],
            $this->headers,
            file_get_contents(__DIR__.'/../fixtures/webhook.json')
        );
        $response->assertStatus(500);
        $this->assertEquals('Missing webhook job: \App\Jobs\ProductsCreateJob', $response->exception->getMessage());
    }

    public function testShouldCaseTypeToClass()
    {
        $controller = new \OhMyBrew\ShopifyApp\Controllers\WebhookController;
        $method = new ReflectionMethod(\OhMyBrew\ShopifyApp\Controllers\WebhookController::class, 'getJobClassFromType');
        $method->setAccessible(true);

        $types = [
            'orders-create' => 'OrdersCreateJob',
            'super-duper-order' => 'SuperDuperOrderJob',
            'order' => 'OrderJob'
        ];

        foreach ($types as $type => $className) {
            $this->assertEquals("\\App\\Jobs\\$className", $method->invoke($controller, $type));
        }
    }

    public function testWebhookShouldRecieveData()
    {
        Queue::fake();

        $response = $this->call(
            'post',
            '/webhook/orders-create',
            [],
            [],
            [],
            $this->headers,
            file_get_contents(__DIR__.'/../fixtures/webhook.json')
        );
        $response->assertStatus(201);

        Queue::assertPushed(\App\Jobs\OrdersCreateJob::class, function ($job) {
            return $job->shopDomain === 'example.myshopify.com'
                   && $job->data instanceof \stdClass
                   && $job->data->email === 'jon@doe.ca'
            ;
        });
    }
}
