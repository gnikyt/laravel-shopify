<?php

namespace OhMyBrew\ShopifyApp\Test\Actions;

use OhMyBrew\ShopifyApp\Actions\CreateWebhooks;
use OhMyBrew\ShopifyApp\Test\TestCase;
use OhMyBrew\ShopifyApp\Test\Stubs\Api as ApiStub;

class CreateWebhooksTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->action = $this->app->make(CreateWebhooks::class);
    }

    public function testShouldNotCreateIfExists(): void
    {
        // Create the config
        $webhooks = [
            [
                'topic'   => 'orders/create',
                'address' => 'https://localhost/webhooks/orders-create'
            ]
        ];
        $this->app['config']->set('shopify-app.webhooks', $webhooks);

        // Setup API stub
        $this->setApiStub();
        ApiStub::stubResponses([
            'get_webhooks',
        ]);

        // Create the shop
        $shop = factory($this->model)->create();

        // Run
        $result = call_user_func(
            $this->action,
            $shop->getId(),
            $webhooks
        );

        $this->assertEquals(0, count($result));
    }

    public function testShouldCreate(): void
    {
        // Create the config
        $webhooks = [
            [
                'topic'   => 'orders/create',
                'address' => 'https://localhost/webhooks/orders-create-different'
            ]
        ];
        $this->app['config']->set('shopify-app.webhooks', $webhooks);

        // Setup API stub
        $this->setApiStub();
        ApiStub::stubResponses([
            'get_webhooks',
            'post_webhook'
        ]);

        // Create the shop
        $shop = factory($this->model)->create();

        // Run
        $result = call_user_func(
            $this->action,
            $shop->getId(),
            $webhooks
        );

        $this->assertEquals(1, count($result));
    }
}
