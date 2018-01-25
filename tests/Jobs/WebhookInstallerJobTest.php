<?php

namespace OhMyBrew\ShopifyApp\Test\Jobs;

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

        // Re-used variables
        $this->shop = Shop::find(1);
        $this->webhooks = [
            [
                'topic'   => 'orders/create',
                'address' => 'https://localhost/webhooks/orders-create',
            ],
        ];

        // Stub with our API
        config(['shopify-app.api_class' => new ApiStub()]);
    }

    public function testJobAcceptsLoad()
    {
        $job = new WebhookInstaller($this->shop, $this->webhooks);

        $refJob = new ReflectionObject($job);
        $refWebhooks = $refJob->getProperty('webhooks');
        $refWebhooks->setAccessible(true);
        $refShop = $refJob->getProperty('shop');
        $refShop->setAccessible(true);

        $this->assertEquals($this->webhooks, $refWebhooks->getValue($job));
        $this->assertEquals($this->shop, $refShop->getValue($job));
    }

    public function testJobShouldTestWebhookExistanceMethod()
    {
        $job = new WebhookInstaller($this->shop, $this->webhooks);

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
        $job = new WebhookInstaller($this->shop, $this->webhooks);
        $created = $job->handle();

        // Webhook JSON comes from fixture JSON which matches $this->webhooks
        // so this should be 0
        $this->assertEquals(0, count($created));
    }

    public function testJobShouldCreateWebhooks()
    {
        $webhooks = [
            [
                'topic'   => 'orders/create',
                'address' => 'https://localhost/webhooks/orders-create-two',
            ],
        ];

        $job = new WebhookInstaller($this->shop, $webhooks);
        $created = $job->handle();

        // $webhooks is new webhooks which does not exist in the JSON fixture
        // for webhooks, so it should create it
        $this->assertEquals(1, count($created));
        $this->assertEquals($webhooks[0], $created[0]);
    }
}
