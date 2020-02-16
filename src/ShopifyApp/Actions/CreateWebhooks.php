<?php

namespace OhMyBrew\ShopifyApp\Actions;

use OhMyBrew\ShopifyApp\Contracts\ApiHelper as IApiHelper;
use OhMyBrew\ShopifyApp\Contracts\Queries\Shop as IShopQuery;
use OhMyBrew\ShopifyApp\Objects\Values\ShopId;
use OhMyBrew\ShopifyApp\Traits\ConfigAccessible;

/**
 * Create webhooks for this app on the shop.
 */
class CreateWebhooks
{
    use ConfigAccessible;

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
     * TODO: Rethrow an API exception.
     *
     * @param ShopId $shopId         The shop ID.
     * @param array  $configWebhooks The webhooks to add.
     *
     * @return array
     */
    public function __invoke(ShopId $shopId, array $configWebhooks): array
    {
        /**
         * Checks if a webhooks exists already in the shop.
         *
         * @param array $webhook  The webhook config.
         * @param array $webhooks The current webhooks to search.
         *
         * @return bool
         */
        $exists = function (array $webhook, array $webhooks): bool {
            foreach ($webhooks as $shopWebhook) {
                if ($shopWebhook->address === $webhook['address']) {
                    // Found the webhook in our list
                    return true;
                }
            }

            return false;
        };

        // Get the shop
        $shop = $this->shopQuery->getById($shopId);
        $apiHelper = $shop->apiHelper();

        // Get the webhooks existing in for the shop
        $webhooks = $apiHelper->getWebhooks();

        $created = [];
        foreach ($configWebhooks as $webhook) {
            // Check if the required webhook exists on the shop
            if (!$exists($webhook, $webhooks)) {
                // It does not... create the webhook
                $apiHelper->createWebhook($webhook);

                // Keep track of what was created
                $created[] = $webhook;
            }
        }

        return $created;
    }
}
