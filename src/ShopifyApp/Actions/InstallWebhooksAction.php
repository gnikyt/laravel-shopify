<?php

namespace OhMyBrew\ShopifyApp\Actions;

use Illuminate\Support\Facades\Config;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;
use OhMyBrew\ShopifyApp\Interfaces\IShopQuery;
use OhMyBrew\ShopifyApp\Jobs\WebhookInstaller;

/**
 * Attempt to install webhooks on a shop.
 */
class InstallWebhooksAction
{
    /**
     * Querier for shops.
     *
     * @var IShopQuery
     */
    protected $shopQuery;

    /**
     * Setup.
     *
     * @param IShopQuery $shopQuery The querier for the shop.
     *
     * @return self
     */
    public function __construct(IShopQuery $shopQuery)
    {
        $this->shopQuery = $shopQuery;
    }

    /**
     * Execution.
     *
     * @param string $shopDomain The shop's domain.
     *
     * @return bool
     */
    public function __invoke(string $shopDomain): bool
    {
        // Get the shop
        $shop = $this->shopQuery->getByDomain(ShopifyApp::sanitizeShopDomain($shopDomain));
        
        // Get the webhooks
        $webhooks = Config::get('shopify-app.webhooks');
        if (count($webhooks) === 0) {
            // Nothing to do
            return false;
        }

        // Run the installer job
        WebhookInstaller::dispatch($shop)
            ->onQueue(Config::get('shopify-app.job_queues.webhooks'));

        return true;
    }
}
