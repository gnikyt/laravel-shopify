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
                'topic' => 'ORDERS_CREATE',
                'address' => 'https://localhost/webhooks/orders-create',
            ],
        ];
        $this->app['config']->set('shopify-app.webhooks', $webhooks);

        // Setup API stub
        $this->setApiStub();
        ApiStub::stubResponses([
            'get_webhooks',
            'delete_webhook',
        ]);

        // Create the shop
        $shop = factory($this->model)->create();

        // Run
        $result = call_user_func(
            $this->action,
            $shop->getId(),
            $webhooks
        );

        $this->assertCount(0, $result['created']);
        $this->assertCount(1, $result['deleted']);
        $this->assertSame($result['deleted'][0]['node']['endpoint']['callbackUrl'], 'http://apple.com/uninstall');
    }

    public function testShouldCreate(): void
    {
        // Create the config
        $webhooks = [
            [
                'topic' => 'ORDERS_CREATE',
                'address' => 'https://localhost/webhooks/orders-create',
            ],
            [
                'topic' => 'ORDERS_CREATE',
                'address' => 'https://localhost/webhooks/orders-create-different',
            ],
            [
                'topic' => 'APP_UNINSTALLED',
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

        $this->assertCount(1, $result['created']);
        $this->assertCount(0, $result['deleted']);
    }
}
