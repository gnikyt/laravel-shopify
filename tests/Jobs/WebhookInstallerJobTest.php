<?php

namespace OhMyBrew\ShopifyApp\Test\Jobs;

use Illuminate\Support\Facades\Config;
use OhMyBrew\ShopifyApp\Jobs\WebhookInstaller;
use OhMyBrew\ShopifyApp\Models\Shop;
use OhMyBrew\ShopifyApp\Test\Stubs\ApiStub;
use OhMyBrew\ShopifyApp\Test\TestCase;
use ReflectionObject;

class WebhookInstallerJobTest extends TestCase
{
    public function setup()
    {
        parent::setup();

        // Stub with our API
        Config::set('shopify-app.api_class', new ApiStub());
    }

    public function testJobAcceptsLoad()
    {
        $shop = factory(Shop::class)->create();
        $job = new WebhookInstaller($shop);

        $refJob = new ReflectionObject($job);
        $refShop = $refJob->getProperty('shop');
        $refShop->setAccessible(true);

        $this->assertEquals($shop, $refShop->getValue($job));
    }

    public function testJobShouldCreateWebhooks()
    {
        // Stub the responses
        ApiStub::stubResponses([
            'get_webhooks',
            'get_webhooks',
        ]);

        // Set the webhooks
        $webhooks = [
            [
                'topic'   => 'orders/create',
                'address' => 'https://localhost/webhooks/orders-create-two',
            ],
        ];
        Config::set('shopify-app.webhooks', $webhooks);

        $shop = factory(Shop::class)->create();
        $job = new WebhookInstaller($shop);
        $created = $job->handle();

        // $webhooks is new webhooks which does not exist in the JSON fixture
        // for webhooks, so it should create it
        $this->assertEquals(1, count($created));
        $this->assertEquals($webhooks[0], $created[0]);
    }
}
