<?php

namespace OhMyBrew\ShopifyApp\Test\Jobs;

use Illuminate\Support\Facades\Config;
use OhMyBrew\ShopifyApp\Jobs\WebhookInstaller;
use OhMyBrew\ShopifyApp\Models\Shop;
use OhMyBrew\ShopifyApp\Test\Stubs\ApiStub;
use OhMyBrew\ShopifyApp\Test\TestCase;
use ReflectionMethod;
use ReflectionObject;

class WebhookInstallerJobTest extends TestCase
{
    public function setup()
    {
        parent::setup();

        // Webhooks
        $this->webhooks = [
            [
                'topic'   => 'orders/create',
                'address' => 'https://localhost/webhooks/orders-create',
            ],
        ];

        // Stub with our API
        Config::set('shopify-app.api_class', new ApiStub());
    }

    public function testJobAcceptsLoad()
    {
        $shop = factory(Shop::class)->create();
        $job = new WebhookInstaller($shop, $this->webhooks);

        $refJob = new ReflectionObject($job);
        $refWebhooks = $refJob->getProperty('webhooks');
        $refWebhooks->setAccessible(true);
        $refShop = $refJob->getProperty('shop');
        $refShop->setAccessible(true);

        $this->assertEquals($this->webhooks, $refWebhooks->getValue($job));
        $this->assertEquals($shop, $refShop->getValue($job));
    }

    public function testJobShouldTestWebhookExistanceMethod()
    {
        $shop = factory(Shop::class)->create();
        $job = new WebhookInstaller($shop, $this->webhooks);

        $method = new ReflectionMethod($job, 'webhookExists');
        $method->setAccessible(true);

        $result = $method->invoke(
            $job,
            [
                // Existing webhooks
                (object) ['address' => 'http://localhost/webhooks/test'],
            ],
            [
                // Defined webhooks in config
                'address' => 'http://localhost/webhooks/test',
            ]
        );
        $result_2 = $method->invoke(
            $job,
            [
                // Existing webhooks
                (object) ['address' => 'http://localhost/webhooks/test'],
            ],
            [
                // Defined webhook in config
                'address' => 'http://localhost/webhooks/test-two',
            ]
        );

        $this->assertTrue($result);
        $this->assertFalse($result_2);
    }

    public function testJobShouldNotRecreateWebhooks()
    {
        // Stub the responses
        ApiStub::stubResponses([
            'get_webhooks',
        ]);

        $shop = factory(Shop::class)->create();
        $job = new WebhookInstaller($shop, $this->webhooks);
        $created = $job->handle();

        // Webhook JSON comes from fixture JSON which matches $this->webhooks
        // so this should be 0
        $this->assertEquals(0, count($created));
    }

    public function testJobShouldCreateWebhooks()
    {
        // Stub the responses
        ApiStub::stubResponses([
            'get_webhooks',
            'get_webhooks',
        ]);

        $webhooks = [
            [
                'topic'   => 'orders/create',
                'address' => 'https://localhost/webhooks/orders-create-two',
            ],
        ];

        $shop = factory(Shop::class)->create();
        $job = new WebhookInstaller($shop, $webhooks);
        $created = $job->handle();

        // $webhooks is new webhooks which does not exist in the JSON fixture
        // for webhooks, so it should create it
        $this->assertEquals(1, count($created));
        $this->assertEquals($webhooks[0], $created[0]);
    }
}
