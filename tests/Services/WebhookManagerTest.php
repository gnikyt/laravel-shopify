<?php

namespace OhMyBrew\ShopifyApp\Test\Services;

use Illuminate\Support\Facades\Config;
use OhMyBrew\ShopifyApp\Models\Shop;
use OhMyBrew\ShopifyApp\Services\WebhookManager;
use OhMyBrew\ShopifyApp\Test\Stubs\ApiStub;
use OhMyBrew\ShopifyApp\Test\TestCase;

class WebhookManagerTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // Stub in our API class
        Config::set('shopify-app.api_class', new ApiStub());
    }

    public function testShouldGetShopWebhooks()
    {
        // Stub the responses
        ApiStub::stubResponses([
            'get_webhooks',
        ]);

        $shop = factory(Shop::class)->create();
        $wm = new WebhookManager($shop);
        $webhooks = $wm->shopWebhooks();

        $this->assertEquals(4759306, $webhooks[0]->id);
    }

    public function testShouldGetConfigWebhooks()
    {
        // Set the webhooks
        $webhooks = [
            [
                'topic'   => 'orders/create',
                'address' => 'https://localhost/webhooks/orders-create',
            ],
        ];
        Config::set('shopify-app.webhooks', $webhooks);

        $shop = factory(Shop::class)->create();
        $wm = new WebhookManager($shop);
        $configWebhooks = $wm->configWebhooks();

        $this->assertEquals($webhooks, $configWebhooks);
    }

    public function testShouldConfirmExistances()
    {
        // Set the webhooks
        $webhooks = [
            [
                'topic'   => 'orders/create',
                'address' => 'https://localhost/webhooks/orders-create',
            ],
            [
                'topic'   => 'app/uninstalled',
                'address' => 'https://localhost/webhooks/app-uninstalled',
            ],
        ];
        Config::set('shopify-app.webhooks', $webhooks);

        // Stub the responses
        ApiStub::stubResponses([
            'get_webhooks',
        ]);

        $shop = factory(Shop::class)->create();
        $wm = new WebhookManager($shop);

        $this->assertTrue($wm->webhookExists($webhooks[0]));
        $this->assertFalse($wm->webhookExists($webhooks[1]));
    }

    public function testShouldCreateWebhooks()
    {
        // Set the webhooks
        $webhooks = [
            [
                'topic'   => 'orders/create',
                'address' => 'https://localhost/webhooks/orders-create',
            ],
            [
                'topic'   => 'app/uninstalled',
                'address' => 'https://localhost/webhooks/app-uninstalled',
            ],
        ];
        Config::set('shopify-app.webhooks', $webhooks);

        // Stub the responses
        ApiStub::stubResponses([
            'get_webhooks',
            'post_webhook',
        ]);

        $shop = factory(Shop::class)->create();
        $wm = new WebhookManager($shop);
        $result = $wm->createWebhooks();

        // Only one should be created
        $this->assertEquals(1, count($result));
    }

    public function testShouldDeleteWebhooks()
    {
        // Set the webhooks
        $webhooks = [
            [
                'topic'   => 'orders/create',
                'address' => 'https://localhost/webhooks/orders-create',
            ],
            [
                'topic'   => 'app/uninstalled',
                'address' => 'https://localhost/webhooks/app-uninstalled',
            ],
        ];
        Config::set('shopify-app.webhooks', $webhooks);

        // Stub the responses
        ApiStub::stubResponses([
            'get_webhooks',
            'webhook',
            'webhook',
        ]);

        $shop = factory(Shop::class)->create();
        $wm = new WebhookManager($shop);
        $result = $wm->deleteWebhooks();

        // Should match fixture
        $this->assertEquals(2, count($result));
    }

    public function testShouldRunRecreate()
    {
        // Stub the responses
        ApiStub::stubResponses([
            'get_webhooks',
            'webhook',
            'webhook',
        ]);

        $shop = factory(Shop::class)->create();
        $wm = new WebhookManager($shop);
        $wm->recreateWebhooks();

        $this->assertTrue(true);
    }
}
