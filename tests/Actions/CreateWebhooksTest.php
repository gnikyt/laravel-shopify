<?php

namespace Osiset\ShopifyApp\Test\Actions;

use Osiset\ShopifyApp\Actions\CreateWebhooks;
use Osiset\ShopifyApp\Test\Stubs\Api as ApiStub;
use Osiset\ShopifyApp\Test\TestCase;

class CreateWebhooksTest extends TestCase
{
    /**
     * @var \Osiset\ShopifyApp\Actions\CreateWebhooks
     */
    protected $action;

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
                'address' => 'https://localhost/webhooks/orders-create',
            ],
        ];
        $this->app['config']->set('shopify-app.webhooks', $webhooks);

        // Setup API stub
        $this->setApiStub();
        ApiStub::stubResponses([
            'get_webhooks',
            'post_webhook',
        ]);

        // Create the shop
        $shop = factory($this->model)->create();

        // Run
        $result = call_user_func(
            $this->action,
            $shop->getId(),
            $webhooks
        );

        $this->assertEquals(0, count($result['created']));
        $this->assertEquals(1, count($result['deleted']));
        $this->assertTrue($result['deleted'][0]['address'] === 'http://apple.com/uninstall');
    }

    public function testShouldCreate(): void
    {
        // Create the config
        $webhooks = [
            [
                'topic'   => 'orders/create',
                'address' => 'https://localhost/webhooks/orders-create',
            ],
            [
                'topic'   => 'orders/create',
                'address' => 'https://localhost/webhooks/orders-create-different',
            ],
            [
                'topic'   => 'app/uninstalled',
                'address' => 'http://apple.com/uninstall',
            ],
        ];
        $this->app['config']->set('shopify-app.webhooks', $webhooks);

        // Setup API stub
        $this->setApiStub();
        ApiStub::stubResponses([
            'get_webhooks',
            'post_webhook',
        ]);

        // Create the shop
        $shop = factory($this->model)->create();

        // Run
        $result = call_user_func(
            $this->action,
            $shop->getId(),
            $webhooks
        );

        $this->assertEquals(1, count($result['created']));
        $this->assertEquals(0, count($result['deleted']));
    }
}
