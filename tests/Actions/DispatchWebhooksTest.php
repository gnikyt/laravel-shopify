<?php

namespace Osiset\ShopifyApp\Test\Actions;

use Illuminate\Support\Facades\Queue;
use Osiset\ShopifyApp\Actions\DispatchWebhooks;
use Osiset\ShopifyApp\Messaging\Jobs\WebhookInstaller;
use Osiset\ShopifyApp\Test\Stubs\Api as ApiStub;
use Osiset\ShopifyApp\Test\TestCase;

class DispatchWebhooksTest extends TestCase
{
    /**
     * @var \Osiset\ShopifyApp\Actions\DispatchWebhooks
     */
    protected $action;

    public function setUp(): void
    {
        parent::setUp();

        $this->action = $this->app->make(DispatchWebhooks::class);
    }

    public function testRunDispatchOnNoScripts(): void
    {
        // Fake the queue
        Queue::fake();

        // Create the shop
        $shop = factory($this->model)->create();

        // Run
        $result = call_user_func(
            $this->action,
            $shop->getId(),
            false // async
        );

        Queue::assertNotPushed(WebhookInstaller::class);
        $this->assertFalse($result);
    }

    public function testRunDispatch(): void
    {
        // Fake the queue
        Queue::fake();

        // Create the config
        $this->app['config']->set('shopify-app.webhooks', [
            [
                'topic'   => 'orders/create',
                'address' => 'https://localhost/webhooks/orders-create',
            ],
            [
                'topic'   => 'app/uninstalled',
                'address' => 'http://apple.com/uninstall',
            ],
        ]);

        // Setup API stub
        $this->setApiStub();
        ApiStub::stubResponses(['get_webhooks']);

        // Create the shop
        $shop = factory($this->model)->create();

        // Run
        $result = call_user_func(
            $this->action,
            $shop->getId(),
            false // async
        );

        Queue::assertPushed(WebhookInstaller::class);
        $this->assertTrue($result);
    }

    public function testRunDispatchNow(): void
    {
        // Fake the queue
        Queue::fake();

        // Create the config
        $this->app['config']->set('shopify-app.webhooks', [
            [
                'topic'   => 'orders/create',
                'address' => 'https://localhost/webhooks/orders-create',
            ],
            [
                'topic'   => 'app/uninstalled',
                'address' => 'http://apple.com/uninstall',
            ],
        ]);

        // Setup API stub
        $this->setApiStub();
        ApiStub::stubResponses(['get_webhooks']);

        // Create the shop
        $shop = factory($this->model)->create();

        // Run
        $result = call_user_func(
            $this->action,
            $shop->getId(),
            true // sync
        );

        Queue::assertNotPushed(WebhookInstaller::class);
        $this->assertTrue($result);
    }
}
