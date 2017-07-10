<?php namespace OhMyBrew\ShopifyApp\Test;

use \ReflectionObject;
use Illuminate\Support\Facades\Queue;
use OhMyBrew\ShopifyApp\Jobs\WebhookInstaller;
use OhMyBrew\ShopifyApp\Models\Shop;

class WebhookInstallerJobTest extends TestCase
{
    public function testJobAcceptsLoad()
    {
        $shop = Shop::find(1);
        $webhooks = [
            [
                'topic' => 'orders/create',
                'address' => 'https://localhost/webhooks/orders-create'
            ]
        ];

        $job = new WebhookInstaller($shop, $webhooks);

        $refJob = new ReflectionObject($job);
        $refWebhooks = $refJob->getProperty('webhooks');
        $refWebhooks->setAccessible(true);
        $refShop = $refJob->getProperty('shop');
        $refShop->setAccessible(true);

        $this->assertEquals($webhooks, $refWebhooks->getValue($job));
        $this->assertEquals($shop, $refShop->getValue($job));
    }
}
