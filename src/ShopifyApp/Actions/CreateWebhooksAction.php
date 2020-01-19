<?php

namespace OhMyBrew\ShopifyApp\Actions;

use Illuminate\Support\Facades\Config;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;
use OhMyBrew\ShopifyApp\Services\IApiHelper;
use OhMyBrew\ShopifyApp\Interfaces\IShopQuery;

/**
 * Create webhooks for this app on the shop.
 */
class CreateWebhooksAction
{
    /**
     * The API helper.
     *
     * @var IApiHelper
     */
    protected $apiHelper;

    /**
     * Querier for shops.
     *
     * @var IShopQuery
     */
    protected $shopQuery;

    /**
     * Setup.
     *
     * @param IApiHelper $apiHelper The API helper.
     * @param IShopQuery $shopQuery The querier for the shop.
     *
     * @return self
     */
    public function __construct(IApiHelper $apiHelper, IShopQuery $shopQuery)
    {
        $this->apiHelper = $apiHelper;
        $this->shopQuery = $shopQuery;
    }

    /**
     * Execution.
     *
     * @param string $shopDomain The shop's domain.
     *
     * @return array
     */
    public function __invoke(string $shopDomain): array
    {
        // Get the shop
        $shop = $this->shopQuery->getByDomain(ShopifyApp::sanitizeShopDomain($shopDomain));

        // Set the API instance
        $this->apiHelper->setInstance($shop->api());

        // Get the webhooks in config
        $configWebhooks = Config::get('shopify-app.webhooks');

        // Get the webhooks existing in for the shop
        $webhooks = $this->apiHelper->getWebhooks();

        /**
         * Checks if a webhooks exists already in the shop.
         *
         * @param array $webhook The webhook config.
         *
         * @return bool
         */
        $exists = function (array $webhook) use ($webhooks): bool {
            foreach ($webhooks as $shopWebhook) {
                if ($shopWebhook->address === $webhook['address']) {
                    // Found the webhook in our list
                    return true;
                }
            }
    
            return false;
        };

        $created = [];
        foreach ($configWebhooks as $webhook) {
            // Check if the required webhook exists on the shop
            if (!$exists($webhook)) {
                // It does not... create the webhook
                $this->api->createWebhook($webhook);

                // Keep track of what was created
                $created[] = $webhook;
            }
        }

        return $created;
    }
}
